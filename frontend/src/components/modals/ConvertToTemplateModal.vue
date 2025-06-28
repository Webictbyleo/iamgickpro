<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Convert Design to Template</h3>
      
      <form @submit.prevent="handleSubmit">
        <div class="space-y-4">
          <!-- Template Name -->
          <div>
            <label for="templateName" class="block text-sm font-medium text-gray-700 mb-1">
              Template Name
            </label>
            <input
              id="templateName"
              v-model="form.name"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
              :placeholder="design?.name || 'Enter template name'"
            />
          </div>

          <!-- Category -->
          <div>
            <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
              Category <span class="text-red-500">*</span>
            </label>
            <input
              id="category"
              v-model="form.category"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
              placeholder="e.g. social-media, presentation, marketing"
            />
            <p class="text-xs text-gray-500 mt-1">
              Letters, numbers, spaces, hyphens, and underscores only
            </p>
          </div>

          <!-- Description -->
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
              Description
            </label>
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
              :placeholder="design?.description || 'Describe this template'"
            ></textarea>
          </div>

          <!-- Tags -->
          <div>
            <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">
              Tags
            </label>
            <input
              id="tags"
              v-model="tagsInput"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
              placeholder="tag1, tag2, tag3"
            />
            <p class="text-xs text-gray-500 mt-1">
              Separate tags with commas
            </p>
          </div>

          <!-- Options -->
          <div class="space-y-3">
            <div class="flex items-center">
              <input
                id="isPremium"
                v-model="form.isPremium"
                type="checkbox"
                class="h-4 w-4 text-violet-600 focus:ring-violet-500 border-gray-300 rounded"
              />
              <label for="isPremium" class="ml-2 block text-sm text-gray-700">
                Mark as premium template
              </label>
            </div>

            <div class="flex items-center">
              <input
                id="isActive"
                v-model="form.isActive"
                type="checkbox"
                class="h-4 w-4 text-violet-600 focus:ring-violet-500 border-gray-300 rounded"
              />
              <label for="isActive" class="ml-2 block text-sm text-gray-700">
                Make template active/visible
              </label>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 mt-6">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="!form.category || isConverting"
            class="px-4 py-2 text-sm font-medium text-white bg-violet-600 border border-transparent rounded-md hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="isConverting">Converting...</span>
            <span v-else>Convert to Template</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { Design } from '@/types'

interface Props {
  isOpen: boolean
  design: Design | null
}

interface ConvertForm {
  name: string
  category: string
  description: string
  tags: string[]
  isPremium: boolean
  isActive: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  convert: [data: ConvertForm]
}>()

const isConverting = ref(false)
const tagsInput = ref('')

const form = ref<ConvertForm>({
  name: '',
  category: '',
  description: '',
  tags: [],
  isPremium: false,
  isActive: true
})

// Watch for design changes to populate form
watch(() => props.design, (newDesign) => {
  if (newDesign) {
    form.value = {
      name: newDesign.name || newDesign.title || '',
      category: '',
      description: newDesign.description || '',
      tags: [],
      isPremium: false,
      isActive: true
    }
    tagsInput.value = ''
  }
}, { immediate: true })

// Convert tags input to array
const processedTags = computed(() => {
  return tagsInput.value
    .split(',')
    .map(tag => tag.trim())
    .filter(tag => tag.length > 0)
})

const handleSubmit = () => {
  if (!form.value.category) return

  // Process tags
  form.value.tags = processedTags.value

  isConverting.value = true
  emit('convert', { ...form.value })
}

// Reset converting state when modal closes
watch(() => props.isOpen, (isOpen) => {
  if (!isOpen) {
    isConverting.value = false
  }
})

// Expose method to reset converting state
const resetConverting = () => {
  isConverting.value = false
}

defineExpose({
  resetConverting
})
</script>
