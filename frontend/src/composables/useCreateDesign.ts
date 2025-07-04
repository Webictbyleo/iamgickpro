import { ref } from 'vue'

const showDesignSizeModal = ref(false)

export function useCreateDesign() {
  const openModal = () => {
    showDesignSizeModal.value = true
  }

  const closeModal = () => {
    showDesignSizeModal.value = false
  }

  return {
    showDesignSizeModal,
    openModal,
    closeModal
  }
}
