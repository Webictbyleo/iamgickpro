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
          'bg-white/95 backdrop-blur-xl border border-gray-200/50 shadow-xl shadow-black/10',
          'rounded-2xl overflow-hidden group hover:scale-[1.02] hover:shadow-2xl hover:shadow-black/20',
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
                  :class="[
                    'w-6 h-6 transition-colors duration-300',
                    getIconClasses(notification.type)
                  ]"
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
                class="mt-1.5 text-sm text-gray-600 leading-relaxed font-medium"
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
                class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100/80 focus:outline-none focus:ring-2 focus:ring-gray-300/50 transition-all duration-200 transform hover:scale-110 group/close"
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
    success: 'border-l-4 border-emerald-400',
    error: 'border-l-4 border-red-400', 
    warning: 'border-l-4 border-amber-400',
    info: 'border-l-4 border-blue-400'
  }
  return classes[type as keyof typeof classes] || classes.info
}

const getProgressBarClasses = (type: string) => {
  const classes = {
    success: 'from-emerald-400 to-emerald-500',
    error: 'from-red-400 to-red-500',
    warning: 'from-amber-400 to-amber-500', 
    info: 'from-blue-400 to-blue-500'
  }
  return classes[type as keyof typeof classes] || classes.info
}

const getProgressGradient = (type: string) => {
  const gradients = {
    success: 'linear-gradient(90deg, #10b981, #059669)',
    error: 'linear-gradient(90deg, #f87171, #dc2626)',
    warning: 'linear-gradient(90deg, #fbbf24, #d97706)',
    info: 'linear-gradient(90deg, #60a5fa, #2563eb)'
  }
  return gradients[type as keyof typeof gradients] || gradients.info
}

const getIconBackgroundClasses = (type: string) => {
  const classes = {
    success: 'bg-emerald-100 group-hover:bg-emerald-200',
    error: 'bg-red-100 group-hover:bg-red-200',
    warning: 'bg-amber-100 group-hover:bg-amber-200',
    info: 'bg-blue-100 group-hover:bg-blue-200'
  }
  return classes[type as keyof typeof classes] || classes.info
}

const getIconClasses = (type: string) => {
  const classes = {
    success: 'text-emerald-600 group-hover:text-emerald-700',
    error: 'text-red-600 group-hover:text-red-700',
    warning: 'text-amber-600 group-hover:text-amber-700',
    info: 'text-blue-600 group-hover:text-blue-700'
  }
  return classes[type as keyof typeof classes] || classes.info
}

const getTitleClasses = (type: string) => {
  const classes = {
    success: 'from-emerald-700 to-emerald-900',
    error: 'from-red-700 to-red-900',
    warning: 'from-amber-700 to-amber-900',
    info: 'from-blue-700 to-blue-900'
  }
  return classes[type as keyof typeof classes] || classes.info
}

const getActionButtonClasses = (type: string) => {
  const classes = {
    success: 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 focus:ring-emerald-500',
    error: 'bg-red-50 text-red-700 hover:bg-red-100 focus:ring-red-500',
    warning: 'bg-amber-50 text-amber-700 hover:bg-amber-100 focus:ring-amber-500',
    info: 'bg-blue-50 text-blue-700 hover:bg-blue-100 focus:ring-blue-500'
  }
  return classes[type as keyof typeof classes] || classes.info
}

const getGlowClasses = (type: string) => {
  const classes = {
    success: 'bg-gradient-to-br from-emerald-400/5 to-emerald-600/10',
    error: 'bg-gradient-to-br from-red-400/5 to-red-600/10',
    warning: 'bg-gradient-to-br from-amber-400/5 to-amber-600/10',
    info: 'bg-gradient-to-br from-blue-400/5 to-blue-600/10'
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
