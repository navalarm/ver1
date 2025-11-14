<?php

namespace App\Jobs;

use App\Models\Generation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessGeneration implements ShouldQueue
{
    use Queueable;

    public $timeout = 600; // 10 minutes timeout

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Generation $generation
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->generation->update(['status' => 'processing']);

            // Submit generation request to Runware API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.runware.api_key'),
                'Content-Type' => 'application/json',
            ])->post(config('services.runware.api_url') . '/generate', [
                'prompt' => $this->generation->prompt,
                'image' => Storage::url($this->generation->input_image_path),
                // Add other Runware API parameters as needed
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Store task ID for tracking
                $this->generation->update([
                    'runware_task_id' => $data['task_id'] ?? null,
                ]);

                // Poll for completion (this is a simplified example)
                // In production, you might want to use webhooks or separate polling job
                $this->pollForCompletion($data['task_id'] ?? null);
            } else {
                throw new \Exception('Runware API request failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Generation failed', [
                'generation_id' => $this->generation->id,
                'error' => $e->getMessage(),
            ]);

            $this->generation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function pollForCompletion(?string $taskId): void
    {
        if (!$taskId) {
            throw new \Exception('No task ID provided');
        }

        $maxAttempts = 30;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            sleep(10); // Wait 10 seconds between polls

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.runware.api_key'),
            ])->get(config('services.runware.api_url') . '/status/' . $taskId);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'completed') {
                    // Download and save the generated video
                    $videoUrl = $data['output_url'];
                    $videoContent = file_get_contents($videoUrl);
                    $videoPath = 'generations/' . $this->generation->hash . '.mp4';

                    Storage::put($videoPath, $videoContent);

                    $this->generation->update([
                        'status' => 'completed',
                        'output_video_path' => $videoPath,
                    ]);

                    return;
                } elseif ($data['status'] === 'failed') {
                    throw new \Exception('Generation failed on Runware side');
                }
            }

            $attempt++;
        }

        throw new \Exception('Generation timeout: exceeded maximum polling attempts');
    }
}
