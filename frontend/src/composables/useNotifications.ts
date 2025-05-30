import { ref, reactive } from 'vue'

export interface Notification {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title: string
  message?: string
  duration?: number
  persistent?: boolean
}

const notifications = ref<Notification[]>([])

export const useNotifications = () => {
  const addNotification = (notification: Omit<Notification, 'id'>) => {
    const id = `notification_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
    const newNotification: Notification = {
      id,
      duration: 5000,
      ...notification
    }
    
    notifications.value.push(newNotification)
    
    // Auto-remove notification after duration (unless persistent)
    if (!newNotification.persistent && newNotification.duration) {
      setTimeout(() => {
        removeNotification(id)
      }, newNotification.duration)
    }
    
    return id
  }
  
  const removeNotification = (id: string) => {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index >= 0) {
      notifications.value.splice(index, 1)
    }
  }
  
  const clearAll = () => {
    notifications.value = []
  }
  
  // Convenience methods - Both title and message are required for better UX
  const success = (title: string, message: string, options?: Partial<Notification>) => {
    return addNotification({ type: 'success', title, message, ...options })
  }
  
  const error = (title: string, message: string, options?: Partial<Notification>) => {
    return addNotification({ type: 'error', title, message, persistent: true, ...options })
  }
  
  const warning = (title: string, message: string, options?: Partial<Notification>) => {
    return addNotification({ type: 'warning', title, message, ...options })
  }
  
  const info = (title: string, message: string, options?: Partial<Notification>) => {
    return addNotification({ type: 'info', title, message, ...options })
  }
  
  // Quick methods with predefined titles for common scenarios
  const showSuccess = (message: string, options?: Partial<Notification>) => {
    return success('Success!', message, options)
  }
  
  const showError = (message: string, options?: Partial<Notification>) => {
    return error('Error', message, options)
  }
  
  const showWarning = (message: string, options?: Partial<Notification>) => {
    return warning('Warning', message, options)
  }
  
  const showInfo = (message: string, options?: Partial<Notification>) => {
    return info('Information', message, options)
  }
  
  // Specific UX-focused methods for common design platform scenarios
  const designSaved = (designName: string) => {
    return success('Design Saved', `"${designName}" has been saved successfully`)
  }
  
  const designDeleted = (designName: string) => {
    return success('Design Deleted', `"${designName}" has been moved to trash`)
  }
  
  const designExported = (format: string) => {
    return success('Export Complete', `Your design has been exported as ${format.toUpperCase()}`)
  }
  
  const designShared = (platform?: string) => {
    const message = platform ? `Design shared to ${platform}` : 'Design link copied to clipboard'
    return success('Design Shared', message)
  }
  
  const uploadComplete = (fileName: string) => {
    return success('Upload Complete', `"${fileName}" has been uploaded successfully`)
  }
  
  const saveFailed = (reason?: string) => {
    const message = reason || 'Please check your connection and try again'
    return error('Save Failed', message)
  }
  
  const exportFailed = (reason?: string) => {
    const message = reason || 'Please try again or contact support'
    return error('Export Failed', message)
  }
  
  const uploadFailed = (reason?: string) => {
    const message = reason || 'Please check file format and size, then try again'
    return error('Upload Failed', message)
  }
  
  return {
    notifications,
    addNotification,
    removeNotification,
    clearAll,
    // Core methods (require both title and message)
    success,
    error,
    warning,
    info,
    // Quick methods with predefined titles
    showSuccess,
    showError,
    showWarning,
    showInfo,
    // UX-focused methods for design platform scenarios
    designSaved,
    designDeleted,
    designExported,
    designShared,
    uploadComplete,
    saveFailed,
    exportFailed,
    uploadFailed
  }
}
