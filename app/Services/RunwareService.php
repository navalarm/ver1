<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RunwareService
{
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.runware.api_url', 'https://api.runware.ai/v1');
        $this->apiKey = config('services.runware.api_key');
    }

    /**
     * Upload image to Runware and get inputImage UUID
     */
    public function uploadImage(string $imagePath): ?string
    {
        try {
            $imageContent = file_get_contents($imagePath);
            $base64Image = base64_encode($imageContent);

            $response = $this->sendRequest([
                [
                    'taskType' => 'imageUpload',
                    'taskUUID' => Str::uuid()->toString(),
                    'image' => $base64Image,
                ]
            ]);

            if (isset($response['data'][0]['imageUUID'])) {
                return $response['data'][0]['imageUUID'];
            }

            Log::error('Failed to upload image to Runware', ['response' => $response]);
            return null;
        } catch (\Exception $e) {
            Log::error('Error uploading image to Runware', [
                'error' => $e->getMessage(),
                'file' => $imagePath,
            ]);
            return null;
        }
    }

    /**
     * Create video generation task (async)
     */
    public function createVideoGeneration(
        string $imageUUID,
        string $prompt,
        int $duration = 5
    ): ?array {
        try {
            $taskUUID = Str::uuid()->toString();

            $response = $this->sendRequest([
                [
                    'taskType' => 'videoInference',
                    'taskUUID' => $taskUUID,
                    'deliveryMethod' => 'async',
                    'model' => 'klingai:5@3', // KlingAI Standard model
                    'positivePrompt' => $prompt,
                    'duration' => $duration,
                    'width' => 1280,
                    'height' => 720,
                    'frameImages' => [
                        [
                            'inputImage' => $imageUUID,
                            'frame' => 'first'
                        ]
                    ],
                    'outputFormat' => 'mp4',
                    'outputQuality' => 95,
                    'numberResults' => 1,
                    'includeCost' => true,
                ]
            ]);

            // Initial response should contain taskUUID acknowledgment
            if (isset($response['data'][0]['taskUUID'])) {
                return [
                    'taskUUID' => $response['data'][0]['taskUUID'],
                    'status' => 'queued',
                ];
            }

            if (isset($response['errors'][0])) {
                Log::error('Runware API error', ['error' => $response['errors'][0]]);
                return [
                    'status' => 'error',
                    'error' => $response['errors'][0]['message'] ?? 'Unknown error',
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error creating video generation', [
                'error' => $e->getMessage(),
            ]);
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check generation status (polling)
     */
    public function checkGenerationStatus(string $taskUUID): ?array
    {
        try {
            $response = $this->sendRequest([
                [
                    'taskType' => 'getResponse',
                    'taskUUID' => $taskUUID,
                ]
            ]);

            // Check for completed generation in data array
            if (isset($response['data'][0])) {
                $result = $response['data'][0];

                if (isset($result['status']) && $result['status'] === 'success') {
                    return [
                        'status' => 'completed',
                        'videoURL' => $result['videoURL'] ?? null,
                        'videoUUID' => $result['videoUUID'] ?? null,
                        'cost' => $result['cost'] ?? null,
                    ];
                }
            }

            // Check for errors
            if (isset($response['errors'][0])) {
                $error = $response['errors'][0];
                return [
                    'status' => 'failed',
                    'error' => $error['message'] ?? 'Unknown error',
                ];
            }

            // Still processing
            return [
                'status' => 'processing',
            ];
        } catch (\Exception $e) {
            Log::error('Error checking generation status', [
                'error' => $e->getMessage(),
                'taskUUID' => $taskUUID,
            ]);
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Download video from URL and save to storage
     */
    public function downloadVideo(string $videoURL, string $savePath): bool
    {
        try {
            $videoContent = file_get_contents($videoURL);
            if ($videoContent === false) {
                return false;
            }

            return file_put_contents($savePath, $videoContent) !== false;
        } catch (\Exception $e) {
            Log::error('Error downloading video', [
                'error' => $e->getMessage(),
                'url' => $videoURL,
            ]);
            return false;
        }
    }

    /**
     * Send request to Runware API
     */
    private function sendRequest(array $payload): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl, $payload);

        if (!$response->successful()) {
            Log::error('Runware API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Runware API request failed: ' . $response->status());
        }

        return $response->json();
    }
}
