/**
 * FontManager - Advanced font management system for design editor
 * Features: Google Fonts integration, font loading, caching, and fallback management
 */

export interface FontVariant {
  weight: string
  style: 'normal' | 'italic'
  subset?: string[]
}

export interface FontFamily {
  family: string
  category: 'serif' | 'sans-serif' | 'display' | 'handwriting' | 'monospace'
  variants: FontVariant[]
  subsets: string[]
  popularity?: number
  previewUrl?: string
  loaded?: boolean
  loading?: boolean
  error?: boolean
}

export interface FontLoadOptions {
  weights?: string[]
  styles?: string[]
  subsets?: string[]
  display?: 'auto' | 'block' | 'swap' | 'fallback' | 'optional'
}

class FontManagerService {
  private static instance: FontManagerService
  private loadedFonts = new Set<string>()
  private loadingFonts = new Map<string, Promise<void>>()
  private fontCache = new Map<string, FontFamily>()
  private googleFontsApiKey = '' // Will be configured
  private googleFontsBaseUrl = 'https://fonts.googleapis.com/css2'
  
  // Comprehensive Google Fonts database (similar to Canva)
  private googleFontsDatabase: FontFamily[] = [
    // Popular Sans-Serif Fonts
    { family: 'Inter', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'], popularity: 1 },
    { family: 'Roboto', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'], popularity: 2 },
    { family: 'Open Sans', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'], popularity: 3 },
    { family: 'Lato', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'], popularity: 4 },
    { family: 'Montserrat', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'], popularity: 5 },
    { family: 'Poppins', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'], popularity: 6 },
    { family: 'Source Sans Pro', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'], popularity: 7 },
    { family: 'Nunito', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'], popularity: 8 },
    { family: 'Raleway', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'], popularity: 9 },
    { family: 'Ubuntu', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'], popularity: 10 },
    
    // Additional Sans-Serif
    { family: 'Work Sans', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Rubik', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'DM Sans', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Barlow', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Manrope', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'PT Sans', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Cabin', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Karla', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Fira Sans', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Hind', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Oxygen', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Mulish', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Public Sans', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'IBM Plex Sans', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Figtree', category: 'sans-serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    
    // Serif Fonts
    { family: 'Merriweather', category: 'serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'], popularity: 11 },
    { family: 'Playfair Display', category: 'serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'], popularity: 12 },
    { family: 'Crimson Text', category: 'serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Libre Baskerville', category: 'serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Lora', category: 'serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Source Serif Pro', category: 'serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'PT Serif', category: 'serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Cormorant Garamond', category: 'serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Bitter', category: 'serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Arvo', category: 'serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Zilla Slab', category: 'serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Spectral', category: 'serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'IBM Plex Serif', category: 'serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Alegreya', category: 'serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Vollkorn', category: 'serif', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    
    // Display Fonts
    { family: 'Oswald', category: 'display', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'], popularity: 13 },
    { family: 'Anton', category: 'display', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Bebas Neue', category: 'display', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Righteous', category: 'display', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Fredoka One', category: 'display', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Comfortaa', category: 'display', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Pacifico', category: 'display', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Lobster', category: 'display', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Bungee', category: 'display', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Archivo Black', category: 'display', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Titan One', category: 'display', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Kalam', category: 'display', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Creepster', category: 'display', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Squada One', category: 'display', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Orbitron', category: 'display', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    
    // Handwriting/Script Fonts
    { family: 'Caveat', category: 'handwriting', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Dancing Script', category: 'handwriting', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Great Vibes', category: 'handwriting', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Satisfy', category: 'handwriting', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Kaushan Script', category: 'handwriting', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Amatic SC', category: 'handwriting', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Permanent Marker', category: 'handwriting', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Indie Flower', category: 'handwriting', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Shadows Into Light', category: 'handwriting', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Handlee', category: 'handwriting', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Patrick Hand', category: 'handwriting', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Courgette', category: 'handwriting', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Sacramento', category: 'handwriting', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Allura', category: 'handwriting', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    { family: 'Cookie', category: 'handwriting', variants: [{ weight: '400', style: 'normal' }], subsets: ['latin'] },
    
    // Monospace Fonts
    { family: 'Fira Code', category: 'monospace', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Source Code Pro', category: 'monospace', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'JetBrains Mono', category: 'monospace', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Inconsolata', category: 'monospace', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Space Mono', category: 'monospace', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Roboto Mono', category: 'monospace', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'IBM Plex Mono', category: 'monospace', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Courier Prime', category: 'monospace', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Anonymous Pro', category: 'monospace', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] },
    { family: 'Overpass Mono', category: 'monospace', variants: [{ weight: '400', style: 'normal' }, { weight: '700', style: 'normal' }], subsets: ['latin'] }
  ]

  // Popular fonts that should be prioritized
  private popularFonts = [
    'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins',
    'Source Sans Pro', 'Oswald', 'Raleway', 'PT Sans', 'Merriweather',
    'Playfair Display', 'Nunito', 'Ubuntu', 'Cabin'
  ]

  // System fonts that don't need loading
  private systemFonts: FontFamily[] = [
    {
      family: 'Arial',
      category: 'sans-serif',
      variants: [
        { weight: 'normal', style: 'normal' },
        { weight: 'bold', style: 'normal' },
        { weight: 'normal', style: 'italic' },
        { weight: 'bold', style: 'italic' }
      ],
      subsets: ['latin'],
      loaded: true
    },
    {
      family: 'Helvetica',
      category: 'sans-serif',
      variants: [
        { weight: 'normal', style: 'normal' },
        { weight: 'bold', style: 'normal' },
        { weight: 'normal', style: 'italic' },
        { weight: 'bold', style: 'italic' }
      ],
      subsets: ['latin'],
      loaded: true
    },
    {
      family: 'Times New Roman',
      category: 'serif',
      variants: [
        { weight: 'normal', style: 'normal' },
        { weight: 'bold', style: 'normal' },
        { weight: 'normal', style: 'italic' },
        { weight: 'bold', style: 'italic' }
      ],
      subsets: ['latin'],
      loaded: true
    },
    {
      family: 'Georgia',
      category: 'serif',
      variants: [
        { weight: 'normal', style: 'normal' },
        { weight: 'bold', style: 'normal' },
        { weight: 'normal', style: 'italic' },
        { weight: 'bold', style: 'italic' }
      ],
      subsets: ['latin'],
      loaded: true
    }
  ]

  private constructor() {
    this.initializeSystemFonts()
  }

  static getInstance(): FontManagerService {
    if (!FontManagerService.instance) {
      FontManagerService.instance = new FontManagerService()
    }
    return FontManagerService.instance
  }

  /**
   * Initialize font manager with configuration
   */
  async initialize(config?: { googleFontsApiKey?: string }) {
    if (config?.googleFontsApiKey) {
      this.googleFontsApiKey = config.googleFontsApiKey
    }
    
    // Load popular fonts in the background
    this.preloadPopularFonts()
  }

  /**
   * Get available font families with loading status
   */
  getAvailableFonts(): FontFamily[] {
    const fonts = [...this.systemFonts]
    
    // Add Google Fonts database
    for (const font of this.googleFontsDatabase) {
      // Check if font has loading status from cache
      const cachedFont = this.fontCache.get(font.family)
      if (cachedFont) {
        fonts.push(cachedFont)
      } else {
        // Add font with default loading status
        const fontWithStatus = { ...font, loaded: false, loading: false, error: false }
        this.fontCache.set(font.family, fontWithStatus)
        fonts.push(fontWithStatus)
      }
    }
    
    // Sort by popularity and loaded status
    return fonts.sort((a, b) => {
      // Loaded fonts first
      if (a.loaded && !b.loaded) return -1
      if (!a.loaded && b.loaded) return 1
      
      // Then by popularity
      const aPopularity = a.popularity || 999
      const bPopularity = b.popularity || 999
      
      if (aPopularity !== bPopularity) {
        return aPopularity - bPopularity
      }
      
      // Finally alphabetical
      return a.family.localeCompare(b.family)
    })
  }

  /**
   * Load a specific font family
   */
  async loadFont(family: string, options: FontLoadOptions = {}): Promise<void> {
    // Skip if system font
    if (this.isSystemFont(family)) {
      return Promise.resolve()
    }

    // Validate font name
    if (!family || typeof family !== 'string' || family.trim().length === 0) {
      return Promise.reject(new Error(`Invalid font family name: ${family}`))
    }

    const normalizedFamily = family.trim()

    // Check if already loaded
    if (this.loadedFonts.has(normalizedFamily)) {
      return Promise.resolve()
    }

    // Check if currently loading
    const loadingPromise = this.loadingFonts.get(normalizedFamily)
    if (loadingPromise) {
      return loadingPromise
    }

    // Validate font exists in our database
    const fontExists = this.googleFontsDatabase.some(f => f.family === normalizedFamily)
    if (!fontExists) {
      console.warn(`Font ${normalizedFamily} not found in database, skipping load to prevent errors`)
      return Promise.reject(new Error(`Font ${normalizedFamily} not available`))
    }

    // Start loading with retry
    const promise = this.loadGoogleFontWithRetry(normalizedFamily, options)
    this.loadingFonts.set(normalizedFamily, promise)

    try {
      await promise
      this.loadedFonts.add(normalizedFamily)
      this.updateFontStatus(normalizedFamily, { loaded: true, loading: false, error: false })
    } catch (error) {
      console.error(`Failed to load font ${normalizedFamily} after retries:`, error)
      this.updateFontStatus(normalizedFamily, { loaded: false, loading: false, error: true })
      throw error
    } finally {
      this.loadingFonts.delete(normalizedFamily)
    }
  }

  private async loadGoogleFontWithRetry(family: string, options: FontLoadOptions, maxRetries = 2): Promise<void> {
    let lastError: Error
    
    for (let attempt = 0; attempt <= maxRetries; attempt++) {
      try {
        await this.loadGoogleFont(family, options)
        return // Success
      } catch (error) {
        lastError = error as Error
        console.warn(`Font load attempt ${attempt + 1} failed for ${family}:`, error)
        
        if (attempt < maxRetries) {
          // Wait before retry
          await new Promise(resolve => setTimeout(resolve, 1000 * (attempt + 1)))
        }
      }
    }
    
    throw lastError!
  }

  /**
   * Check if a font is loaded
   */
  isFontLoaded(family: string): boolean {
    return this.isSystemFont(family) || this.loadedFonts.has(family)
  }

  /**
   * Check if a font is currently loading
   */
  isFontLoading(family: string): boolean {
    return this.loadingFonts.has(family)
  }

  /**
   * Get font with fallbacks
   */
  getFontWithFallbacks(family: string): string {
    const font = this.getFontFamily(family)
    if (!font) return family

    const fallbacks = this.getFallbacksForCategory(font.category)
    return `"${family}", ${fallbacks}`
  }

  /**
   * Search fonts by name or category
   */
  searchFonts(query: string, limit = 20): FontFamily[] {
    const normalizedQuery = query.toLowerCase()
    const allFonts = this.getAvailableFonts()
    
    return allFonts
      .filter(font => 
        font.family.toLowerCase().includes(normalizedQuery) ||
        font.category.toLowerCase().includes(normalizedQuery)
      )
      .slice(0, limit)
  }

  /**
   * Get font families by category
   */
  getFontsByCategory(category: FontFamily['category']): FontFamily[] {
    return this.getAvailableFonts().filter(font => font.category === category)
  }

  private initializeSystemFonts() {
    for (const font of this.systemFonts) {
      this.fontCache.set(font.family, font)
    }
  }

  private isSystemFont(family: string): boolean {
    return this.systemFonts.some(font => font.family === family)
  }

  private getFontFamily(family: string): FontFamily | undefined {
    return this.fontCache.get(family)
  }

  private updateFontStatus(family: string, status: Partial<FontFamily>) {
    const font = this.fontCache.get(family)
    if (font) {
      Object.assign(font, status)
    }
  }

  private async loadGoogleFont(family: string, options: FontLoadOptions): Promise<void> {
    const { weights = ['400'], styles = ['normal'], display = 'swap' } = options
    
    // Check if already loaded first
    if (this.loadedFonts.has(family)) {
      return Promise.resolve()
    }

    // Build Google Fonts URL
    const fontUrl = this.buildGoogleFontUrl(family, weights, styles, display)
    console.log(`Loading font: ${family} from URL: ${fontUrl}`)
    
    // Create and load font stylesheet
    return new Promise((resolve, reject) => {
      const link = document.createElement('link')
      link.href = fontUrl
      link.rel = 'stylesheet'
      link.type = 'text/css'
      
      // Add timeout for font loading
      const timeout = setTimeout(() => {
        if (document.head.contains(link)) {
          document.head.removeChild(link)
        }
        reject(new Error(`Font loading timeout for ${family} after 10 seconds`))
      }, 10000)
      
      link.onload = () => {
        clearTimeout(timeout)
        console.log(`Successfully loaded font stylesheet: ${family}`)
        // Verify font is actually loaded - but don't fail if verification fails
        this.verifyFontLoaded(family)
          .then(() => {
            console.log(`Font verification successful: ${family}`)
            resolve()
          })
          .catch((error) => {
            console.warn(`Font verification failed for ${family}:`, error)
            // Still resolve if stylesheet loaded, verification might be unreliable
            resolve()
          })
      }
      
      link.onerror = (event) => {
        clearTimeout(timeout)
        if (document.head.contains(link)) {
          document.head.removeChild(link)
        }
        console.error(`Failed to load font stylesheet for ${family}. URL: ${fontUrl}`, event)
        reject(new Error(`Failed to load font stylesheet for ${family}. URL may be invalid or network error occurred.`))
      }
      
      document.head.appendChild(link)
    })
  }

  private buildGoogleFontUrl(family: string, weights: string[], styles: string[], display: string): string {
    // Google Fonts CSS2 API format: https://fonts.googleapis.com/css2?family=Font+Name:wght@400;700&display=swap
    const familyName = family.replace(/\s+/g, '+') // Replace spaces with +
    
    // Check if italic is requested
    const hasItalic = styles.includes('italic')
    
    if (hasItalic) {
      // For fonts with italic, use ital,wght format
      // Format: family=Font+Name:ital,wght@0,400;0,700;1,400;1,700
      const weightCombinations: string[] = []
      
      // Add normal weights (italic = 0)
      weights.forEach(weight => {
        weightCombinations.push(`0,${weight}`)
      })
      
      // Add italic weights (italic = 1)
      weights.forEach(weight => {
        weightCombinations.push(`1,${weight}`)
      })
      
      const familyQuery = `${familyName}:ital,wght@${weightCombinations.join(';')}`
      return `${this.googleFontsBaseUrl}?family=${familyQuery}&display=${display}`
    } else {
      // For fonts without italic, use simple wght format
      // Format: family=Font+Name:wght@400;700
      const weightParams = weights.join(';')
      const familyQuery = `${familyName}:wght@${weightParams}`
      return `${this.googleFontsBaseUrl}?family=${familyQuery}&display=${display}`
    }
  }

  private async verifyFontLoaded(family: string): Promise<void> {
    // Use FontFace API to verify font is loaded
    if ('fonts' in document) {
      try {
        await (document as any).fonts.load(`16px "${family}"`)
        return Promise.resolve()
      } catch (error) {
        return Promise.reject(error)
      }
    }
    
    // Fallback verification method
    return this.fallbackFontVerification(family)
  }

  private async fallbackFontVerification(family: string): Promise<void> {
    return new Promise((resolve, reject) => {
      const testText = 'BESbswy'
      const fallbackFont = 'monospace'
      
      // Create test elements
      const container = document.createElement('div')
      container.style.position = 'absolute'
      container.style.visibility = 'hidden'
      container.style.left = '-9999px'
      container.style.top = '-9999px'
      
      const fallbackSpan = document.createElement('span')
      fallbackSpan.style.fontFamily = fallbackFont
      fallbackSpan.style.fontSize = '100px'
      fallbackSpan.textContent = testText
      
      const testSpan = document.createElement('span')
      testSpan.style.fontFamily = `"${family}", ${fallbackFont}`
      testSpan.style.fontSize = '100px'
      testSpan.textContent = testText
      
      container.appendChild(fallbackSpan)
      container.appendChild(testSpan)
      document.body.appendChild(container)
      
      const fallbackWidth = fallbackSpan.offsetWidth
      
      let attempts = 0
      const maxAttempts = 50
      
      const checkFont = () => {
        const testWidth = testSpan.offsetWidth
        
        if (testWidth !== fallbackWidth) {
          document.body.removeChild(container)
          resolve()
          return
        }
        
        attempts++
        if (attempts >= maxAttempts) {
          document.body.removeChild(container)
          reject(new Error('Font verification timeout'))
          return
        }
        
        setTimeout(checkFont, 100)
      }
      
      checkFont()
    })
  }

  private getFallbacksForCategory(category: FontFamily['category']): string {
    switch (category) {
      case 'serif':
        return 'Georgia, "Times New Roman", serif'
      case 'sans-serif':
        return 'Arial, Helvetica, sans-serif'
      case 'monospace':
        return '"Courier New", Courier, monospace'
      case 'handwriting':
        return 'cursive'
      case 'display':
        return 'Impact, Arial, sans-serif'
      default:
        return 'Arial, sans-serif'
    }
  }

  private async preloadPopularFonts() {
    // Load popular fonts in the background with minimal weights
    const loadPromises = this.popularFonts.slice(0, 5).map(family =>
      this.loadFont(family, { weights: ['400'], styles: ['normal'] })
        .catch(error => console.warn(`Failed to preload font ${family}:`, error))
    )
    
    await Promise.allSettled(loadPromises)
  }
}

export const FontManager = FontManagerService.getInstance()
