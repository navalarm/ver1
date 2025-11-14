<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Pricing Plans') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-gray-100 mb-4">Choose Your Plan</h1>
                <p class="text-xl text-gray-600 dark:text-gray-400">Select the perfect plan for your needs</p>
            </div>

            <div class="grid md:grid-cols-4 gap-6">
                @foreach($plans as $plan)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden border-2 border-gray-200 dark:border-gray-700 hover:border-blue-500 transition-colors">
                        <div class="p-6">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">{{ $plan->name }}</h3>
                            <div class="text-4xl font-bold text-blue-500 mb-4">
                                ${{ number_format($plan->price / 100, 2) }}
                            </div>
                            <p class="text-gray-600 dark:text-gray-400 mb-6">{{ $plan->description }}</p>

                            <ul class="mb-6 space-y-2">
                                <li class="flex items-center text-gray-700 dark:text-gray-300">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    {{ $plan->tokens }} tokens
                                </li>
                                <li class="flex items-center text-gray-700 dark:text-gray-300">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    {{ $plan->tokens }} video generations
                                </li>
                                <li class="flex items-center text-gray-700 dark:text-gray-300">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Never expires
                                </li>
                            </ul>

                            <button
                                onclick="alert('Payment integration coming soon! For now, use: php artisan tokens:add your@email.com {{ $plan->tokens }}')"
                                class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg"
                            >
                                Select Plan
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-12 text-center">
                <p class="text-gray-600 dark:text-gray-400">
                    For testing: Use <code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">php artisan tokens:add your@email.com amount</code> to add tokens
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
