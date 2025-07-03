import { computed } from 'vue'
import { useThemeStore } from '@/stores/theme'

export function useTheme() {
  const themeStore = useThemeStore()

  // Computed properties for common theme checks
  const isDark = computed(() => themeStore.isDark)
  const isLight = computed(() => !themeStore.isDark)
  const currentTheme = computed(() => themeStore.mode)
  const currentColors = computed(() => themeStore.currentColors)

  // Helper functions for getting themed colors
  const getPrimaryColor = (shade: 50 | 100 | 200 | 300 | 400 | 500 | 600 | 700 | 800 | 900 = 500) => {
    return themeStore.getThemedColor('primary', shade)
  }

  const getSecondaryColor = (shade: 50 | 100 | 200 | 300 | 400 | 500 | 600 | 700 | 800 | 900 = 500) => {
    return themeStore.getThemedColor('secondary', shade)
  }

  const getAccentColor = (shade: 50 | 100 | 200 | 300 | 400 | 500 | 600 | 700 | 800 | 900 = 500) => {
    return themeStore.getThemedColor('accent', shade)
  }

  // Helper for generating theme-aware gradients
  const createGradient = (direction: string = 'to right', colorType: 'primary' | 'secondary' | 'accent' = 'primary', shades: (50 | 100 | 200 | 300 | 400 | 500 | 600 | 700 | 800 | 900)[] = [500, 600]) => {
    const colors = shades.map(shade => themeStore.getThemedColor(colorType, shade))
    return `linear-gradient(${direction}, ${colors.join(', ')})`
  }

  // Helper for conditional classes based on theme
  const themeClass = (lightClass: string, darkClass: string) => {
    return isDark.value ? darkClass : lightClass
  }

  // Helper for getting contrasting text color
  const getContrastColor = (backgroundColor: string) => {
    // Simple contrast calculation
    const hex = backgroundColor.replace('#', '')
    const r = parseInt(hex.substr(0, 2), 16)
    const g = parseInt(hex.substr(2, 2), 16)
    const b = parseInt(hex.substr(4, 2), 16)
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
    return luminance > 0.5 ? '#000000' : '#ffffff'
  }

  // Theme-aware component variants
  const getButtonVariant = (variant: 'primary' | 'secondary' | 'outline' | 'ghost' = 'primary') => {
    const variants = {
      primary: isDark.value 
        ? 'bg-primary-600 hover:bg-primary-700 text-white'
        : 'bg-primary-600 hover:bg-primary-700 text-white',
      secondary: isDark.value
        ? 'bg-secondary-700 hover:bg-secondary-600 text-secondary-100'
        : 'bg-secondary-200 hover:bg-secondary-300 text-secondary-900',
      outline: isDark.value
        ? 'border border-gray-600 text-gray-300 hover:bg-gray-700'
        : 'border border-gray-300 text-gray-700 hover:bg-gray-50',
      ghost: isDark.value
        ? 'text-gray-300 hover:bg-gray-700'
        : 'text-gray-700 hover:bg-gray-100'
    }
    return variants[variant]
  }

  const getCardVariant = (variant: 'default' | 'elevated' | 'outlined' = 'default') => {
    const variants = {
      default: isDark.value
        ? 'bg-gray-800 border border-gray-700'
        : 'bg-white border border-gray-200',
      elevated: isDark.value
        ? 'bg-gray-800 shadow-xl border border-gray-700'
        : 'bg-white shadow-xl border border-gray-200',
      outlined: isDark.value
        ? 'bg-transparent border-2 border-gray-600'
        : 'bg-transparent border-2 border-gray-300'
    }
    return variants[variant]
  }

  const getInputVariant = () => {
    return isDark.value
      ? 'bg-gray-700 border-gray-600 text-gray-100 placeholder-gray-400 focus:ring-primary-500 focus:border-primary-500'
      : 'bg-white border-gray-300 text-gray-900 placeholder-gray-500 focus:ring-primary-500 focus:border-primary-500'
  }

  // Export all utilities
  return {
    // Store access
    themeStore,
    
    // Computed properties
    isDark,
    isLight,
    currentTheme,
    currentColors,
    
    // Color helpers
    getPrimaryColor,
    getSecondaryColor,
    getAccentColor,
    createGradient,
    getContrastColor,
    
    // Utility functions
    themeClass,
    getButtonVariant,
    getCardVariant,
    getInputVariant,
    
    // Store actions
    setTheme: themeStore.setMode,
    setColorScheme: themeStore.setColorScheme,
    setCustomColors: themeStore.setCustomColors,
    resetTheme: themeStore.resetToDefaults
  }
}
