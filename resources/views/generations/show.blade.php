<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Generation Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold mb-2">Status: <span id="status-text" class="text-blue-500">{{ ucfirst($generation->status) }}</span></h3>
                        <p class="text-gray-600 dark:text-gray-400">Created: {{ $generation->created_at->diffForHumans() }}</p>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Input Image -->
                        <div>
                            <h4 class="font-bold mb-2">Input Image</h4>
                            <img src="{{ Storage::url($generation->input_image_path) }}" alt="Input" class="rounded-lg max-w-full">
                        </div>

                        <!-- Output Video or Status -->
                        <div>
                            <h4 class="font-bold mb-2">Output Video</h4>
                            <div id="output-container">
                                @if($generation->status === 'completed' && $generation->output_video_path)
                                    <video controls class="rounded-lg max-w-full">
                                        <source src="{{ Storage::url($generation->output_video_path) }}" type="video/mp4">
                                    </video>
                                @elseif($generation->status === 'failed')
                                    <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded">
                                        <p class="font-bold">Generation Failed</p>
                                        <p>{{ $generation->error_message }}</p>
                                    </div>
                                @else
                                    <div class="bg-blue-100 dark:bg-blue-900 border border-blue-400 text-blue-700 dark:text-blue-200 px-4 py-3 rounded">
                                        <p class="font-bold">Processing...</p>
                                        <p>Your video is being generated. This may take a few minutes.</p>
                                        <div class="mt-4">
                                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-700"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h4 class="font-bold mb-2">Prompt</h4>
                        <p class="text-gray-700 dark:text-gray-300">{{ $generation->prompt }}</p>
                    </div>

                    <div class="mt-6">
                        <a href="{{ route('generations.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Back to All Generations
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($generation->status !== 'completed' && $generation->status !== 'failed')
        <script>
            // Poll for status updates
            setInterval(() => {
                fetch('{{ route('generations.status', $generation->hash) }}')
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('status-text').textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);

                        if (data.status === 'completed' && data.output_video_path) {
                            document.getElementById('output-container').innerHTML = `
                                <video controls class="rounded-lg max-w-full">
                                    <source src="${data.output_video_path}" type="video/mp4">
                                </video>
                            `;
                        } else if (data.status === 'failed') {
                            document.getElementById('output-container').innerHTML = `
                                <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded">
                                    <p class="font-bold">Generation Failed</p>
                                    <p>${data.error_message || 'Unknown error'}</p>
                                </div>
                            `;
                        }
                    });
            }, 5000); // Poll every 5 seconds
        </script>
    @endif
</x-app-layout>
