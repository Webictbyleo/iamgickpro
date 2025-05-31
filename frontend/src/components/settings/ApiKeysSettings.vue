<template>
  <div class="space-y-8">
    <!-- API Keys Overview -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-100">
      <div class="flex items-start space-x-4">
        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
          <KeyIcon class="w-6 h-6 text-white" />
        </div>
        <div class="flex-1">
          <h2 class="text-2xl font-bold text-gray-900 mb-2">API Keys</h2>
          <p class="text-gray-600 mb-4">
            Manage your API keys to integrate ImgGick Pro with your applications and services.
          </p>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg p-4">
              <div class="text-2xl font-bold text-blue-600">{{ apiKeys.length }}</div>
              <div class="text-sm text-gray-600">Active Keys</div>
            </div>
            <div class="bg-white rounded-lg p-4">
              <div class="text-2xl font-bold text-green-600">{{ apiUsage.requests.toLocaleString() }}</div>
              <div class="text-sm text-gray-600">Requests This Month</div>
            </div>
            <div class="bg-white rounded-lg p-4">
              <div class="text-2xl font-bold text-purple-600">{{ apiUsage.quota.toLocaleString() }}</div>
              <div class="text-sm text-gray-600">Monthly Quota</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create New API Key -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Create New API Key</h3>
      </div>
      
      <form @submit.prevent="createApiKey" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="keyName" class="block text-sm font-medium text-gray-700 mb-2">
              Key Name
            </label>
            <input
              id="keyName"
              v-model="newApiKey.name"
              type="text"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
              placeholder="e.g. Production API, Dev Environment"
              required
            />
          </div>
          <div>
            <label for="keyDescription" class="block text-sm font-medium text-gray-700 mb-2">
              Description (Optional)
            </label>
            <input
              id="keyDescription"
              v-model="newApiKey.description"
              type="text"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
              placeholder="Brief description of usage"
            />
          </div>
        </div>
        
        <!-- Permissions -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-3">
            Permissions
          </label>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div v-for="permission in availablePermissions" :key="permission.id" class="flex items-center space-x-3">
              <input
                :id="permission.id"
                v-model="newApiKey.permissions"
                :value="permission.id"
                type="checkbox"
                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
              />
              <label :for="permission.id" class="text-sm text-gray-700">
                <span class="font-medium">{{ permission.name }}</span>
                <span class="text-gray-500"> - {{ permission.description }}</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Rate Limit -->
        <div>
          <label for="rateLimit" class="block text-sm font-medium text-gray-700 mb-2">
            Rate Limit (requests per minute)
          </label>
          <select
            id="rateLimit"
            v-model="newApiKey.rateLimit"
            class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
          >
            <option value="60">60 requests/minute</option>
            <option value="120">120 requests/minute</option>
            <option value="300">300 requests/minute</option>
            <option value="600">600 requests/minute</option>
          </select>
        </div>

        <div class="flex justify-end">
          <button
            type="submit"
            :disabled="isCreating"
            class="px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-violet-600 rounded-lg hover:from-indigo-700 hover:to-violet-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg"
          >
            <span v-if="isCreating" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Creating...
            </span>
            <span v-else>Create API Key</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Existing API Keys -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Your API Keys</h3>
      </div>
      
      <div v-if="apiKeys.length === 0" class="p-8 text-center">
        <KeyIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
        <h4 class="text-lg font-medium text-gray-900 mb-2">No API Keys</h4>
        <p class="text-gray-600">Create your first API key to get started with the ImgGick Pro API.</p>
      </div>

      <div v-else class="divide-y divide-gray-200">
        <div v-for="key in apiKeys" :key="key.id" class="p-6">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center space-x-3 mb-2">
                <h4 class="text-lg font-medium text-gray-900">{{ key.name }}</h4>
                <span :class="[
                  'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                  key.status === 'active' ? 'bg-green-100 text-green-800' :
                  key.status === 'inactive' ? 'bg-red-100 text-red-800' :
                  'bg-yellow-100 text-yellow-800'
                ]">
                  {{ key.status.charAt(0).toUpperCase() + key.status.slice(1) }}
                </span>
              </div>
              
              <p v-if="key.description" class="text-gray-600 mb-3">{{ key.description }}</p>
              
              <!-- API Key Display -->
              <div class="bg-gray-50 rounded-lg p-3 mb-4">
                <div class="flex items-center justify-between">
                  <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-700 mb-1">API Key</label>
                    <div class="font-mono text-sm text-gray-900">
                      {{ showKey[key.id] ? key.key : maskApiKey(key.key) }}
                    </div>
                  </div>
                  <div class="flex items-center space-x-2 ml-4">
                    <button
                      @click="toggleKeyVisibility(key.id)"
                      class="p-2 text-gray-500 hover:text-gray-700 rounded"
                      :title="showKey[key.id] ? 'Hide key' : 'Show key'"
                    >
                      <EyeIcon v-if="!showKey[key.id]" class="w-4 h-4" />
                      <EyeSlashIcon v-else class="w-4 h-4" />
                    </button>
                    <button
                      @click="copyApiKey(key.key)"
                      class="p-2 text-gray-500 hover:text-gray-700 rounded"
                      title="Copy to clipboard"
                    >
                      <ClipboardIcon class="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>

              <!-- Key Information -->
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div>
                  <div class="text-xs font-medium text-gray-700">Created</div>
                  <div class="text-sm text-gray-900">{{ formatDate(key.createdAt) }}</div>
                </div>
                <div>
                  <div class="text-xs font-medium text-gray-700">Last Used</div>
                  <div class="text-sm text-gray-900">{{ key.lastUsed ? formatDate(key.lastUsed) : 'Never' }}</div>
                </div>
                <div>
                  <div class="text-xs font-medium text-gray-700">Requests</div>
                  <div class="text-sm text-gray-900">{{ key.requestCount.toLocaleString() }}</div>
                </div>
                <div>
                  <div class="text-xs font-medium text-gray-700">Rate Limit</div>
                  <div class="text-sm text-gray-900">{{ key.rateLimit }}/min</div>
                </div>
              </div>

              <!-- Permissions -->
              <div class="mb-4">
                <div class="text-xs font-medium text-gray-700 mb-2">Permissions</div>
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="permission in key.permissions"
                    :key="permission"
                    class="inline-flex px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full"
                  >
                    {{ getPermissionName(permission) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center space-x-2 ml-4">
              <button
                @click="regenerateKey(key)"
                class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200"
                title="Regenerate key"
              >
                <ArrowPathIcon class="w-4 h-4" />
              </button>
              <button
                @click="toggleKeyStatus(key)"
                :class="[
                  'px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
                  key.status === 'active'
                    ? 'text-red-700 bg-red-50 border border-red-200 hover:bg-red-100'
                    : 'text-green-700 bg-green-50 border border-green-200 hover:bg-green-100'
                ]"
              >
                {{ key.status === 'active' ? 'Disable' : 'Enable' }}
              </button>
              <button
                @click="deleteKey(key)"
                class="px-3 py-2 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors duration-200"
                title="Delete key"
              >
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- API Documentation -->
    <div class="bg-gray-50 rounded-xl p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">API Documentation</h3>
      <p class="text-gray-600 mb-4">
        Learn how to integrate ImgGick Pro into your applications with our comprehensive API documentation.
      </p>
      <div class="flex flex-wrap gap-3">
        <a
          href="/docs/api"
          target="_blank"
          class="inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-600 bg-white border border-indigo-200 rounded-lg hover:bg-indigo-50 transition-colors duration-200"
        >
          <DocumentTextIcon class="w-4 h-4 mr-2" />
          API Reference
        </a>
        <a
          href="/docs/quickstart"
          target="_blank"
          class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200"
        >
          <RocketLaunchIcon class="w-4 h-4 mr-2" />
          Quick Start Guide
        </a>
        <a
          href="/docs/examples"
          target="_blank"
          class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200"
        >
          <CodeBracketIcon class="w-4 h-4 mr-2" />
          Code Examples
        </a>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import {
  KeyIcon,
  EyeIcon,
  EyeSlashIcon,
  ClipboardIcon,
  ArrowPathIcon,
  TrashIcon,
  DocumentTextIcon,
  RocketLaunchIcon,
  CodeBracketIcon
} from '@heroicons/vue/24/outline'

// Types
interface ApiKey {
  id: string
  name: string
  description?: string
  key: string
  status: 'active' | 'inactive'
  permissions: string[]
  rateLimit: number
  createdAt: string
  lastUsed?: string
  requestCount: number
}

interface Permission {
  id: string
  name: string
  description: string
}

// State
const isCreating = ref(false)
const showKey = ref<Record<string, boolean>>({})

const newApiKey = reactive({
  name: '',
  description: '',
  permissions: [] as string[],
  rateLimit: 60
})

const availablePermissions = ref<Permission[]>([
  {
    id: 'designs.read',
    name: 'Read Designs',
    description: 'View and list user designs'
  },
  {
    id: 'designs.write',
    name: 'Create Designs',
    description: 'Create and modify designs'
  },
  {
    id: 'exports.read',
    name: 'Read Exports',
    description: 'View export history and status'
  },
  {
    id: 'exports.write',
    name: 'Create Exports',
    description: 'Trigger new exports'
  },
  {
    id: 'templates.read',
    name: 'Read Templates',
    description: 'Access template library'
  },
  {
    id: 'media.read',
    name: 'Read Media',
    description: 'Access media library'
  },
  {
    id: 'media.write',
    name: 'Upload Media',
    description: 'Upload new media files'
  },
  {
    id: 'webhooks.write',
    name: 'Manage Webhooks',
    description: 'Create and manage webhooks'
  }
])

const apiKeys = ref<ApiKey[]>([
  {
    id: '1',
    name: 'Production API',
    description: 'Main production environment key',
    key: 'imgk_live_1234567890abcdef1234567890abcdef',
    status: 'active',
    permissions: ['designs.read', 'designs.write', 'exports.read', 'exports.write'],
    rateLimit: 300,
    createdAt: '2025-01-15T10:30:00Z',
    lastUsed: '2025-05-30T14:22:00Z',
    requestCount: 15420
  },
  {
    id: '2',
    name: 'Development',
    description: 'Development and testing',
    key: 'imgk_test_abcdef1234567890abcdef1234567890',
    status: 'active',
    permissions: ['designs.read', 'templates.read'],
    rateLimit: 60,
    createdAt: '2025-02-10T09:15:00Z',
    lastUsed: '2025-05-29T16:45:00Z',
    requestCount: 2340
  }
])

const apiUsage = ref({
  requests: 17760,
  quota: 50000
})

// Methods
const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const maskApiKey = (key: string): string => {
  const prefix = key.substring(0, 12)
  const suffix = key.substring(key.length - 4)
  return `${prefix}${'•'.repeat(20)}${suffix}`
}

const toggleKeyVisibility = (keyId: string) => {
  showKey.value[keyId] = !showKey.value[keyId]
}

const copyApiKey = async (key: string) => {
  try {
    await navigator.clipboard.writeText(key)
    console.log('API key copied to clipboard')
    // Show success notification
  } catch (error) {
    console.error('Failed to copy API key:', error)
  }
}

const getPermissionName = (permissionId: string): string => {
  const permission = availablePermissions.value.find(p => p.id === permissionId)
  return permission?.name || permissionId
}

const createApiKey = async () => {
  if (!newApiKey.name.trim() || newApiKey.permissions.length === 0) {
    return
  }

  isCreating.value = true
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1500))
    
    const key = `imgk_${Date.now()}_${Math.random().toString(36).substring(2, 15)}`
    const newKey: ApiKey = {
      id: Date.now().toString(),
      name: newApiKey.name,
      description: newApiKey.description,
      key,
      status: 'active',
      permissions: [...newApiKey.permissions],
      rateLimit: newApiKey.rateLimit,
      createdAt: new Date().toISOString(),
      requestCount: 0
    }

    apiKeys.value.unshift(newKey)
    
    // Reset form
    Object.assign(newApiKey, {
      name: '',
      description: '',
      permissions: [],
      rateLimit: 60
    })

    console.log('API key created:', newKey)
    // Show success notification
  } catch (error) {
    console.error('Error creating API key:', error)
  } finally {
    isCreating.value = false
  }
}

const regenerateKey = async (key: ApiKey) => {
  console.log('Regenerating key:', key.name)
  // Implement key regeneration
}

const toggleKeyStatus = async (key: ApiKey) => {
  key.status = key.status === 'active' ? 'inactive' : 'active'
  console.log('Toggled key status:', key.name, key.status)
  // Implement status toggle
}

const deleteKey = async (key: ApiKey) => {
  if (confirm(`Are you sure you want to delete the API key "${key.name}"? This action cannot be undone.`)) {
    const index = apiKeys.value.findIndex(k => k.id === key.id)
    if (index > -1) {
      apiKeys.value.splice(index, 1)
    }
    console.log('Deleted key:', key.name)
    // Implement key deletion
  }
}
</script>
