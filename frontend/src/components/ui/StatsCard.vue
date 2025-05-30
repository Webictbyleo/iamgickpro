<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ title }}</h2>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="stat in stats"
        :key="stat.id"
        class="relative p-4 bg-gradient-to-r rounded-lg text-white"
        :class="stat.bgClass"
      >
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <component :is="getIcon(stat.iconName)" :class="'w-8 h-8'" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold">{{ stat.value }}</div>
            <div class="text-sm opacity-90">{{ stat.label }}</div>
          </div>
        </div>
        <div v-if="stat.change" class="mt-2 text-xs opacity-75">
          <span :class="stat.changeClass">{{ stat.change }}</span>
          <span class="ml-1">vs last month</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useIcons } from '@/composables/useIcons'

interface Stat {
  id: string
  label: string
  value: string | number
  iconName: string // Changed from icon to iconName
  bgClass: string
  change?: string
  changeClass?: string
}

interface Props {
  title: string
  stats: Stat[]
}

defineProps<Props>()

const icons = useIcons()

const getIcon = (iconName: string) => {
  return icons[iconName as keyof typeof icons] || icons.design
}
</script>
