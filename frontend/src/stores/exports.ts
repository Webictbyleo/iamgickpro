import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { exportAPI } from '@/services/api'
import { useNotifications } from '@/composables/useNotifications'
import type { ExportJob, ExportOptions } from '@/types'

export const useExportsStore = defineStore('exports', () => {
  const { 
    designExported, 
    exportFailed, 
    showError, 
    showSuccess,
    showInfo
  } = useNotifications()
  
  const exportJobs = ref<ExportJob[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    total: 0,
    page: 1,
    totalPages: 1
  })

  // Computed properties
  const pendingJobs = computed(() => 
    exportJobs.value.filter(job => job.status === 'pending')
  )
  
  const processingJobs = computed(() => 
    exportJobs.value.filter(job => job.status === 'processing')
  )
  
  const completedJobs = computed(() => 
    exportJobs.value.filter(job => job.status === 'completed')
  )
  
  const failedJobs = computed(() => 
    exportJobs.value.filter(job => job.status === 'failed')
  )

  const hasActiveJobs = computed(() => 
    pendingJobs.value.length > 0 || processingJobs.value.length > 0
  )

  // Actions
  const fetchExportJobs = async (params?: {
    page?: number
    limit?: number
    status?: 'pending' | 'processing' | 'completed' | 'failed'
    format?: string
    design_id?: string
  }) => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await exportAPI.getExportJobs(params)
      
      if (response.data) {
        exportJobs.value = response.data.data
        pagination.value = response.data.meta
        return { success: true }
      }
      
      return { success: false, error: 'No data received' }
    } catch (err: any) {
      const errorMessage = err?.response?.data?.message || err?.message || 'Failed to load export jobs'
      error.value = errorMessage
      showError('Failed to Load Export Jobs', errorMessage)
      return { success: false, error: errorMessage }
    } finally {
      isLoading.value = false
    }
  }

  const createExportJob = async (data: {
    designId: string
    designName: string
    format: 'png' | 'jpg' | 'jpeg' | 'pdf' | 'svg' | 'mp4' | 'gif'
    options?: ExportOptions
  }) => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await exportAPI.createExportJob({
        designId: data.designId,
        format: data.format,
        options: data.options
      })
      
      if (response.data) {
        const newJob = response.data.data
        exportJobs.value.unshift(newJob)
        showSuccess(`Your ${data.format.toUpperCase()} export for "${data.designName}" has been queued`)
        return { success: true, job: newJob }
      }
      
      return { success: false, error: 'Export job creation failed' }
    } catch (err: any) {
      const errorMessage = err?.response?.data?.message || err?.message || 'Export job creation failed'
      error.value = errorMessage
      exportFailed(errorMessage)
      return { success: false, error: errorMessage }
    } finally {
      isLoading.value = false
    }
  }

  const cancelExportJob = async (id: string) => {
    try {
      await exportAPI.cancelExportJob(id)
      
      // Remove from local state
      const index = exportJobs.value.findIndex(job => job.id === id)
      if (index >= 0) {
        const job = exportJobs.value[index]
        exportJobs.value.splice(index, 1)
        showInfo(`Export job for "${job.designName}" has been cancelled`)
      }
      
      return { success: true }
    } catch (err: any) {
      const errorMessage = err?.response?.data?.message || err?.message || 'Failed to cancel export job'
      showError('Cancellation Failed', errorMessage)
      return { success: false, error: errorMessage }
    }
  }

  const downloadExport = async (job: ExportJob) => {
    try {
      const response = await exportAPI.downloadExport(job.id)
      
      // Create download link
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.download = `${job.designName}.${job.format}`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
      
      designExported(job.format.toUpperCase())
      return { success: true }
    } catch (err: any) {
      const errorMessage = err?.response?.data?.message || err?.message || 'Download failed'
      exportFailed(errorMessage)
      return { success: false, error: errorMessage }
    }
  }

  const retryExportJob = async (id: string) => {
    try {
      const response = await exportAPI.retryExportJob(id)
      
      if (response.data) {
        const updatedJob = response.data.data
        const index = exportJobs.value.findIndex(job => job.id === id)
        if (index >= 0) {
          exportJobs.value[index] = updatedJob
          showSuccess(`Retrying export for "${updatedJob.designName}"`)
        }
        return { success: true, job: updatedJob }
      }
      
      return { success: false, error: 'Retry failed' }
    } catch (err: any) {
      const errorMessage = err?.response?.data?.message || err?.message || 'Failed to retry export'
      showError('Retry Failed', errorMessage)
      return { success: false, error: errorMessage }
    }
  }

  const updateJobProgress = (jobId: string, status: string, progress?: number) => {
    const index = exportJobs.value.findIndex(job => job.id === jobId)
    if (index >= 0) {
      exportJobs.value[index].status = status as any
      if (progress !== undefined) {
        exportJobs.value[index].progress = progress
      }
      if (status === 'completed') {
        exportJobs.value[index].completedAt = new Date().toISOString()
      }
    }
  }

  const clearError = () => {
    error.value = null
  }

  return {
    // State
    exportJobs,
    isLoading,
    error,
    pagination,
    
    // Computed
    pendingJobs,
    processingJobs,
    completedJobs,
    failedJobs,
    hasActiveJobs,
    
    // Actions
    fetchExportJobs,
    createExportJob,
    cancelExportJob,
    downloadExport,
    retryExportJob,
    updateJobProgress,
    clearError
  }
})
