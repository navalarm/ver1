<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="text-center">
                        <h1 class="text-4xl font-bold mb-4">AI Video Generation</h1>
                        <p class="text-xl mb-8">Transform your images into stunning videos with AI</p>

                        @auth
                            <p class="mb-6 text-lg">Your token balance: <span class="font-bold">{{ $tokenBalance }}</span></p>
                        @endauth

                        <button
                            @click="$dispatch('open-generation-modal')"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg text-lg"
                        >
                            Generate Video
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Generation Modal -->
    <div
        x-data="{ open: false, imagePreview: null, prompt: '', uploading: false }"
        @open-generation-modal.window="open = true"
        x-show="open"
        class="fixed z-10 inset-0 overflow-y-auto"
        style="display: none;"
    >
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false"></div>

            <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6">
                <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-gray-100">Generate Video</h3>

                <form @submit.prevent="submitGeneration" enctype="multipart/form-data">
                    <!-- Image Upload -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2 text-gray-900 dark:text-gray-100">Upload Image</label>
                        <input
                            type="file"
                            accept="image/*"
                            @change="handleImageUpload"
                            class="block w-full text-sm text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700"
                            required
                        >
                        <div x-show="imagePreview" class="mt-2">
                            <img :src="imagePreview" class="max-w-full h-48 object-cover rounded">
                        </div>
                    </div>

                    <!-- Prompt Input -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2 text-gray-900 dark:text-gray-100">Prompt</label>
                        <textarea
                            x-model="prompt"
                            rows="3"
                            class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg"
                            placeholder="Describe the video you want to generate..."
                            required
                        ></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="open = false"
                            class="px-4 py-2 bg-gray-300 dark:bg-gray-600 rounded-lg"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="uploading"
                            class="px-4 py-2 bg-blue-500 hover:bg-blue-700 text-white rounded-lg disabled:opacity-50"
                        >
                            <span x-show="!uploading">Generate</span>
                            <span x-show="uploading">Processing...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($tempUpload)
        <!-- Auto-process temporary upload -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if(confirm('Continue with your previous generation?')) {
                    fetch('{{ route('generations.process-after-auth') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        }
                    }).then(response => response.json())
                      .then(data => {
                          if(data.redirect_url) {
                              window.location.href = data.redirect_url;
                          }
                      });
                }
            });
        </script>
    @endif

    <script>
        function handleImageUpload(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        function submitGeneration(event) {
            this.uploading = true;
            const formData = new FormData(event.target);
            formData.append('prompt', this.prompt);

            fetch('{{ route('generations.store') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.requires_auth) {
                    window.location.href = '{{ route('login') }}';
                } else if (data.requires_payment) {
                    window.location.href = '{{ route('plans.index') }}';
                } else if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            })
            .finally(() => {
                this.uploading = false;
            });
        }
    </script>
</x-app-layout>
