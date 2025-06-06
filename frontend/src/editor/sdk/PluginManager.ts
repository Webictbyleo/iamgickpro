import type { EditorState, PluginAPI, EditorPlugin, PluginManifest, PluginPermission } from './types'
import type { EventEmitter } from './EventEmitter'

export class PluginManager implements PluginAPI {
  private plugins: Map<string, EditorPlugin> = new Map()

  constructor(
    private state: EditorState,
    private emitter: EventEmitter
  ) {
    // Debug canvas count during PluginManager initialization
    const beforeCanvases = document.querySelectorAll('canvas')
    console.log(`🔍 PluginManager: Starting constructor, global canvas count: ${beforeCanvases.length}`)
    
    // PluginManager initialization complete
    const finalCanvases = document.querySelectorAll('canvas')
    console.log(`🔍 PluginManager: Constructor complete, global canvas count: ${finalCanvases.length}`)
  }

  async loadPlugin(url: string): Promise<void> {
    try {
      // Fetch plugin manifest
      const manifestResponse = await fetch(`${url}/manifest.json`)
      const manifest: PluginManifest = await manifestResponse.json()
      
      // Validate manifest
      this.validateManifest(manifest)
      
      // Create plugin instance
      const plugin: EditorPlugin = {
        id: manifest.id,
        name: manifest.name,
        version: manifest.version,
        url,
        manifest,
        loaded: false
      }
      
      // Create sandboxed iframe
      await this.createPluginIframe(plugin)
      
      this.plugins.set(plugin.id, plugin)
      
      // Call onLoad if defined
      if (plugin.onLoad) {
        plugin.onLoad()
      }
      
      this.emitter.emit('plugin:loaded', plugin.id)
    } catch (error) {
      console.error('Failed to load plugin:', error)
      throw error
    }
  }

  unloadPlugin(id: string): void {
    const plugin = this.plugins.get(id)
    if (plugin) {
      // Call onUnload if defined
      if (plugin.onUnload) {
        plugin.onUnload()
      }
      
      // Remove iframe
      if (plugin.iframe && plugin.iframe.parentNode) {
        plugin.iframe.parentNode.removeChild(plugin.iframe)
      }
      
      // Remove from plugins map
      this.plugins.delete(id)
      this.emitter.emit('plugin:unloaded', id)
    }
  }

  getPlugins(): string[] {
    return Array.from(this.plugins.keys())
  }

  destroy(): void {
    // Unload all plugins
    Array.from(this.plugins.keys()).forEach(pluginId => {
      this.unloadPlugin(pluginId)
    })
    this.plugins.clear()
  }

  private validateManifest(manifest: PluginManifest): void {
    if (!manifest.id || !manifest.name || !manifest.version) {
      throw new Error('Invalid plugin manifest: missing required fields')
    }
    
    if (!manifest.permissions || !Array.isArray(manifest.permissions)) {
      throw new Error('Invalid plugin manifest: permissions must be an array')
    }
  }

  private async createPluginIframe(plugin: EditorPlugin): Promise<void> {
    return new Promise((resolve, reject) => {
      const iframe = document.createElement('iframe')
      iframe.style.display = 'none'
      iframe.sandbox.add('allow-scripts', 'allow-same-origin')
      
      iframe.onload = () => {
        plugin.iframe = iframe
        plugin.loaded = true
        this.setupPluginCommunication(plugin)
        resolve()
      }
      
      iframe.onerror = () => {
        reject(new Error(`Failed to load plugin iframe: ${plugin.url}`))
      }
      
      iframe.src = `${plugin.url}/${plugin.manifest.entry_point}`
      document.body.appendChild(iframe)
    })
  }

  private setupPluginCommunication(plugin: EditorPlugin): void {
    if (!plugin.iframe) return

    // Set up message handling
    const messageHandler = (event: MessageEvent) => {
      if (event.source === plugin.iframe?.contentWindow) {
        if (plugin.onMessage) {
          plugin.onMessage(event)
        }
        this.handlePluginMessage(plugin, event)
      }
    }

    window.addEventListener('message', messageHandler)
    
    // Store cleanup function
    plugin.onUnload = () => {
      window.removeEventListener('message', messageHandler)
    }
  }

  private handlePluginMessage(plugin: EditorPlugin, event: MessageEvent): void {
    const { type, data } = event.data
    
    switch (type) {
      case 'plugin:ready':
        this.emitter.emit('plugin:ready', plugin.id)
        break
      case 'layer:create':
        this.emitter.emit('plugin:layer:create', data)
        break
      case 'layer:update':
        this.emitter.emit('plugin:layer:update', data)
        break
      default:
        console.warn(`Unknown plugin message type: ${type}`)
    }
  }

  private hasPermission(plugin: EditorPlugin, permission: PluginPermission): boolean {
    return plugin.manifest.permissions.includes(permission)
  }

  private loadPluginStyles(plugin: EditorPlugin): void {
    if (!plugin.manifest.styles) return
    
    plugin.manifest.styles.forEach((styleUrl: string) => {
      const link = document.createElement('link')
      link.rel = 'stylesheet'
      link.href = `${plugin.url}/${styleUrl}`
      document.head.appendChild(link)
    })
  }

  // Plugin event handlers
  onSelectionChange(selectedLayers: string[]): void {
    Array.from(this.plugins.values()).forEach(plugin => {
      if (plugin.loaded && plugin.onSelectionChange) {
        plugin.onSelectionChange(selectedLayers)
      }
    })
  }

  onLayerUpdate(layer: any): void {
    Array.from(this.plugins.values()).forEach(plugin => {
      if (plugin.loaded && plugin.onLayerUpdate) {
        plugin.onLayerUpdate(layer)
      }
    })
  }
}
