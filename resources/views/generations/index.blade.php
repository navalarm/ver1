<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('My Generations') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if($generations->count() > 0)
                        <div class="grid md:grid-cols-3 gap-6">
                            @foreach($generations as $generation)
                                <div class="border dark:border-gray-700 rounded-lg overflow-hidden">
                                    <img
                                        src="{{ Storage::url($generation->input_image_path) }}"
                                        alt="Generation"
                                        class="w-full h-48 object-cover"
                                    >
                                    <div class="p-4">
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                            {{ $generation->created_at->diffForHumans() }}
                                        </p>
                                        <p class="text-sm mb-2">
                                            <span class="font-bold">Status:</span>
                                            <span class="
                                                @if($generation->status === 'completed') text-green-500
                                                @elseif($generation->status === 'failed') text-red-500
                                                @else text-blue-500
                                                @endif
                                            ">
                                                {{ ucfirst($generation->status) }}
                                            </span>
                                        </p>
                                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-4 line-clamp-2">
                                            {{ $generation->prompt }}
                                        </p>
                                        <a
                                            href="{{ route('generations.show', $generation->hash) }}"
                                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-block"
                                        >
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $generations->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <p class="text-xl mb-4">No generations yet</p>
                            <a href="{{ route('home') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Create Your First Generation
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
