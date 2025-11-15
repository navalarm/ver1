<?php

namespace App\Jobs;

use App\Models\Generation;
use App\Services\RunwareService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CheckGenerationStatus implements ShouldQueue
{
    use Queueable;

    public $timeout = 600;
    public $tries = 1; // Only one try, we handle retries manually

    private const MAX_ATTEMPTS = 30; // 30 attempts * 10 seconds = 5 minutes
    private const RETRY_DELAY = 10; // seconds

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Generation $generation,
        public int $attempt = 1
    ) {}

    /**
     * Execute the job.
     */
    public function handle(RunwareService $runware): void
    {
        try {
            // Check if generation was already completed or failed
            $this->generation->refresh();
            if (in_array($this->generation->status, ['completed', 'failed'])) {
                Log::info('Generation already finished', [
                    'generation_id' => $this->generation->id,
                    'status' => $this->generation->status,
                ]);
                return;
            }

            // Check status with Runware API
            $result = $runware->checkGenerationStatus($this->generation->runware_task_id);

            if (!$result) {
                $this->handleError('No response from Runware API');
                return;
            }

            match ($result['status']) {
                'completed' => $this->handleCompleted($result, $runware),
                'failed', 'error' => $this->handleError($result['error'] ?? 'Unknown error'),
                'processing' => $this->handleProcessing(),
                default => $this->handleError('Unknown status: ' . $result['status']),
            };
        } catch (\Exception $e) {
            Log::error('Error checking generation status', [
                'generation_id' => $this->generation->id,
                'error' => $e->getMessage(),
            ]);

            $this->handleError($e->getMessage());
        }
    }

    /**
     * Handle completed generation
     */
    private function handleCompleted(array $result, RunwareService $runware): void
    {
        if (empty($result['videoURL'])) {
            $this->handleError('No video URL in response');
            return;
        }

        try {
            // Download video
            $videoPath = 'generations/' . $this->generation->hash . '.mp4';
            $localPath = Storage::path($videoPath);

            // Ensure directory exists
            $directory = dirname($localPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $downloaded = $runware->downloadVideo($result['videoURL'], $localPath);

            if (!$downloaded) {
                $this->handleError('Failed to download video');
                return;
            }

            // Update generation record
            $this->generation->update([
                'status' => 'completed',
                'output_video_path' => $videoPath,
            ]);

            Log::info('Video generation completed', [
                'generation_id' => $this->generation->id,
                'hash' => $this->generation->hash,
                'cost' => $result['cost'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving completed video', [
                'generation_id' => $this->generation->id,
                'error' => $e->getMessage(),
            ]);

            $this->handleError('Failed to save video: ' . $e->getMessage());
        }
    }

    /**
     * Handle still processing status
     */
    private function handleProcessing(): void
    {
        // Check if we exceeded max attempts
        if ($this->attempt >= self::MAX_ATTEMPTS) {
            $this->handleError('Generation timeout: exceeded maximum polling attempts');
            return;
        }

        // Dispatch another check after delay
        Log::info('Generation still processing, will check again', [
            'generation_id' => $this->generation->id,
            'attempt' => $this->attempt,
            'max_attempts' => self::MAX_ATTEMPTS,
        ]);

        CheckGenerationStatus::dispatch($this->generation, $this->attempt + 1)
            ->delay(now()->addSeconds(self::RETRY_DELAY));
    }

    /**
     * Handle error
     */
    private function handleError(string $errorMessage): void
    {
        Log::error('Generation failed', [
            'generation_id' => $this->generation->id,
            'error' => $errorMessage,
        ]);

        $this->generation->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}
