<template>
  <div class="space-y-8">
    <!-- Section Header -->
    <div class="text-center">
      <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
        Third-Party Integrations
      </h2>
      <p class="text-gray-600 mt-2">
        Connect external services to enhance your design workflow
      </p>
    </div>

    <!-- AI Services Section -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-8 border border-blue-100">
      <div class="flex items-center mb-6">
        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
          <component :is="icons.cpu" class="w-6 h-6 text-white" />
        </div>
        <div class="ml-4">
          <h3 class="text-xl font-semibold text-gray-900">AI Services</h3>
          <p class="text-gray-600">Connect AI-powered tools for enhanced functionality</p>
        </div>
      </div>

      <div class="space-y-6">
        <!-- OpenAI Integration -->
        <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
          <div class="flex items-start justify-between">
            <div class="flex items-center">
              <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-sm">AI</span>
              </div>
              <div class="ml-4">
                <h4 class="text-lg font-semibold text-gray-900">OpenAI</h4>
                <p class="text-gray-600 text-sm">Enable AI-powered text generation and image creation</p>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <span v-if="openAiConnected" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                <component :is="icons.check" class="w-3 h-3 mr-1" />
                Connected
              </span>
              <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                Not Connected
              </span>
            </div>
          </div>

          <div class="mt-4 space-y-4">
            <!-- Connected State -->
            <div v-if="openAiConnected" class="space-y-4">
              <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                      <component :is="icons.check" class="w-4 h-4 text-white" />
                    </div>
                    <div>
                      <p class="text-sm font-medium text-green-800">OpenAI Connected</p>
                      <p class="text-xs text-green-600">API key configured and ready to use</p>
                    </div>
                  </div>
                  <div class="flex items-center space-x-2">
                    <button
                      @click="testOpenAiConnection"
                      :disabled="testingOpenAi"
                      class="px-3 py-1.5 text-xs font-medium text-green-700 border border-green-300 rounded-md hover:bg-green-100 transition-colors disabled:opacity-50"
                    >
                      <component :is="icons.refresh" class="w-3 h-3 mr-1 inline" :class="{ 'animate-spin': testingOpenAi }" />
                      Test
                    </button>
                    <button
                      @click="disconnectOpenAi"
                      class="px-3 py-1.5 text-xs font-medium text-red-700 border border-red-300 rounded-md hover:bg-red-50 transition-colors"
                    >
                      <component :is="icons.xMark" class="w-3 h-3 mr-1 inline" />
                      Remove
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Update Key Option -->
              <div v-if="showUpdateOpenAi" class="space-y-3">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    New API Key
                  </label>
                  <div class="relative">
                    <input
                      v-model="openAiApiKey"
                      :type="showOpenAiKey ? 'text' : 'password'"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pr-20"
                      placeholder="sk-..."
                      autocomplete="new-password"
                    />
                    <div class="absolute inset-y-0 right-0 flex items-center space-x-2 pr-3">
                      <button
                        @click="showOpenAiKey = !showOpenAiKey"
                        class="text-gray-400 hover:text-gray-600 transition-colors"
                      >
                        <component :is="showOpenAiKey ? icons.eyeSlash : icons.eye" class="w-5 h-5" />
                      </button>
                    </div>
                  </div>
                </div>
                <div class="flex items-center justify-between">
                  <div class="text-sm text-gray-600">
                    <a href="https://platform.openai.com/api-keys" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
                      Get a new OpenAI API key
                    </a>
                  </div>
                  <div class="flex items-center space-x-2">
                    <button
                      @click="saveOpenAiKey"
                      :disabled="savingOpenAi || !openAiApiKey"
                      class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                    >
                      <component :is="icons.check" class="w-4 h-4 mr-1 inline" />
                      Update Key
                    </button>
                    <button
                      @click="cancelUpdateOpenAi"
                      class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      Cancel
                    </button>
                  </div>
                </div>
              </div>
              
              <div v-else class="text-center">
                <button
                  @click="showUpdateOpenAi = true"
                  class="text-sm text-blue-600 hover:text-blue-800 underline"
                >
                  Update API Key
                </button>
              </div>
            </div>

            <!-- Not Connected State -->
            <div v-else class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  API Key
                </label>
                <div class="relative">
                  <input
                    v-model="openAiApiKey"
                    :type="showOpenAiKey ? 'text' : 'password'"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pr-20"
                    placeholder="sk-..."
                    autocomplete="new-password"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    data-lpignore="true"
                    data-form-type="other"
                    data-1p-ignore="true"
                  />
                  <div class="absolute inset-y-0 right-0 flex items-center space-x-2 pr-3">
                    <button
                      @click="showOpenAiKey = !showOpenAiKey"
                      class="text-gray-400 hover:text-gray-600 transition-colors"
                    >
                      <component :is="showOpenAiKey ? icons.eyeSlash : icons.eye" class="w-5 h-5" />
                    </button>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">
                  <a href="https://platform.openai.com/api-keys" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
                    Get your OpenAI API key
                  </a>
                </div>
                <button
                  @click="saveOpenAiKey"
                  :disabled="savingOpenAi || !openAiApiKey"
                  class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                >
                  <component :is="icons.check" class="w-4 h-4 mr-1 inline" />
                  Connect
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Image Processing Services Section -->
    <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl p-8 border border-purple-100">
      <div class="flex items-center mb-6">
        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
          <component :is="icons.photo" class="w-6 h-6 text-white" />
        </div>
        <div class="ml-4">
          <h3 class="text-xl font-semibold text-gray-900">Image Processing</h3>
          <p class="text-gray-600">Connect image processing and editing services</p>
        </div>
      </div>

      <div class="space-y-6">
        <!-- Remove.bg Integration -->
        <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
          <div class="flex items-start justify-between">
            <div class="flex items-center">
              <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-orange-500 rounded-lg flex items-center justify-center">
                <component :is="icons.scissors" class="w-5 h-5 text-white" />
              </div>
              <div class="ml-4">
                <h4 class="text-lg font-semibold text-gray-900">Remove.bg</h4>
                <p class="text-gray-600 text-sm">Automatically remove backgrounds from images</p>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <span v-if="removeBgConnected" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                <component :is="icons.check" class="w-3 h-3 mr-1" />
                Connected
              </span>
              <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                Not Connected
              </span>
            </div>
          </div>

          <div class="mt-4 space-y-4">
            <!-- Connected State -->
            <div v-if="removeBgConnected" class="space-y-4">
              <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                      <component :is="icons.check" class="w-4 h-4 text-white" />
                    </div>
                    <div>
                      <p class="text-sm font-medium text-green-800">Remove.bg Connected</p>
                      <p class="text-xs text-green-600">API key configured and ready to use</p>
                    </div>
                  </div>
                  <div class="flex items-center space-x-2">
                    <button
                      @click="testRemoveBgConnection"
                      :disabled="testingRemoveBg"
                      class="px-3 py-1.5 text-xs font-medium text-green-700 border border-green-300 rounded-md hover:bg-green-100 transition-colors disabled:opacity-50"
                    >
                      <component :is="icons.refresh" class="w-3 h-3 mr-1 inline" :class="{ 'animate-spin': testingRemoveBg }" />
                      Test
                    </button>
                    <button
                      @click="disconnectRemoveBg"
                      class="px-3 py-1.5 text-xs font-medium text-red-700 border border-red-300 rounded-md hover:bg-red-50 transition-colors"
                    >
                      <component :is="icons.xMark" class="w-3 h-3 mr-1 inline" />
                      Remove
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Update Key Option -->
              <div v-if="showUpdateRemoveBg" class="space-y-3">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    New API Key
                  </label>
                  <div class="relative">
                    <input
                      v-model="removeBgApiKey"
                      :type="showRemoveBgKey ? 'text' : 'password'"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors pr-20"
                      placeholder="Enter your Remove.bg API key"
                      autocomplete="new-password"
                    />
                    <div class="absolute inset-y-0 right-0 flex items-center space-x-2 pr-3">
                      <button
                        @click="showRemoveBgKey = !showRemoveBgKey"
                        class="text-gray-400 hover:text-gray-600 transition-colors"
                      >
                        <component :is="showRemoveBgKey ? icons.eyeSlash : icons.eye" class="w-5 h-5" />
                      </button>
                    </div>
                  </div>
                </div>
                <div class="flex items-center justify-between">
                  <div class="text-sm text-gray-600">
                    <a href="https://www.remove.bg/api" target="_blank" class="text-purple-600 hover:text-purple-800 underline">
                      Get a new Remove.bg API key
                    </a>
                  </div>
                  <div class="flex items-center space-x-2">
                    <button
                      @click="saveRemoveBgKey"
                      :disabled="savingRemoveBg || !removeBgApiKey"
                      class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50"
                    >
                      <component :is="icons.check" class="w-4 h-4 mr-1 inline" />
                      Update Key
                    </button>
                    <button
                      @click="cancelUpdateRemoveBg"
                      class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      Cancel
                    </button>
                  </div>
                </div>
              </div>
              
              <div v-else class="text-center">
                <button
                  @click="showUpdateRemoveBg = true"
                  class="text-sm text-purple-600 hover:text-purple-800 underline"
                >
                  Update API Key
                </button>
              </div>
            </div>

            <!-- Not Connected State -->
            <div v-else class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  API Key
                </label>
                <div class="relative">
                  <input
                    v-model="removeBgApiKey"
                    :type="showRemoveBgKey ? 'text' : 'password'"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors pr-20"
                    placeholder="Enter your Remove.bg API key"
                    autocomplete="new-password"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    data-lpignore="true"
                    data-form-type="other"
                    data-1p-ignore="true"
                  />
                  <div class="absolute inset-y-0 right-0 flex items-center space-x-2 pr-3">
                    <button
                      @click="showRemoveBgKey = !showRemoveBgKey"
                      class="text-gray-400 hover:text-gray-600 transition-colors"
                    >
                      <component :is="showRemoveBgKey ? icons.eyeSlash : icons.eye" class="w-5 h-5" />
                    </button>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">
                  <a href="https://www.remove.bg/api" target="_blank" class="text-purple-600 hover:text-purple-800 underline">
                    Get your Remove.bg API key
                  </a>
                </div>
                <button
                  @click="saveRemoveBgKey"
                  :disabled="savingRemoveBg || !removeBgApiKey"
                  class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50"
                >
                  <component :is="icons.check" class="w-4 h-4 mr-1 inline" />
                  Connect
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Usage Information -->
    <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl p-8 border border-amber-100">
      <div class="flex items-center mb-4">
        <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center">
          <component :is="icons.information" class="w-5 h-5 text-white" />
        </div>
        <h3 class="text-lg font-semibold text-gray-900 ml-3">Important Information</h3>
      </div>
      <div class="space-y-3 text-sm text-gray-700">
        <p>• API keys are securely encrypted and stored. We never have access to your credentials.</p>
        <p>• Third-party service usage is subject to their respective terms of service and pricing.</p>
        <p>• You can disconnect any service at any time by removing the API key.</p>
        <p>• Test connections to ensure your API keys are working correctly before use.</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useIcons } from '@/composables/useIcons'
import { integrationsAPI } from '@/services/api'
import type { Integration } from '@/types'

const icons = useIcons()

// State
const integrations = ref<Integration[]>([])
const loading = ref(false)

// OpenAI Integration
const openAiApiKey = ref('')
const showOpenAiKey = ref(false)
const openAiConnected = ref(false)
const savingOpenAi = ref(false)
const testingOpenAi = ref(false)
const showUpdateOpenAi = ref(false)

// Remove.bg Integration
const removeBgApiKey = ref('')
const showRemoveBgKey = ref(false)
const removeBgConnected = ref(false)
const savingRemoveBg = ref(false)
const testingRemoveBg = ref(false)
const showUpdateRemoveBg = ref(false)

// Load integrations on mount
onMounted(async () => {
  await loadIntegrations()
})

const loadIntegrations = async () => {
  loading.value = true
  try {
    const response = await integrationsAPI.getIntegrations()
    integrations.value = response.data.data || []
    
    // Update connection status
    const openAiIntegration = integrations.value.find(i => i.service === 'openai')
    const removeBgIntegration = integrations.value.find(i => i.service === 'removebg')
    
    openAiConnected.value = openAiIntegration?.isConfigured || false
    removeBgConnected.value = removeBgIntegration?.isConfigured || false
  } catch (error) {
    console.error('Failed to load integrations:', error)
  } finally {
    loading.value = false
  }
}

// OpenAI Methods
const saveOpenAiKey = async () => {
  if (!openAiApiKey.value) return
  
  savingOpenAi.value = true
  try {
    await integrationsAPI.saveIntegration({
      serviceName: 'openai',
      credentials: {
        api_key: openAiApiKey.value
      }
    })
    
    openAiConnected.value = true
    openAiApiKey.value = '' // Clear the key for security
    console.log('OpenAI API key saved successfully')
  } catch (error) {
    console.error('Failed to save OpenAI API key:', error)
  } finally {
    savingOpenAi.value = false
  }
}

const testOpenAiConnection = async () => {
  testingOpenAi.value = true
  try {
    const response = await integrationsAPI.testIntegration({
      serviceName: 'openai'
    })
    
    if (response.data.data?.success) {
      console.log('OpenAI connection test successful:', response.data.data.message)
    } else {
      console.error('OpenAI connection test failed:', response.data.data?.message)
    }
  } catch (error) {
    console.error('OpenAI connection test failed:', error)
  } finally {
    testingOpenAi.value = false
  }
}

// Remove.bg Methods
const saveRemoveBgKey = async () => {
  if (!removeBgApiKey.value) return
  
  savingRemoveBg.value = true
  try {
    await integrationsAPI.saveIntegration({
      serviceName: 'removebg',
      credentials: {
        api_key: removeBgApiKey.value
      }
    })
    
    removeBgConnected.value = true
    removeBgApiKey.value = '' // Clear the key for security
    console.log('Remove.bg API key saved successfully')
  } catch (error) {
    console.error('Failed to save Remove.bg API key:', error)
  } finally {
    savingRemoveBg.value = false
  }
}

const testRemoveBgConnection = async () => {
  testingRemoveBg.value = true
  try {
    const response = await integrationsAPI.testIntegration({
      serviceName: 'removebg'
    })
    
    if (response.data.data?.success) {
      console.log('Remove.bg connection test successful:', response.data.data.message)
    } else {
      console.error('Remove.bg connection test failed:', response.data.data?.message)
    }
  } catch (error) {
    console.error('Remove.bg connection test failed:', error)
  } finally {
    testingRemoveBg.value = false
  }
}

// Additional OpenAI methods
const disconnectOpenAi = async () => {
  try {
    await integrationsAPI.removeIntegration('openai')
    openAiConnected.value = false
    openAiApiKey.value = ''
    showUpdateOpenAi.value = false
    console.log('OpenAI integration disconnected successfully')
  } catch (error) {
    console.error('Failed to disconnect OpenAI integration:', error)
  }
}

const cancelUpdateOpenAi = () => {
  showUpdateOpenAi.value = false
  openAiApiKey.value = ''
  showOpenAiKey.value = false
}

// Additional Remove.bg methods
const disconnectRemoveBg = async () => {
  try {
    await integrationsAPI.removeIntegration('removebg')
    removeBgConnected.value = false
    removeBgApiKey.value = ''
    showUpdateRemoveBg.value = false
    console.log('Remove.bg integration disconnected successfully')
  } catch (error) {
    console.error('Failed to disconnect Remove.bg integration:', error)
  }
}

const cancelUpdateRemoveBg = () => {
  showUpdateRemoveBg.value = false
  removeBgApiKey.value = ''
  showRemoveBgKey.value = false
}
</script>
