import { defineStore } from 'pinia'
import { ref, computed, readonly } from 'vue'

export type ThemeMode = 'light' | 'dark' | 'system'

export interface ThemeColors {
  name: string
  primary: {
    50: string
    100: string
    200: string
    300: string
    400: string
    500: string
    600: string
    700: string
    800: string
    900: string
  }
  secondary: {
    50: string
    100: string
    200: string
    300: string
    400: string
    500: string
    600: string
    700: string
    800: string
    900: string
  }
  accent?: {
    50: string
    100: string
    200: string
    300: string
    400: string
    500: string
    600: string
    700: string
    800: string
    900: string
    950: string
  }
}

export const themePresets: Record<string, ThemeColors> = {
  default: {
    name: 'Default',
    primary: {
      50: '#eff6ff',
      100: '#dbeafe',
      200: '#bfdbfe',
      300: '#93c5fd',
      400: '#60a5fa',
      500: '#3b82f6',
      600: '#2563eb',
      700: '#1d4ed8',
      800: '#1e40af',
      900: '#1e3a8a',
    },
    secondary: {
      50: '#f8fafc',
      100: '#f1f5f9',
      200: '#e2e8f0',
      300: '#cbd5e1',
      400: '#94a3b8',
      500: '#64748b',
      600: '#475569',
      700: '#334155',
      800: '#1e293b',
      900: '#0f172a',
    },
    accent: {
      50: '#ecfeff',
      100: '#cffafe',
      200: '#a5f3fc',
      300: '#67e8f9',
      400: '#22d3ee',
      500: '#06b6d4',
      600: '#0891b2',
      700: '#0e7490',
      800: '#155e75',
      900: '#164e63',
      950: '#083344',
    }
  },
  purple: {
    name: 'Purple',
    primary: {
      50: '#faf5ff',
      100: '#f3e8ff',
      200: '#e9d5ff',
      300: '#d8b4fe',
      400: '#c084fc',
      500: '#a855f7',
      600: '#9333ea',
      700: '#7c3aed',
      800: '#6b21a8',
      900: '#581c87',
    },
    secondary: {
      50: '#fdf2f8',
      100: '#fce7f3',
      200: '#fbcfe8',
      300: '#f9a8d4',
      400: '#f472b6',
      500: '#ec4899',
      600: '#db2777',
      700: '#be185d',
      800: '#9d174d',
      900: '#831843',
    },
    accent: {
      50: '#fff7ed',
      100: '#ffedd5',
      200: '#fed7aa',
      300: '#fdba74',
      400: '#fb923c',
      500: '#f97316',
      600: '#ea580c',
      700: '#c2410c',
      800: '#9a3412',
      900: '#7c2d12',
      950: '#431407',
    }
  },
  emerald: {
    name: 'Emerald',
    primary: {
      50: '#ecfdf5',
      100: '#d1fae5',
      200: '#a7f3d0',
      300: '#6ee7b7',
      400: '#34d399',
      500: '#10b981',
      600: '#059669',
      700: '#047857',
      800: '#065f46',
      900: '#064e3b',
    },
    secondary: {
      50: '#f0fdf4',
      100: '#dcfce7',
      200: '#bbf7d0',
      300: '#86efac',
      400: '#4ade80',
      500: '#22c55e',
      600: '#16a34a',
      700: '#15803d',
      800: '#166534',
      900: '#14532d',
    },
    accent: {
      50: '#fefce8',
      100: '#fef3c7',
      200: '#fde68a',
      300: '#fcd34d',
      400: '#fbbf24',
      500: '#f59e0b',
      600: '#d97706',
      700: '#b45309',
      800: '#92400e',
      900: '#78350f',
      950: '#451a03',
    }
  },
  orange: {
    name: 'Orange',
    primary: {
      50: '#fff7ed',
      100: '#ffedd5',
      200: '#fed7aa',
      300: '#fdba74',
      400: '#fb923c',
      500: '#f97316',
      600: '#ea580c',
      700: '#c2410c',
      800: '#9a3412',
      900: '#7c2d12',
    },
    secondary: {
      50: '#fefce8',
      100: '#fef3c7',
      200: '#fde68a',
      300: '#fcd34d',
      400: '#fbbf24',
      500: '#f59e0b',
      600: '#d97706',
      700: '#b45309',
      800: '#92400e',
      900: '#78350f',
    },
    accent: {
      50: '#fdf2f8',
      100: '#fce7f3',
      200: '#fbcfe8',
      300: '#f9a8d4',
      400: '#f472b6',
      500: '#ec4899',
      600: '#db2777',
      700: '#be185d',
      800: '#9d174d',
      900: '#831843',
      950: '#500724',
    }
  },
  rose: {
    name: 'Rose',
    primary: {
      50: '#fff1f2',
      100: '#ffe4e6',
      200: '#fecdd3',
      300: '#fda4af',
      400: '#fb7185',
      500: '#f43f5e',
      600: '#e11d48',
      700: '#be123c',
      800: '#9f1239',
      900: '#881337',
    },
    secondary: {
      50: '#fdf2f8',
      100: '#fce7f3',
      200: '#fbcfe8',
      300: '#f9a8d4',
      400: '#f472b6',
      500: '#ec4899',
      600: '#db2777',
      700: '#be185d',
      800: '#9d174d',
      900: '#831843',
    },
    accent: {
      50: '#ecfef9',
      100: '#c7fef2',
      200: '#90fce8',
      300: '#52f5da',
      400: '#20e4c7',
      500: '#00d4aa',
      600: '#00a085',
      700: '#05845c',
      800: '#0a6847',
      900: '#0d553c',
      950: '#043429',
    }
  }
}

export const useThemeStore = defineStore('theme', () => {
  // State
  const mode = ref<ThemeMode>('system')
  const activeColorScheme = ref<string>('default')
  const customColors = ref<ThemeColors | null>(null)
  
  // Computed
  const isDark = computed(() => {
    if (mode.value === 'system') {
      return window.matchMedia('(prefers-color-scheme: dark)').matches
    }
    return mode.value === 'dark'
  })

  const currentColors = computed((): ThemeColors => {
    if (customColors.value) {
      return customColors.value
    }
    return themePresets[activeColorScheme.value] || themePresets.default
  })

  const cssVariables = computed(() => {
    const colors = currentColors.value
    const variables: Record<string, string> = {}

    // Primary colors
    Object.entries(colors.primary).forEach(([shade, color]) => {
      variables[`--color-primary-${shade}`] = color
    })

    // Secondary colors
    Object.entries(colors.secondary).forEach(([shade, color]) => {
      variables[`--color-secondary-${shade}`] = color
    })

    // Accent colors (if available)
    if (colors.accent) {
      Object.entries(colors.accent).forEach(([shade, color]) => {
        variables[`--color-accent-${shade}`] = color
      })
    }

    // Mode-specific variables
    if (isDark.value) {
      variables['--bg-primary'] = '#0f172a'
      variables['--bg-secondary'] = '#1e293b'
      variables['--bg-tertiary'] = '#334155'
      variables['--text-primary'] = '#f8fafc'
      variables['--text-secondary'] = '#cbd5e1'
      variables['--text-tertiary'] = '#94a3b8'
      variables['--border-primary'] = '#334155'
      variables['--border-secondary'] = '#475569'
      variables['--shadow'] = 'rgba(0, 0, 0, 0.3)'
    } else {
      variables['--bg-primary'] = '#ffffff'
      variables['--bg-secondary'] = '#f8fafc'
      variables['--bg-tertiary'] = '#f1f5f9'
      variables['--text-primary'] = '#0f172a'
      variables['--text-secondary'] = '#475569'
      variables['--text-tertiary'] = '#64748b'
      variables['--border-primary'] = '#e2e8f0'
      variables['--border-secondary'] = '#cbd5e1'
      variables['--shadow'] = 'rgba(0, 0, 0, 0.1)'
    }

    return variables
  })

  // Actions
  const setMode = (newMode: ThemeMode) => {
    mode.value = newMode
    localStorage.setItem('theme-mode', newMode)
    applyTheme()
  }

  const setColorScheme = (scheme: string) => {
    if (themePresets[scheme]) {
      activeColorScheme.value = scheme
      customColors.value = null
      localStorage.setItem('theme-color-scheme', scheme)
      // Remove custom colors when setting a normal scheme
      localStorage.removeItem('theme-custom-colors')
      applyTheme()
    }
  }

  const setCustomColors = (colors: ThemeColors) => {
    customColors.value = colors
    localStorage.setItem('theme-custom-colors', JSON.stringify(colors))
    // Remove normal color scheme when setting custom colors
    localStorage.removeItem('theme-color-scheme')
    applyTheme()
  }

  const resetToDefaults = () => {
    mode.value = 'system'
    activeColorScheme.value = 'default'
    customColors.value = null
    localStorage.removeItem('theme-mode')
    localStorage.removeItem('theme-color-scheme')
    localStorage.removeItem('theme-custom-colors')
    applyTheme()
  }

  const applyTheme = () => {
    const root = document.documentElement
    
    // Apply CSS variables
    Object.entries(cssVariables.value).forEach(([property, value]) => {
      root.style.setProperty(property, value)
    })

    // Apply dark class
    if (isDark.value) {
      root.classList.add('dark')
    } else {
      root.classList.remove('dark')
    }

    // Emit theme change event for components that need to react
    window.dispatchEvent(new CustomEvent('theme-changed', {
      detail: {
        mode: mode.value,
        isDark: isDark.value,
        colors: currentColors.value
      }
    }))
  }

  const initializeTheme = () => {
    // Load saved preferences
    const savedMode = localStorage.getItem('theme-mode') as ThemeMode
    const savedScheme = localStorage.getItem('theme-color-scheme')
    const savedCustomColors = localStorage.getItem('theme-custom-colors')

    if (savedMode && ['light', 'dark', 'system'].includes(savedMode)) {
      mode.value = savedMode
    }

    if (savedScheme && themePresets[savedScheme]) {
      activeColorScheme.value = savedScheme
    }

    if (savedCustomColors) {
      try {
        customColors.value = JSON.parse(savedCustomColors)
      } catch (e) {
        console.warn('Failed to parse custom colors from localStorage')
      }
    }

    // Listen for system theme changes
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    mediaQuery.addEventListener('change', () => {
      if (mode.value === 'system') {
        applyTheme()
      }
    })

    // Initial theme application
    applyTheme()
  }

  // Export gradient helpers for the design editor
  const generateGradient = (direction: string = 'to right', colors?: string[]) => {
    const activeColors = colors || [currentColors.value.primary[500], currentColors.value.secondary[500]]
    return `linear-gradient(${direction}, ${activeColors.join(', ')})`
  }

  const getThemedColor = (colorType: 'primary' | 'secondary' | 'accent', shade: keyof ThemeColors['primary'] = 500) => {
    const colors = currentColors.value
    if (colorType === 'accent' && colors.accent) {
      return colors.accent[shade]
    }
    if (colorType === 'primary' || colorType === 'secondary') {
      return colors[colorType][shade]
    }
    return colors.primary[shade]
  }

  // Initialize on store creation
  initializeTheme()

  return {
    // State
    mode: readonly(mode),
    activeColorScheme: readonly(activeColorScheme),
    customColors: readonly(customColors),
    
    // Computed
    isDark,
    currentColors,
    cssVariables,
    
    // Actions
    setMode,
    setColorScheme,
    setCustomColors,
    resetToDefaults,
    applyTheme,
    
    // Helpers
    generateGradient,
    getThemedColor,
    
    // Constants
    themePresets
  }
})
