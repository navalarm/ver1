<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessGeneration;
use App\Models\Generation;
use App\Models\TemporaryUpload;
use App\Models\UserToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GenerationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB max
            'prompt' => 'required|string|max:1000',
        ]);

        // If user is not authenticated, store in temporary uploads
        if (!Auth::check()) {
            $imagePath = $request->file('image')->store('temp_uploads');

            TemporaryUpload::create([
                'session_id' => session()->getId(),
                'image_path' => $imagePath,
                'prompt' => $request->prompt,
                'expires_at' => now()->addHours(24),
            ]);

            return response()->json([
                'message' => 'Please login or register to continue',
                'requires_auth' => true,
            ]);
        }

        // Check if user has enough tokens
        $userTokens = UserToken::firstOrCreate(
            ['user_id' => Auth::id()],
            ['balance' => 0]
        );

        if ($userTokens->balance < 1) {
            return response()->json([
                'message' => 'Insufficient tokens',
                'requires_payment' => true,
            ], 402);
        }

        // Deduct token
        $userTokens->decrement('balance');

        // Store image
        $imagePath = $request->file('image')->store('uploads');

        // Create generation record
        $generation = Generation::create([
            'user_id' => Auth::id(),
            'prompt' => $request->prompt,
            'input_image_path' => $imagePath,
            'status' => 'pending',
        ]);

        // Dispatch job
        ProcessGeneration::dispatch($generation);

        return response()->json([
            'message' => 'Generation started',
            'hash' => $generation->hash,
            'redirect_url' => route('generations.show', $generation->hash),
        ]);
    }

    public function show($hash)
    {
        $generation = Generation::where('hash', $hash)->firstOrFail();

        // Only allow owner to view
        if ($generation->user_id !== Auth::id()) {
            abort(403);
        }

        return view('generations.show', compact('generation'));
    }

    public function index()
    {
        $generations = Generation::where('user_id', Auth::id())
            ->latest()
            ->paginate(12);

        return view('generations.index', compact('generations'));
    }

    public function status($hash)
    {
        $generation = Generation::where('hash', $hash)->firstOrFail();

        if ($generation->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json([
            'status' => $generation->status,
            'output_video_path' => $generation->output_video_path
                ? Storage::url($generation->output_video_path)
                : null,
            'error_message' => $generation->error_message,
        ]);
    }

    public function processAfterAuth(Request $request)
    {
        // Retrieve temporary upload and process it
        $tempUpload = TemporaryUpload::where('session_id', session()->getId())
            ->latest()
            ->first();

        if (!$tempUpload) {
            return redirect()->route('home');
        }

        // Check tokens
        $userTokens = UserToken::firstOrCreate(
            ['user_id' => Auth::id()],
            ['balance' => 0]
        );

        if ($userTokens->balance < 1) {
            return redirect()->route('plans.index');
        }

        // Deduct token
        $userTokens->decrement('balance');

        // Move file from temp to permanent storage
        $newPath = str_replace('temp_uploads', 'uploads', $tempUpload->image_path);
        Storage::move($tempUpload->image_path, $newPath);

        // Create generation
        $generation = Generation::create([
            'user_id' => Auth::id(),
            'prompt' => $tempUpload->prompt,
            'input_image_path' => $newPath,
            'status' => 'pending',
        ]);

        // Delete temp upload
        $tempUpload->delete();

        // Dispatch job
        ProcessGeneration::dispatch($generation);

        return redirect()->route('generations.show', $generation->hash);
    }
}
