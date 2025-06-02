<template>
  <div class="space-y-8">
    <!-- Privacy Overview -->
    <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-2xl p-6 border border-emerald-200/50">
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <ShieldCheckIcon class="w-8 h-8 text-emerald-600" />
        </div>
        <div class="ml-4">
          <h3 class="text-lg font-semibold text-emerald-900">Privacy & Security</h3>
          <p class="text-emerald-700 mt-1">Manage your data and protect your account with essential privacy and security controls.</p>
        </div>
      </div>
    </div>

    <!-- Data Management -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm">
      <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
          <ServerIcon class="w-5 h-5 mr-2 text-gray-600" />
          Data Management
        </h3>
        <p class="text-sm text-gray-600 mt-1">Manage your personal data and privacy rights</p>
      </div>
      
      <div class="p-6 space-y-6">
        <!-- Download Data -->
        <div class="flex items-center justify-between p-4 bg-blue-50 rounded-xl border border-blue-200">
          <div class="flex-1">
            <h4 class="text-sm font-medium text-blue-900">Download Your Data</h4>
            <p class="text-sm text-blue-700 mt-1">Get a copy of all your personal data and designs</p>
          </div>
          <button
            @click="requestDataDownload"
            :disabled="dataDownload.loading"
            class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <span v-if="dataDownload.loading" class="flex items-center">
              <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
              Processing...
            </span>
            <span v-else>Request Download</span>
          </button>
        </div>

        <!-- Data Portability -->
        <div class="flex items-center justify-between p-4 bg-green-50 rounded-xl border border-green-200">
          <div class="flex-1">
            <h4 class="text-sm font-medium text-green-900">Data Portability</h4>
            <p class="text-sm text-green-700 mt-1">Export your data in a standard format for use elsewhere</p>
          </div>
          <button
            @click="exportPortableData"
            :disabled="dataExport.loading"
            class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500/20 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <span v-if="dataExport.loading" class="flex items-center">
              <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
              Exporting...
            </span>
            <span v-else>Export Data</span>
          </button>
        </div>

        <!-- Delete Account -->
        <div class="flex items-center justify-between p-4 bg-red-50 rounded-xl border border-red-200">
          <div class="flex-1">
            <h4 class="text-sm font-medium text-red-900">Delete Account</h4>
            <p class="text-sm text-red-700 mt-1">Permanently delete your account and all associated data</p>
          </div>
          <button
            @click="showDeleteConfirmation = true"
            class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-colors"
          >
            Delete Account
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Account Confirmation Modal -->
    <div v-if="showDeleteConfirmation" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl max-w-md w-full p-6">
        <div class="flex items-center mb-4">
          <ExclamationTriangleIcon class="w-6 h-6 text-red-600 mr-3" />
          <h3 class="text-lg font-semibold text-gray-900">Delete Account</h3>
        </div>
        <p class="text-sm text-gray-600 mb-6">
          This action cannot be undone. All your designs, data, and account information will be permanently deleted.
        </p>
        <div class="flex space-x-3">
          <button
            @click="showDeleteConfirmation = false"
            class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="deleteAccount"
            class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-colors"
          >
            Delete Account
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import {
  ShieldCheckIcon,
  ServerIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'
import { useNotifications } from '@/composables/useNotifications'
import { userAPI } from '@/services/api'

// Use notifications
const { showSuccess, showError } = useNotifications()

// UI state
const showDeleteConfirmation = ref(false)

// Data management state
const dataDownload = reactive({
  loading: false,
  lastRequest: null as Date | null
})

const dataExport = reactive({
  loading: false,
  lastExport: null as Date | null
})

// Methods
const requestDataDownload = async () => {
  dataDownload.loading = true
  try {
    const response = await userAPI.downloadData()
    dataDownload.lastRequest = new Date()
    showSuccess('Data download request submitted successfully. You will receive an email with download instructions within 24 hours.')
    console.log('Data download requested successfully')
  } catch (error) {
    console.error('Failed to request data download:', error)
    showError('Failed to request data download. Please try again later.')
  } finally {
    dataDownload.loading = false
  }
}

const exportPortableData = async () => {
  dataExport.loading = true
  try {
    const response = await userAPI.exportData({
      format: 'json',
      includeDesigns: true,
      includeMedia: true,
      includeProjects: true
    })
    
    dataExport.lastExport = new Date()
    
    if (response.data?.data?.downloadUrl) {
      // Use the download URL provided by the backend
      const link = document.createElement('a')
      link.href = response.data.data.downloadUrl
      link.download = `igpro-data-export-${new Date().toISOString().split('T')[0]}.json`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    }
    
    showSuccess('Data exported successfully! Your file has been downloaded.')
    console.log('Data export completed')
  } catch (error) {
    console.error('Failed to export data:', error)
    showError('Failed to export data. Please try again later.')
  } finally {
    dataExport.loading = false
  }
}

const deleteAccount = async () => {
  try {
    const response = await userAPI.deleteAccount()
    console.log('Account deletion initiated:', response.data?.message)
    showDeleteConfirmation.value = false
    
    showSuccess('Account deletion initiated successfully. We\'re sorry to see you go!')
    
    // Clear auth data and redirect after a short delay
    setTimeout(() => {
      localStorage.removeItem('auth_token')
      window.location.href = '/'
    }, 2000)
  } catch (error) {
    console.error('Failed to delete account:', error)
    showError('Failed to delete account. Please try again later.')
  }
}
</script>
