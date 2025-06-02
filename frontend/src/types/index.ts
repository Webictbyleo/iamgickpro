export interface User {
  id: string
  email: string
  firstName: string
  lastName: string
  jobTitle?: string
  company?: string
  website?: string
  portfolio?: string
  bio?: string
  avatar?: string
  role: string
  socialLinks?: {
    twitter?: string
    linkedin?: string
    dribbble?: string
    behance?: string
  }
  createdAt: Date
  updatedAt?: Date
  lastLoginAt?: Date
}

export interface Design {
  id: string
  title: string
  description?: string
  data: DesignData
  thumbnail?: string
  dimensions: {
    width: number
    height: number
  }
  userId: string
  isPublic: boolean
  createdAt: string
  updatedAt: string
}

export interface DesignData {
  version: string
  layers: Layer[]
  canvas: CanvasSettings
}

export interface CanvasSettings {
  width: number
  height: number
  backgroundColor: string
  backgroundImage?: string
}

export interface Layer {
  id: string
  type: LayerType
  name: string
  visible: boolean
  locked: boolean
  opacity: number
  x: number
  y: number
  width: number
  height: number
  rotation: number
  scaleX: number
  scaleY: number
  zIndex: number
  properties: LayerProperties
}

export type LayerType = 'text' | 'image' | 'shape' | 'group'

export interface LayerProperties {
  // Text layer properties
  text?: string
  fontSize?: number
  fontFamily?: string
  fontWeight?: string | number
  fontStyle?: string
  textAlign?: string
  color?: string
  lineHeight?: number
  letterSpacing?: number
  
  // Image layer properties
  src?: string
  filters?: ImageFilter[]
  
  // Shape layer properties
  fill?: string
  stroke?: string
  strokeWidth?: number
  cornerRadius?: number
  
  // Group layer properties
  children?: Layer[]
}

export interface ImageFilter {
  type: 'blur' | 'brightness' | 'contrast' | 'saturate' | 'grayscale' | 'sepia'
  value: number
}

export interface Template {
  id: string
  title: string
  category: string
  description?: string
  tags: string[]
  thumbnail: string
  dimensions: {
    width: number
    height: number
  }
  data?: DesignData
  isPremium: boolean
  createdAt: string
  updatedAt: string
}

export interface MediaItem {
  id: string
  name: string
  type: 'image' | 'video' | 'audio'
  url: string
  thumbnail?: string
  size: number
  dimensions?: {
    width: number
    height: number
  }
  tags: string[]
  isPremium: boolean
  createdAt: Date
}

export interface ExportJob {
  id: string
  designId: string
  designTitle: string
  designThumbnail?: string
  format: 'png' | 'jpg' | 'jpeg' | 'pdf' | 'svg' | 'mp4' | 'gif'
  status: 'pending' | 'processing' | 'completed' | 'failed'
  progress?: number
  options: ExportOptions
  downloadUrl?: string
  fileSize?: number
  errorMessage?: string
  createdAt: string
  updatedAt: string
  completedAt?: string
}

export interface ExportOptions {
  quality?: number
  width?: number
  height?: number
  scale?: number
  transparent?: boolean
  duration?: number // for video/gif exports
  fps?: number // for video/gif exports
}


