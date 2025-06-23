import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { LoadingState, ErrorState } from '@/types';

export const useUiStore = defineStore('ui', () => {
  // State
  const loading = ref<LoadingState>({});
  const errors = ref<ErrorState>({});
  const notifications = ref<Array<{
    id: string;
    type: 'success' | 'error' | 'warning' | 'info';
    title: string;
    message: string;
    duration?: number;
  }>>([]);

  // Actions
  const setLoading = (key: string, value: boolean) => {
    loading.value[key] = value;
  };

  const setError = (key: string, error: string | null) => {
    errors.value[key] = error;
  };

  const clearError = (key: string) => {
    errors.value[key] = null;
  };

  const addNotification = (notification: {
    type: 'success' | 'error' | 'warning' | 'info';
    title: string;
    message: string;
    duration?: number;
  }) => {
    const id = Date.now().toString();
    const newNotification = {
      id,
      ...notification,
      duration: notification.duration || 5000,
    };
    
    notifications.value.push(newNotification);
    
    // Auto-remove notification after duration
    setTimeout(() => {
      removeNotification(id);
    }, newNotification.duration);
  };

  const removeNotification = (id: string) => {
    const index = notifications.value.findIndex(n => n.id === id);
    if (index > -1) {
      notifications.value.splice(index, 1);
    }
  };

  const clearAllNotifications = () => {
    notifications.value = [];
  };

  return {
    // State
    loading,
    errors,
    notifications,
    
    // Actions
    setLoading,
    setError,
    clearError,
    addNotification,
    removeNotification,
    clearAllNotifications,
  };
});
