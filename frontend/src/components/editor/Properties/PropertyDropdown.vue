<template>
  <Listbox v-model="selectedValue" as="div" class="relative">
    <ListboxButton :class="buttonClasses">
      <span class="truncate">{{ selectedLabel }}</span>
      <ChevronDownIcon 
        v-if="showDropdownIcon" 
        class="h-4 w-4 text-gray-400 dark:text-gray-500 ml-2 transition-colors group-hover:text-gray-600 dark:group-hover:text-gray-300" 
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
      <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg bg-white dark:bg-gray-800 py-1 text-sm shadow-lg border border-gray-200 dark:border-gray-600 focus:outline-none">
        <ListboxOption
          v-for="option in options"
          :key="option.value"
          :value="option.value"
          v-slot="{ active, selected }"
        >
          <li :class="[
            'relative cursor-default select-none py-2 px-3 transition-colors',
            active ? 'bg-primary-100 dark:bg-gray-600 text-primary-700 dark:text-gray-100' : 'text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700'
          ]">
            <span :class="[
              'block truncate',
              selected ? 'font-semibold' : 'font-normal'
            ]">
              {{ option.label }}
            </span>
            
            <CheckIcon 
              v-if="selected"
              class="absolute inset-y-0 right-0 flex items-center pr-3 h-5 w-5 text-primary-600 dark:text-primary-400"
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
  'group relative w-full cursor-default rounded-lg py-2 pl-3 text-left border transition-colors duration-200',
  'bg-white dark:bg-gray-800',
  'border-gray-300 dark:border-gray-600',
  'text-gray-900 dark:text-gray-100',
  'hover:border-gray-400 dark:hover:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700',
  'focus:outline-none focus:ring-2 focus:ring-primary-500/50 dark:focus:ring-primary-400/50 focus:border-primary-500 dark:focus:border-primary-400',
  props.showDropdownIcon ? 'pr-10' : 'pr-3',
  props.disabled ? 'opacity-50 cursor-not-allowed hover:border-gray-300 dark:hover:border-gray-600 hover:bg-white dark:hover:bg-gray-800' : 'cursor-pointer'
])
</script>
