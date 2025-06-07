<template>
  <Listbox v-model="selectedValue" as="div" class="relative">
    <ListboxButton :class="buttonClasses">
      <span class="truncate">{{ selectedLabel }}</span>
      <ChevronDownIcon 
        v-if="showDropdownIcon" 
        class="h-4 w-4 text-gray-400 ml-2" 
      />
    </ListboxButton>

    <Transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg bg-white py-1 text-sm shadow-lg border border-gray-200 focus:outline-none">
        <ListboxOption
          v-for="option in options"
          :key="option.value"
          :value="option.value"
          v-slot="{ active, selected }"
        >
          <li :class="[
            'relative cursor-default select-none py-2 px-3',
            active ? 'bg-purple-50 text-purple-900' : 'text-gray-900'
          ]">
            <span :class="[
              'block truncate',
              selected ? 'font-semibold' : 'font-normal'
            ]">
              {{ option.label }}
            </span>
            
            <CheckIcon 
              v-if="selected"
              class="absolute inset-y-0 right-0 flex items-center pr-3 h-5 w-5 text-purple-600"
            />
          </li>
        </ListboxOption>
      </ListboxOptions>
    </Transition>
  </Listbox>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Listbox, ListboxButton, ListboxOptions, ListboxOption } from '@headlessui/vue'
import { ChevronDownIcon, CheckIcon } from '@heroicons/vue/24/outline'

interface Option {
  value: string | number
  label: string
}

interface Props {
  value: string | number
  options: Option[]
  placeholder?: string
  disabled?: boolean
  showDropdownIcon?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select an option',
  disabled: false,
  showDropdownIcon: true
})

const emit = defineEmits<{
  update: [value: string | number]
}>()

const selectedValue = computed({
  get: () => props.value,
  set: (value) => emit('update', value)
})

const selectedLabel = computed(() => {
  const option = props.options.find(opt => opt.value === props.value)
  return option?.label || props.placeholder
})

const buttonClasses = computed(() => [
  'relative w-full cursor-default rounded-lg bg-white py-2 pl-3 text-left border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent',
  props.showDropdownIcon ? 'pr-10' : 'pr-3',
  props.disabled ? 'opacity-50 cursor-not-allowed' : ''
])
</script>
