<?php

namespace App\Jobs;

use App\Models\Generation;
use App\Services\RunwareService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessGeneration implements ShouldQueue
{
    use Queueable;

    public $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Generation $generation
    ) {}

    /**
     * Execute the job.
     */
    public function handle(RunwareService $runware): void
    {
        try {
            $this->generation->update(['status' => 'processing']);

            // Step 1: Upload image to Runware
            $localImagePath = Storage::path($this->generation->input_image_path);

            Log::info('Uploading image to Runware', [
                'generation_id' => $this->generation->id,
                'image_path' => $localImagePath,
            ]);

            $imageUUID = $runware->uploadImage($localImagePath);

            if (!$imageUUID) {
                throw new \Exception('Failed to upload image to Runware');
            }

            Log::info('Image uploaded successfully', [
                'generation_id' => $this->generation->id,
                'imageUUID' => $imageUUID,
            ]);

            // Step 2: Create video generation task
            $result = $runware->createVideoGeneration(
                $imageUUID,
                $this->generation->prompt,
                5 // 5 seconds duration
            );

            if (!$result || $result['status'] === 'error') {
                throw new \Exception($result['error'] ?? 'Failed to create video generation');
            }

            // Store task UUID
            $this->generation->update([
                'runware_task_id' => $result['taskUUID'],
            ]);

            Log::info('Video generation started', [
                'generation_id' => $this->generation->id,
                'taskUUID' => $result['taskUUID'],
            ]);

            // Step 3: Dispatch status checking job (delayed 10 seconds)
            CheckGenerationStatus::dispatch($this->generation)
                ->delay(now()->addSeconds(10));

        } catch (\Exception $e) {
            Log::error('Generation failed', [
                'generation_id' => $this->generation->id,
                'error' => $e->getMessage(),
            ]);

            $this->generation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
