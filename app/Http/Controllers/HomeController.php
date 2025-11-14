<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\TemporaryUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        $tempUpload = null;

        // Check for temporary upload if user just logged in
        if (Auth::check()) {
            $tempUpload = TemporaryUpload::where('session_id', session()->getId())
                ->latest()
                ->first();
        }

        $tokenBalance = Auth::check() ? Auth::user()->getTokenBalance() : 0;

        return view('home', compact('tempUpload', 'tokenBalance'));
    }
}
