<template>
  <div class="h-full flex flex-col bg-gray-50">
    <!-- Clean Header -->
    <div class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center space-x-4">
        <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center">
          <span class="text-white font-semibold">🎨</span>
        </div>
        <div>
          <h1 class="text-xl font-semibold text-gray-900">Cover Image Generation</h1>
          <p class="text-sm text-gray-600">Generate a viral-optimized cover image for your content (optional)</p>
        </div>
      </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 overflow-auto">
      <div class="p-6">
        <div class="max-w-4xl mx-auto space-y-8">
          
          <!-- Skip Option Notice -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Optional Step</h3>
                <p class="mt-1 text-sm text-blue-700">
                  You can skip cover image generation and proceed directly to the posting behavior step, or generate a cover image to enhance your content's viral potential.
                </p>
              </div>
            </div>
          </div>

          <!-- Cover Image Generator -->
          <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="text-center mb-8">
              <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-2xl">🖼️</span>
              </div>
              <h2 class="text-xl font-semibold text-gray-900 mb-2">Cover Image Generator</h2>
              <p class="text-gray-600 max-w-md mx-auto">
                Create an eye-catching cover image that will help your content stand out and increase engagement.
              </p>
            </div>

            <!-- Prompt Input -->
            <div class="space-y-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">
                  Describe your ideal cover image
                </label>
                <div class="space-y-4">
                  <textarea
                    v-model="imagePrompt"
                    rows="4"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 text-sm"
                    placeholder="Example: A vibrant, modern graphic featuring motivational text about success, with bold typography, energetic colors, and inspiring visual elements that grab attention on social media..."
                  ></textarea>
                  
                  <!-- Quick Suggestions -->
                  <div class="flex flex-wrap gap-2">
                    <span class="text-sm text-gray-600 mr-2">Quick ideas:</span>
                    <button
                      v-for="suggestion in quickSuggestions"
                      :key="suggestion"
                      @click="addSuggestion(suggestion)"
                      class="inline-flex items-center px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition-colors"
                    >
                      {{ suggestion }}
                    </button>
                  </div>
                </div>
              </div>

              <!-- Style Options -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Style</label>
                  <select
                    v-model="imageStyle"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 text-sm"
                  >
                    <option value="vibrant">Vibrant & Energetic</option>
                    <option value="minimalist">Clean & Minimalist</option>
                    <option value="professional">Professional</option>
                    <option value="trendy">Trendy & Modern</option>
                    <option value="artistic">Artistic & Creative</option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Quality</label>
                  <select
                    v-model="imageQuality"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 text-sm"
                  >
                    <option value="standard">Standard (512x512)</option>
                    <option value="high">High (1024x1024)</option>
                    <option value="ultra">Ultra (1536x1536)</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Variations</label>
                  <select
                    v-model="imageVariations"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 text-sm"
                  >
                    <option value="1">1 image</option>
                    <option value="2">2 variations</option>
                    <option value="4">4 variations</option>
                  </select>
                </div>
              </div>

              <!-- Viral Optimization Toggle -->
              <div class="flex items-center justify-between p-4 bg-purple-50 rounded-lg">
                <div>
                  <h3 class="text-sm font-medium text-gray-900">Viral Optimization</h3>
                  <p class="text-sm text-gray-600">Apply AI techniques to maximize engagement potential</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="viralOptimization"
                    class="sr-only peer"
                  >
                  <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                </label>
              </div>

              <!-- Generate Button -->
              <div class="flex justify-center">
                <button
                  @click="generateCoverImage"
                  :disabled="isGenerating || !imagePrompt.trim()"
                  class="px-8 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                >
                  <span v-if="isGenerating" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Generating Cover Image...
                  </span>
                  <span v-else>Generate Cover Image</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Generated Image Display -->
          <div v-if="generatedImage" class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="text-center">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Generated Cover Image</h3>
              <div class="max-w-md mx-auto mb-6">
                <img
                  :src="generatedImage"
                  alt="Generated cover image"
                  class="w-full rounded-lg shadow-md"
                />
              </div>
              <div class="flex justify-center space-x-4">
                <button
                  @click="regenerateImage"
                  class="px-4 py-2 text-purple-600 border border-purple-600 rounded-lg hover:bg-purple-50 transition-colors"
                >
                  Regenerate
                </button>
                <button
                  @click="downloadImage"
                  class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                >
                  Download Image
                </button>
              </div>
            </div>
          </div>

          <!-- No Image Generated State -->
          <div v-else-if="!isGenerating" class="bg-gray-50 rounded-lg border-2 border-dashed border-gray-300 p-12 text-center">
            <div class="text-4xl mb-4">🎨</div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Ready to Generate</h3>
            <p class="text-gray-600">
              Enter a description above and click "Generate Cover Image" to create your viral-optimized cover image.
            </p>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

// Reactive state
const imagePrompt = ref('')
const imageStyle = ref('vibrant')
const imageQuality = ref('high')
const imageVariations = ref('2')
const viralOptimization = ref(true)
const isGenerating = ref(false)
const generatedImage = ref<string | null>(null)

// Quick suggestion options
const quickSuggestions = [
  'motivational quote',
  'bold typography',
  'vibrant colors',
  'modern design',
  'minimalist style',
  'eye-catching',
  'professional look',
  'trendy aesthetic'
]

// Methods
const addSuggestion = (suggestion: string) => {
  if (!imagePrompt.value.includes(suggestion)) {
    if (imagePrompt.value.trim()) {
      imagePrompt.value += `, ${suggestion}`
    } else {
      imagePrompt.value = suggestion
    }
  }
}

const generateCoverImage = async () => {
  if (!imagePrompt.value.trim()) return
  
  isGenerating.value = true
  
  try {
    // Simulate API call to generate image
    await new Promise(resolve => setTimeout(resolve, 3000))
    
    // For demo purposes, use a placeholder image
    generatedImage.value = `https://picsum.photos/512/512?random=${Date.now()}`
    
    console.log('Cover image generated successfully!')
  } catch (error) {
    console.error('Error generating image:', error)
  } finally {
    isGenerating.value = false
  }
}

const regenerateImage = () => {
  generateCoverImage()
}

const downloadImage = () => {
  if (generatedImage.value) {
    const link = document.createElement('a')
    link.href = generatedImage.value
    link.download = 'cover-image.png'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }
}

// Initialize with example prompt for demo
onMounted(() => {
  imagePrompt.value = 'A vibrant, modern cover image with motivational text about success and achievement'
})
</script>

<style scoped>
/* Custom checkbox styling for viral optimization toggle */
</style>
