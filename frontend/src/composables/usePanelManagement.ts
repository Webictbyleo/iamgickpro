import { ref, computed, watch } from 'vue'
import type { Layer } from '@/types'

export interface PanelCache {
  panelType: string
  layerType?: string
  layerId?: number
  lastAccessed: number
  cachedData?: any
}

export interface PanelState {
  activePanel: string | null
  activePanelModal: string | null
  panelCache: Map<string, PanelCache>
  autoCloseContextual: boolean
}

/**
 * Smart panel management with caching and contextual behavior
 */
export function usePanelManagement() {
  // Regular panels that appear in the left sidebar
  const leftPanels = ['elements', 'templates', 'uploads', 'media', 'layers', 'properties', 'animation', 'colors']
  
  // Plugin panels - these are loaded dynamically
  const pluginPanels = ref<string[]>([])
  
  // State
  const activePanel = ref<string | null>('elements')
  const activePanelModal = ref<string | null>(null)
  const panelCache = ref<Map<string, PanelCache>>(new Map())
  const autoCloseContextual = ref(true)
  
  // Cache management settings
  const CACHE_TTL = 5 * 60 * 1000 // 5 minutes
  const MAX_CACHE_SIZE = 10
  
  /**
   * Get panel title for display
   */
  const getPanelTitle = (panel: string) => {
    const titles: Record<string, string> = {
      'elements': 'Elements',
      'templates': 'Templates',
      'uploads': 'Uploads',
      'media': 'Media',
      'layers': 'Layers',
      'properties': 'Properties',
      'animation': 'Animation',
      'colors': 'Colors',
      'image-editing': 'Image Settings',
      'text-editing': 'Text Settings',
      'shape-editing': 'Shape Settings',
      'layer-properties': 'Properties',
      // Plugin panels
      'plugin-removebg': 'Background Remover'
    }
    return titles[panel] || panel.replace('plugin-', '').replace(/[-_]/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
  }
  
  /**
   * Check if a panel is a regular sidebar panel
   */
  const isLeftPanel = (panelType: string): boolean => {
    return leftPanels.includes(panelType)
  }
  
  /**
   * Check if a panel is a plugin panel
   */
  const isPluginPanel = (panelType: string): boolean => {
    return panelType.startsWith('plugin-') || pluginPanels.value.includes(panelType)
  }
  
  /**
   * Register a plugin panel
   */
  const registerPluginPanel = (pluginId: string) => {
    const panelId = `plugin-${pluginId}`
    if (!pluginPanels.value.includes(panelId)) {
      pluginPanels.value.push(panelId)
    }
  }
  
  /**
   * Unregister a plugin panel
   */
  const unregisterPluginPanel = (pluginId: string) => {
    const panelId = `plugin-${pluginId}`
    const index = pluginPanels.value.indexOf(panelId)
    if (index > -1) {
      pluginPanels.value.splice(index, 1)
    }
  }
  
  /**
   * Open a plugin panel
   */
  const openPluginPanel = (pluginId: string, layerId?: number, data?: any) => {
    const panelId = `plugin-${pluginId}`
    registerPluginPanel(pluginId)
    
    // Close regular panels when opening plugin panel
    activePanel.value = null
    activePanelModal.value = panelId
    
    // Cache plugin data
    if (data || layerId) {
      cachePanelData(panelId, 'plugin', layerId, { pluginId, layerId, ...data })
    }
  }
  
  /**
   * Get contextual panel for layer type
   */
  const getContextualPanelForLayer = (layer: Layer | null): string | null => {
    if (!layer) return null
    
    switch (layer.type) {
      case 'image':
        return 'image-editing'
      case 'text':
        return 'text-editing'
      case 'shape':
        return 'shape-editing'
      default:
        return 'layer-properties'
    }
  }
  
  /**
   * Cache panel data for quick access
   */
  const cachePanelData = (panelType: string, layerType?: string, layerId?: number, data?: any) => {
    // Clean old cache entries first
    cleanCache()
    
    const cacheKey = `${panelType}-${layerType || 'general'}-${layerId || 'none'}`
    
    panelCache.value.set(cacheKey, {
      panelType,
      layerType,
      layerId,
      lastAccessed: Date.now(),
      cachedData: data
    })
  }
  
  /**
   * Get cached panel data
   */
  const getCachedPanelData = (panelType: string, layerType?: string, layerId?: number): any => {
    const cacheKey = `${panelType}-${layerType || 'general'}-${layerId || 'none'}`
    const cached = panelCache.value.get(cacheKey)
    
    if (cached && (Date.now() - cached.lastAccessed) < CACHE_TTL) {
      // Update last accessed time
      cached.lastAccessed = Date.now()
      return cached.cachedData
    }
    
    return null
  }
  
  /**
   * Clean expired cache entries
   */
  const cleanCache = () => {
    const now = Date.now()
    const entries = Array.from(panelCache.value.entries())
    
    // Remove expired entries
    for (const [key, cache] of entries) {
      if (now - cache.lastAccessed > CACHE_TTL) {
        panelCache.value.delete(key)
      }
    }
    
    // If still too many entries, remove oldest
    if (panelCache.value.size > MAX_CACHE_SIZE) {
      const sortedEntries = Array.from(panelCache.value.entries())
        .sort(([, a], [, b]) => a.lastAccessed - b.lastAccessed)
      
      const toRemove = sortedEntries.slice(0, panelCache.value.size - MAX_CACHE_SIZE)
      for (const [key] of toRemove) {
        panelCache.value.delete(key)
      }
    }
  }
  
  /**
   * Handle panel changes with smart logic
   */
  const handlePanelChange = (panel: string) => {
    if (isLeftPanel(panel)) {
      // Toggle regular panel
      activePanel.value = activePanel.value === panel ? null : panel
      
      // Close contextual panel when opening regular panel
      if (activePanel.value) {
        activePanelModal.value = null
      }
    } else if (isPluginPanel(panel)) {
      // Handle plugin panel
      activePanel.value = null
      activePanelModal.value = activePanelModal.value === panel ? null : panel
    } else {
      // This is a contextual panel
      handleToggleContextualPanel(panel)
    }
  }
  
  /**
   * Handle contextual panel toggling
   */
  const handleToggleContextualPanel = (panelType: string, data?: any) => {
    // Close regular panels when opening contextual panel
    activePanel.value = null
    
    if (activePanelModal.value === panelType) {
      activePanelModal.value = null // Close if already open
    } else {
      activePanelModal.value = panelType // Open the contextual panel
      
      // Cache any provided data
      if (data) {
        cachePanelData(panelType, data.layerType, data.layerId, data)
      }
    }
  }
  
  /**
   * Handle layer selection change with smart panel management
   */
  const handleLayerSelectionChange = (selectedLayers: Layer[]) => {
    if (!autoCloseContextual.value) return
    
    const selectedLayer = selectedLayers[0] || null
    
    // If no layer selected and contextual panel is open, close it
    if (!selectedLayer && activePanelModal.value && !isLeftPanel(activePanelModal.value)) {
      activePanelModal.value = null
      return
    }
    
    // If layer type changed, suggest appropriate contextual panel
    if (selectedLayer && activePanelModal.value) {
      const suggestedPanel = getContextualPanelForLayer(selectedLayer)
      
      // Only auto-switch if current panel doesn't match layer type
      if (suggestedPanel && suggestedPanel !== activePanelModal.value) {
        // Check if there's cached data for the suggested panel
        const cachedData = getCachedPanelData(suggestedPanel, selectedLayer.type, selectedLayer.id)
        
        activePanelModal.value = suggestedPanel
        
        // Cache current layer info
        cachePanelData(suggestedPanel, selectedLayer.type, selectedLayer.id, {
          layerType: selectedLayer.type,
          layerId: selectedLayer.id,
          properties: selectedLayer.properties
        })
      }
    }
  }
  
  /**
   * Close all panels
   */
  const closeAllPanels = () => {
    activePanel.value = null
    activePanelModal.value = null
  }
  
  /**
   * Close contextual panels only
   */
  const closeContextualPanels = () => {
    if (activePanelModal.value && !isLeftPanel(activePanelModal.value) && !isPluginPanel(activePanelModal.value)) {
      activePanelModal.value = null
    }
  }
  
  /**
   * Close plugin panels only
   */
  const closePluginPanels = () => {
    if (activePanelModal.value && isPluginPanel(activePanelModal.value)) {
      activePanelModal.value = null
    }
  }
  
  /**
   * Get current panel state
   */
  const currentPanelState = computed(() => ({
    activePanel: activePanel.value,
    activePanelModal: activePanelModal.value,
    hasAnyPanel: !!(activePanel.value || activePanelModal.value),
    hasLeftPanel: !!(activePanel.value && isLeftPanel(activePanel.value)),
    hasContextualPanel: !!(activePanelModal.value && !isLeftPanel(activePanelModal.value) && !isPluginPanel(activePanelModal.value)),
    hasPluginPanel: !!(activePanelModal.value && isPluginPanel(activePanelModal.value))
  }))
  
  /**
   * Check if panel is currently active
   */
  const isPanelActive = (panelType: string): boolean => {
    return activePanel.value === panelType || activePanelModal.value === panelType
  }
  
  return {
    // State
    activePanel,
    activePanelModal,
    currentPanelState,
    leftPanels,
    pluginPanels,
    
    // Settings
    autoCloseContextual,
    
    // Methods
    getPanelTitle,
    isLeftPanel,
    isPluginPanel,
    registerPluginPanel,
    unregisterPluginPanel,
    openPluginPanel,
    getContextualPanelForLayer,
    handlePanelChange,
    handleToggleContextualPanel,
    handleLayerSelectionChange,
    closeAllPanels,
    closeContextualPanels,
    closePluginPanels,
    isPanelActive,
    
    // Cache methods
    cachePanelData,
    getCachedPanelData,
    cleanCache
  }
}
