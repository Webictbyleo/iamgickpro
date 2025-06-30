/**
 * Plan Feature Types
 * Based on backend/config/plans.yaml
 */

// Individual feature keys as defined in plans.yaml
export type PlanFeatureKey = 
  | 'basic_templates'
  | 'basic_export'
  | 'cloud_storage'
  | 'premium_templates'
  | 'advanced_export'
  | 'collaboration'
  | 'api_access'
  | 'priority_support'
  | 'custom_branding'
  | 'team_management'

// Plan feature definitions with metadata
export interface PlanFeatureDefinition {
  key: PlanFeatureKey
  name: string
  description: string
  category: PlanFeatureCategory
  icon?: string
  premium?: boolean // Whether this is a premium feature
}

export type PlanFeatureCategory = 
  | 'core'
  | 'templates'
  | 'export'
  | 'collaboration'
  | 'integrations'
  | 'support'
  | 'branding'

// Plan features object as stored in backend
export type PlanFeatures = Record<PlanFeatureKey, boolean>

// Plan limits as defined in plans.yaml
export interface PlanLimits {
  projects: number | null // -1 for unlimited, null for not set
  storage: number | null // bytes, -1 for unlimited
  exports: number | null // monthly exports, -1 for unlimited
  collaborators: number | null // team members, -1 for unlimited
  templates: number | null // template access, -1 for unlimited
}

// Complete plan structure
export interface Plan {
  id?: string
  code: string
  name: string
  description: string
  pricing: {
    monthly: number
    yearly: number
    currency: string
  }
  limits: PlanLimits
  features: PlanFeatures
  is_active: boolean
  created_at?: string
  updated_at?: string
}

// Plan form structure for the modal
export interface PlanFormData {
  name: string
  description: string
  monthly_price: number
  yearly_price: number
  currency: string
  limits: PlanLimits
  features: PlanFeatures
  is_active: boolean
}

// Predefined plan feature definitions
export const PLAN_FEATURE_DEFINITIONS: PlanFeatureDefinition[] = [
  // Core Features
  {
    key: 'basic_templates',
    name: 'Basic Templates',
    description: 'Access to free design templates',
    category: 'templates',
    icon: 'DocumentIcon'
  },
  {
    key: 'basic_export',
    name: 'Basic Export',
    description: 'Export designs in PNG and JPG formats',
    category: 'export',
    icon: 'ArrowDownTrayIcon'
  },
  {
    key: 'cloud_storage',
    name: 'Cloud Storage',
    description: 'Store your designs securely in the cloud',
    category: 'core',
    icon: 'CloudIcon'
  },
  
  // Premium Templates
  {
    key: 'premium_templates',
    name: 'Premium Templates',
    description: 'Access to premium design templates and assets',
    category: 'templates',
    icon: 'SparklesIcon',
    premium: true
  },
  
  // Advanced Export
  {
    key: 'advanced_export',
    name: 'Advanced Export',
    description: 'Export in multiple formats: PDF, SVG, MP4, GIF',
    category: 'export',
    icon: 'DocumentArrowDownIcon',
    premium: true
  },
  
  // Collaboration
  {
    key: 'collaboration',
    name: 'Team Collaboration',
    description: 'Real-time collaboration with team members',
    category: 'collaboration',
    icon: 'UsersIcon',
    premium: true
  },
  {
    key: 'team_management',
    name: 'Team Management',
    description: 'Advanced team management and permissions',
    category: 'collaboration',
    icon: 'UserGroupIcon',
    premium: true
  },
  
  // Integrations
  {
    key: 'api_access',
    name: 'API Access',
    description: 'Developer API for custom integrations',
    category: 'integrations',
    icon: 'CodeBracketIcon',
    premium: true
  },
  
  // Support
  {
    key: 'priority_support',
    name: 'Priority Support',
    description: '24/7 priority customer support',
    category: 'support',
    icon: 'ChatBubbleLeftEllipsisIcon',
    premium: true
  },
  
  // Branding
  {
    key: 'custom_branding',
    name: 'Custom Branding',
    description: 'Remove watermarks and add your own branding',
    category: 'branding',
    icon: 'PaintBrushIcon',
    premium: true
  }
]

// Group features by category
export const PLAN_FEATURES_BY_CATEGORY = PLAN_FEATURE_DEFINITIONS.reduce((acc, feature) => {
  if (!acc[feature.category]) {
    acc[feature.category] = []
  }
  acc[feature.category].push(feature)
  return acc
}, {} as Record<PlanFeatureCategory, PlanFeatureDefinition[]>)

// Feature category display names
export const PLAN_FEATURE_CATEGORY_NAMES: Record<PlanFeatureCategory, string> = {
  core: 'Core Features',
  templates: 'Templates & Assets',
  export: 'Export Options',
  collaboration: 'Collaboration',
  integrations: 'Integrations',
  support: 'Support & Service',
  branding: 'Branding & Customization'
}

// Helper functions
export const getFeatureDefinition = (key: PlanFeatureKey): PlanFeatureDefinition | undefined => {
  return PLAN_FEATURE_DEFINITIONS.find(f => f.key === key)
}

export const isFeatureEnabled = (features: PlanFeatures, key: PlanFeatureKey): boolean => {
  return features[key] === true
}

export const isPremiumFeature = (key: PlanFeatureKey): boolean => {
  const definition = getFeatureDefinition(key)
  return definition?.premium === true
}

// Default plan features (for new plans)
export const DEFAULT_PLAN_FEATURES: PlanFeatures = {
  basic_templates: true,
  basic_export: true,
  cloud_storage: true,
  premium_templates: false,
  advanced_export: false,
  collaboration: false,
  api_access: false,
  priority_support: false,
  custom_branding: false,
  team_management: false
}

// Default plan limits (for new plans)
export const DEFAULT_PLAN_LIMITS: PlanLimits = {
  projects: 10,
  storage: 1073741824, // 1GB in bytes
  exports: 50,
  collaborators: 1,
  templates: 50
}
