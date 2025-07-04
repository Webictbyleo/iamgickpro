<template>
  <div
    v-if="notifications.length > 0"
    class="fixed top-4 right-4 z-[9999] space-y-3 pointer-events-none"
  >
    <transition-group
      name="toast"
      tag="div"
      class="space-y-3"
    >
      <div
        v-for="notification in notifications"
        :key="notification.id"
        :class="[
          'min-w-80 max-w-md w-full pointer-events-auto transform transition-all duration-300 ease-out',
          'bg-white/95 dark:bg-gray-800/95 backdrop-blur-xl border border-gray-200/50 dark:border-gray-700/50 shadow-xl shadow-black/10 dark:shadow-black/30',
          'rounded-2xl overflow-hidden group hover:scale-[1.02] hover:shadow-2xl hover:shadow-black/20 dark:hover:shadow-black/40',
          getTypeClasses(notification.type)
        ]"
      >
        <!-- Progress bar -->
        <div
          v-if="notification.duration"
          class="h-1 bg-gradient-to-r transition-all duration-100 ease-linear"
          :class="getProgressBarClasses(notification.type)"
          :style="{ 
            width: `${getProgress(notification)}%`,
            background: getProgressGradient(notification.type)
          }"
        ></div>
        
        <div class="p-4">
          <div class="flex items-start">
            <!-- Enhanced icon with background -->
            <div class="flex-shrink-0">
              <div 
                :class="[
                  'w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-300',
                  'group-hover:scale-110 group-hover:-rotate-3',
                  getIconBackgroundClasses(notification.type)
                ]"
              >
                <component 
                  :is="getIcon(notification.type)" 
                  class="w-6 h-6 transition-colors duration-300"
                  :class="getIconClasses(notification.type)"
                />
              </div>
            </div>
            
            <div class="ml-4 w-0 flex-1 pt-1">
              <!-- Enhanced title with gradient -->
              <p :class="[
                'text-sm font-bold transition-colors duration-300',
                'bg-gradient-to-r bg-clip-text text-transparent',
                getTitleClasses(notification.type)
              ]">
                {{ notification.title }}
              </p>
              
              <!-- Enhanced message -->
              <p
                v-if="notification.message"
                class="mt-1.5 text-sm text-gray-600 dark:text-gray-300 leading-relaxed font-medium"
              >
                {{ notification.message }}
              </p>
              
              <!-- Action button if provided -->
              <!-- <button
                v-if="notification.action"
                @click="notification.action.handler"
                :class="[
                  'mt-3 text-xs font-semibold px-3 py-1.5 rounded-lg transition-all duration-200',
                  'hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2',
                  getActionButtonClasses(notification.type)
                ]"
              >
                {{ notification.action.label }}
              </button> -->
            </div>
            
            <!-- Enhanced close button -->
            <div class="ml-4 flex-shrink-0 flex">
              <button
                @click="removeNotification(notification.id)"
                class="p-2 rounded-xl text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100/80 dark:hover:bg-gray-700/80 focus:outline-none focus:ring-2 focus:ring-gray-300/50 dark:focus:ring-gray-600/50 transition-all duration-200 transform hover:scale-110 group/close"
              >
                <span class="sr-only">Close</span>
                <component 
                  :is="icons.x" 
                  class="w-5 h-5 transition-transform duration-200 group-hover/close:rotate-90" 
                />
              </button>
            </div>
          </div>
        </div>
        
        <!-- Subtle glow effect -->
        <div 
          :class="[
            'absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none',
            getGlowClasses(notification.type)
          ]"
        ></div>
      </div>
    </transition-group>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'
import { useIcons } from '@/composables/useIcons'
import { useNotifications } from '@/composables/useNotifications'

const icons = useIcons()
const { notifications, removeNotification } = useNotifications()

// Progress tracking for auto-dismiss notifications
const notificationTimers = new Map<string, { startTime: number; duration: number }>()

// Start timers for notifications with duration
const startTimer = (notification: any) => {
  if (notification.duration && !notification.persistent) {
    notificationTimers.set(notification.id, {
      startTime: Date.now(),
      duration: notification.duration
    })
    
    setTimeout(() => {
      removeNotification(notification.id)
      notificationTimers.delete(notification.id)
    }, notification.duration)
  }
}

// Calculate progress percentage for progress bar
const getProgress = (notification: any) => {
  if (!notification.duration || notification.persistent) return 0
  
  const timer = notificationTimers.get(notification.id)
  if (!timer) return 100
  
  const elapsed = Date.now() - timer.startTime
  const progress = Math.max(0, Math.min(100, (elapsed / timer.duration) * 100))
  return progress
}

// Watch for new notifications and start timers
const unwatchNotifications = computed(() => {
  notifications.value.forEach(notification => {
    if (!notificationTimers.has(notification.id)) {
      startTimer(notification)
    }
  })
  return notifications.value
})

// Type-specific styling functions
const getTypeClasses = (type: string) => {
  const classes = {
    success: 'border-l-4 border-success-400 dark:border-success-500',
    error: 'border-l-4 border-danger-400 dark:border-danger-500', 
    warning: 'border-l-4 border-warning-400 dark:border-warning-500',
    info: 'border-l-4 border-primary-400 dark:border-primary-500'
  }
  return classes[type as keyof typeof classes] || classes.info
}

const getProgressBarClasses = (type: string) => {
  const classes = {
    success: 'from-success-400 to-success-500 dark:from-success-500 dark:to-success-600',
    error: 'from-danger-400 to-danger-500 dark:from-danger-500 dark:to-danger-600',
    warning: 'from-warning-400 to-warning-500 dark:from-warning-500 dark:to-warning-600', 
    info: 'from-primary-400 to-primary-500 dark:from-primary-500 dark:to-primary-600'
  }
  return classes[type as keyof typeof classes] || classes.info
}

const getProgressGradient = (type: string) => {
  const gradients = {
    success: 'linear-gradient(90deg, #22c55e, #16a34a)',
    error: 'linear-gradient(90deg, #ef4444, #dc2626)',
    warning: 'linear-gradient(90deg, #f59e0b, #d97706)',
    info: 'linear-gradient(90deg, var(--color-primary-500), var(--color-primary-600))'
  }
  return gradients[type as keyof typeof gradients] || gradients.info
}

const getIconBackgroundClasses = (type: string) => {
  const classes = {
    success: 'bg-success-100 dark:bg-success-900/30 group-hover:bg-success-200 dark:group-hover:bg-success-900/50',
    error: 'bg-danger-100 dark:bg-danger-900/30 group-hover:bg-danger-200 dark:group-hover:bg-danger-900/50',
    warning: 'bg-warning-100 dark:bg-warning-900/30 group-hover:bg-warning-200 dark:group-hover:bg-warning-900/50',
    info: 'bg-primary-100 dark:bg-primary-900/30 group-hover:bg-primary-200 dark:group-hover:bg-primary-900/50'
  }
  return classes[type as keyof typeof classes] || classes.info
}

const getIconClasses = (type: string): string => {
  const classes = {
    success: 'text-success-600 dark:text-success-400 group-hover:text-success-700 dark:group-hover:text-success-300',
    error: 'text-danger-600 dark:text-danger-400 group-hover:text-danger-700 dark:group-hover:text-danger-300',
    warning: 'text-warning-600 dark:text-warning-400 group-hover:text-warning-700 dark:group-hover:text-warning-300',
    info: 'text-primary-600 dark:text-primary-400 group-hover:text-primary-700 dark:group-hover:text-primary-300'
  }
  return classes[type as keyof typeof classes] || classes.info
}

const getTitleClasses = (type: string) => {
  const classes = {
    success: 'from-success-700 to-success-900 dark:from-success-300 dark:to-success-100',
    error: 'from-danger-700 to-danger-900 dark:from-danger-300 dark:to-danger-100',
    warning: 'from-warning-700 to-warning-900 dark:from-warning-300 dark:to-warning-100',
    info: 'from-primary-700 to-primary-900 dark:from-primary-300 dark:to-primary-100'
  }
  return classes[type as keyof typeof classes] || classes.info
}

const getActionButtonClasses = (type: string) => {
  const classes = {
    success: 'bg-success-50 dark:bg-success-900/20 text-success-700 dark:text-success-300 hover:bg-success-100 dark:hover:bg-success-900/30 focus:ring-success-500 dark:focus:ring-success-400',
    error: 'bg-danger-50 dark:bg-danger-900/20 text-danger-700 dark:text-danger-300 hover:bg-danger-100 dark:hover:bg-danger-900/30 focus:ring-danger-500 dark:focus:ring-danger-400',
    warning: 'bg-warning-50 dark:bg-warning-900/20 text-warning-700 dark:text-warning-300 hover:bg-warning-100 dark:hover:bg-warning-900/30 focus:ring-warning-500 dark:focus:ring-warning-400',
    info: 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 hover:bg-primary-100 dark:hover:bg-primary-900/30 focus:ring-primary-500 dark:focus:ring-primary-400'
  }
  return classes[type as keyof typeof classes] || classes.info
}

const getGlowClasses = (type: string) => {
  const classes = {
    success: 'bg-gradient-to-br from-success-400/5 to-success-600/10 dark:from-success-400/10 dark:to-success-600/20',
    error: 'bg-gradient-to-br from-danger-400/5 to-danger-600/10 dark:from-danger-400/10 dark:to-danger-600/20',
    warning: 'bg-gradient-to-br from-warning-400/5 to-warning-600/10 dark:from-warning-400/10 dark:to-warning-600/20',
    info: 'bg-gradient-to-br from-primary-400/5 to-primary-600/10 dark:from-primary-400/10 dark:to-primary-600/20'
  }
  return classes[type as keyof typeof classes] || classes.info
}

const getIcon = (type: string) => {
  const iconMap = {
    success: icons.success,
    error: icons.error, 
    warning: icons.warning,
    info: icons.info
  }
  return iconMap[type as keyof typeof iconMap] || iconMap.info
}

// Cleanup timers on unmount
onUnmounted(() => {
  notificationTimers.clear()
})
</script>

<style scoped>
.toast-enter-active {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.toast-leave-active {
  transition: all 0.3s cubic-bezier(0.55, 0.085, 0.68, 0.53);
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%) scale(0.8) rotate(10deg);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.8) rotate(-10deg);
}

.toast-move {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* Custom progress bar animation */
@keyframes progress-shrink {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}

/* Hover animations */
.group:hover .toast-icon {
  animation: bounce 0.6s ease-in-out;
}

@keyframes bounce {
  0%, 20%, 50%, 80%, 100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-4px);
  }
  60% {
    transform: translateY(-2px);
  }
}

/* Pulse animation for urgent notifications */
.toast-urgent {
  animation: pulse-border 2s infinite;
}

@keyframes pulse-border {
  0% {
    box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4);
  }
  70% {
    box-shadow: 0 0 0 10px rgba(99, 102, 241, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(99, 102, 241, 0);
  }
}
</style>
