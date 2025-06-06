<template>
  <EditorLayout />
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import EditorLayout from '@/components/editor/EditorLayout.vue'
import { useDesignStore } from '@/stores/design'

const route = useRoute()
const designStore = useDesignStore()

// Load design when component mounts
onMounted(async () => {
  const designId = route.params.id as string
  if (designId && designId !== 'new') {
    try {
      await designStore.loadDesign(designId)
    } catch (error) {
      console.error('Failed to load design:', error)
      // Handle error - maybe redirect to dashboard
    }
  } else {
    // Create new design
    const newDesign = designStore.createNewDesign(800, 600)
    newDesign.name = 'Untitled Design'
    newDesign.title = 'Untitled Design'
    
    // Save the new design to the backend
    await designStore.saveDesign(newDesign)
  }
})
</script>
