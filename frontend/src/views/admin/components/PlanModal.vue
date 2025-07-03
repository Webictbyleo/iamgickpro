<template>
  <!-- Create/Edit Plan Modal -->
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog as="div" @close="handleClose" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all">
              <!-- Modal Header -->
              <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50/50">
                <div class="flex items-center space-x-3">
                  <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                    <CurrencyDollarIcon class="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <DialogTitle as="h3" class="text-xl font-semibold text-gray-900">
                      {{ editingPlan ? 'Edit Plan' : 'Create New Plan' }}
                    </DialogTitle>
                    <p class="text-sm text-gray-600">
                      {{ editingPlan ? 'Modify your subscription plan settings' : 'Set up a new subscription plan for your users' }}
                    </p>
                  </div>
                </div>
                <button
                  @click="handleClose"
                  class="flex-shrink-0 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-all duration-200"
                >
                  <XMarkIcon class="w-5 h-5" />
                </button>
              </div>

              <!-- Modal Body -->
              <div class="max-h-[calc(90vh-120px)] overflow-y-auto">
                <form @submit.prevent="handleSubmit" class="p-6 space-y-8">
                  <!-- Basic Information Section -->
                  <div class="space-y-6">
                    <div class="flex items-center space-x-3 mb-4">
                      <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <DocumentTextIcon class="w-4 h-4 text-blue-600" />
                      </div>
                      <h4 class="text-lg font-semibold text-gray-900">Basic Information</h4>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                          Plan Name <span class="text-red-500">*</span>
                        </label>
                        <input
                          v-model="form.name"
                          type="text"
                          required
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                          placeholder="e.g., Professional, Enterprise"
                        />
                        <p class="mt-1 text-xs text-gray-500">A clear, descriptive name for your plan</p>
                      </div>
                      
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                          Monthly Price <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                          <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span class="text-gray-500 text-lg font-medium">$</span>
                          </div>
                          <input
                            v-model.number="form.price"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                            placeholder="0.00"
                          />
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Monthly subscription price in USD</p>
                      </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Billing Interval</label>
                        <select
                          v-model="form.billingInterval"
                          required
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                        >
                          <option value="month">Monthly Billing</option>
                          <option value="year">Yearly Billing</option>
                        </select>
                      </div>
                      
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Trial Period</label>
                        <div class="relative">
                          <input
                            v-model.number="form.trialPeriodDays"
                            type="number"
                            min="0"
                            max="365"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                            placeholder="0"
                          />
                          <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <span class="text-gray-500 text-sm">days</span>
                          </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Free trial period before billing starts</p>
                      </div>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                      <textarea
                        v-model="form.description"
                        rows="3"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 resize-none"
                        placeholder="Brief description of what this plan includes..."
                      ></textarea>
                      <p class="mt-1 text-xs text-gray-500">Help users understand what they get with this plan</p>
                    </div>
                  </div>

                  <!-- Plan Limits Section -->
                  <div class="space-y-6">
                    <div class="flex items-center space-x-3 mb-4">
                      <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                        <AdjustmentsHorizontalIcon class="w-4 h-4 text-purple-600" />
                      </div>
                      <h4 class="text-lg font-semibold text-gray-900">Usage Limits</h4>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                      <!-- Max Projects -->
                      <div class="bg-gray-50 rounded-xl p-4">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                          <div class="flex items-center space-x-2">
                            <FolderIcon class="w-4 h-4 text-gray-500" />
                            <span>Max Projects</span>
                          </div>
                        </label>
                        <div class="space-y-3">
                          <div class="flex items-center">
                            <input
                              :checked="form.limits.projects === -1"
                              @change="toggleUnlimited('projects', $event)"
                              type="checkbox"
                              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <label class="ml-2 block text-sm text-gray-700 font-medium">Unlimited projects</label>
                          </div>
                          <input
                            v-if="form.limits.projects !== -1"
                            v-model.number="form.limits.projects"
                            type="number"
                            min="1"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Enter number of projects"
                          />
                        </div>
                      </div>

                      <!-- Storage -->
                      <div class="bg-gray-50 rounded-xl p-4">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                          <div class="flex items-center space-x-2">
                            <CloudArrowUpIcon class="w-4 h-4 text-gray-500" />
                            <span>Storage Limit</span>
                          </div>
                        </label>
                        <div class="space-y-3">
                          <div class="flex items-center">
                            <input
                              :checked="form.limits.storage === -1"
                              @change="toggleUnlimited('storage', $event)"
                              type="checkbox"
                              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <label class="ml-2 block text-sm text-gray-700 font-medium">Unlimited storage</label>
                          </div>
                          <div v-if="form.limits.storage !== -1" class="relative">
                            <input
                              v-model.number="storageInGB"
                              type="number"
                              min="1"
                              step="0.1"
                              class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Enter storage amount"
                            />
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                              <span class="text-gray-500 text-sm">GB</span>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Monthly Exports -->
                      <div class="bg-gray-50 rounded-xl p-4">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                          <div class="flex items-center space-x-2">
                            <ArrowDownTrayIcon class="w-4 h-4 text-gray-500" />
                            <span>Monthly Exports</span>
                          </div>
                        </label>
                        <div class="space-y-3">
                          <div class="flex items-center">
                            <input
                              :checked="form.limits.exports === -1"
                              @change="toggleUnlimited('exports', $event)"
                              type="checkbox"
                              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <label class="ml-2 block text-sm text-gray-700 font-medium">Unlimited exports</label>
                          </div>
                          <input
                            v-if="form.limits.exports !== -1"
                            v-model.number="form.limits.exports"
                            type="number"
                            min="1"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Enter monthly export limit"
                          />
                        </div>
                      </div>

                      <!-- Templates Access -->
                      <div class="bg-gray-50 rounded-xl p-4">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                          <div class="flex items-center space-x-2">
                            <DocumentDuplicateIcon class="w-4 h-4 text-gray-500" />
                            <span>Template Access</span>
                          </div>
                        </label>
                        <div class="space-y-3">
                          <div class="flex items-center">
                            <input
                              :checked="form.limits.templates === -1"
                              @change="toggleUnlimited('templates', $event)"
                              type="checkbox"
                              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <label class="ml-2 block text-sm text-gray-700 font-medium">All templates</label>
                          </div>
                          <input
                            v-if="form.limits.templates !== -1"
                            v-model.number="form.limits.templates"
                            type="number"
                            min="1"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Enter template limit"
                          />
                        </div>
                      </div>

                      <!-- Collaborators -->
                      <div class="bg-gray-50 rounded-xl p-4">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                          <div class="flex items-center space-x-2">
                            <UsersIcon class="w-4 h-4 text-gray-500" />
                            <span>Collaborators</span>
                          </div>
                        </label>
                        <div class="space-y-3">
                          <div class="flex items-center">
                            <input
                              :checked="form.limits.collaborators === -1"
                              @change="toggleUnlimited('collaborators', $event)"
                              type="checkbox"
                              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <label class="ml-2 block text-sm text-gray-700 font-medium">Unlimited collaborators</label>
                          </div>
                          <input
                            v-if="form.limits.collaborators !== -1"
                            v-model.number="form.limits.collaborators"
                            type="number"
                            min="1"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Enter collaborator limit"
                          />
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Features Section -->
                  <div class="space-y-6">
                    <div class="flex items-center space-x-3 mb-4">
                      <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <SparklesIcon class="w-4 h-4 text-green-600" />
                      </div>
                      <h4 class="text-lg font-semibold text-gray-900">Plan Features</h4>
                    </div>
                    
                    <div class="bg-gray-50 rounded-xl p-6">
                      <label class="block text-sm font-medium text-gray-700 mb-4">Select Features by Category</label>
                      
                      <!-- Feature Categories -->
                      <div class="space-y-6">
                        <div v-for="(categoryFeatures, category) in PLAN_FEATURES_BY_CATEGORY" :key="category" class="space-y-3">
                          <h5 class="text-sm font-semibold text-gray-900 flex items-center space-x-2">
                            <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                            <span>{{ PLAN_FEATURE_CATEGORY_NAMES[category as PlanFeatureCategory] }}</span>
                          </h5>
                          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 ml-4">
                            <div v-for="feature in categoryFeatures" :key="feature.key" class="flex items-start space-x-3">
                              <div class="flex items-center h-5">
                                <input
                                  :id="`feature-${feature.key}`"
                                  v-model="selectedFeatures[feature.key]"
                                  type="checkbox"
                                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                              </div>
                              <div class="flex-1">
                                <label :for="`feature-${feature.key}`" class="block text-sm font-medium text-gray-900 cursor-pointer flex items-center space-x-2">
                                  <span>{{ feature.name }}</span>
                                  <span v-if="feature.premium" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white text-xs px-2 py-0.5 rounded-full font-medium">
                                    PRO
                                  </span>
                                </label>
                                <p class="text-xs text-gray-500 mt-1">{{ feature.description }}</p>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Custom Features Section -->
                      <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex items-center justify-between mb-3">
                          <label class="block text-sm font-medium text-gray-700">Custom Features</label>
                          <button
                            type="button"
                            @click="addCustomFeature"
                            class="text-xs text-blue-600 hover:text-blue-700 transition-colors font-medium"
                          >
                            + Add Custom
                          </button>
                        </div>
                        <div v-if="customFeatures.length > 0" class="space-y-3">
                          <div v-for="(feature, index) in customFeatures" :key="`custom-${index}`" class="flex items-center space-x-3">
                            <div class="flex-1">
                              <input
                                v-model="customFeatures[index]"
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                placeholder="Enter custom feature..."
                              />
                            </div>
                            <button
                              type="button"
                              @click="removeCustomFeature(index)"
                              class="flex-shrink-0 p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-all duration-200"
                            >
                              <XMarkIcon class="w-4 h-4" />
                            </button>
                          </div>
                        </div>
                        <p v-else class="text-xs text-gray-400 italic">No custom features added</p>
                      </div>
                      
                      <p class="mt-4 text-xs text-gray-500">Features are organized by category. Premium features are marked with a PRO badge. Custom features can be added for unique offerings.</p>
                    </div>
                  </div>

                  <!-- Plan Status -->
                  <div class="space-y-4">
                    <div class="flex items-center space-x-3 mb-4">
                      <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                        <Cog6ToothIcon class="w-4 h-4 text-amber-600" />
                      </div>
                      <h4 class="text-lg font-semibold text-gray-900">Plan Settings</h4>
                    </div>
                    
                    <div class="bg-gray-50 rounded-xl p-4">
                      <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                          <div class="flex-shrink-0">
                            <div :class="form.isActive ? 'bg-green-100' : 'bg-gray-100'" class="w-10 h-10 rounded-xl flex items-center justify-center">
                              <CheckCircleIcon v-if="form.isActive" class="w-5 h-5 text-green-600" />
                              <XMarkIcon v-else class="w-5 h-5 text-gray-400" />
                            </div>
                          </div>
                          <div>
                            <label for="planActive" class="block text-sm font-medium text-gray-900">
                              Plan Status
                            </label>
                            <p class="text-xs text-gray-500">
                              {{ form.isActive ? 'Plan is active and available for subscription' : 'Plan is inactive and hidden from users' }}
                            </p>
                          </div>
                        </div>
                        <div class="flex items-center">
                          <input
                            v-model="form.isActive"
                            type="checkbox"
                            id="planActive"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </form>
              </div>

              <!-- Modal Footer -->
              <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                <div class="flex items-center space-x-2 text-sm text-gray-500">
                  <InformationCircleIcon class="w-4 h-4" />
                  <span>{{ editingPlan ? 'Changes will be applied immediately' : 'New plan will be available once created' }}</span>
                </div>
                <div class="flex items-center space-x-3">
                  <button
                    type="button"
                    @click="handleClose"
                    class="px-6 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 font-medium"
                  >
                    Cancel
                  </button>
                  <button
                    @click="handleSubmit"
                    :disabled="submitting || !isFormValid"
                    class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl hover:from-blue-700 hover:to-purple-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed font-medium flex items-center space-x-2"
                  >
                    <div v-if="submitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    <span>{{ submitting ? 'Saving...' : (editingPlan ? 'Update Plan' : 'Create Plan') }}</span>
                  </button>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { 
  Dialog, 
  DialogPanel, 
  DialogTitle, 
  TransitionChild, 
  TransitionRoot 
} from '@headlessui/vue'
import {
  PLAN_FEATURE_DEFINITIONS,
  PLAN_FEATURES_BY_CATEGORY,
  PLAN_FEATURE_CATEGORY_NAMES,
  DEFAULT_PLAN_FEATURES,
  DEFAULT_PLAN_LIMITS,
  type PlanFeatures,
  type PlanLimits,
  type PlanFeatureKey,
  type PlanFeatureCategory
} from '@/types/plans'
import type { AdminSubscriptionPlan } from '@/types'

// Heroicons
import { 
  XMarkIcon,
  CurrencyDollarIcon,
  DocumentTextIcon,
  AdjustmentsHorizontalIcon,
  FolderIcon,
  CloudArrowUpIcon,
  ArrowDownTrayIcon,
  UsersIcon,
  SparklesIcon,
  Cog6ToothIcon,
  CheckCircleIcon,
  PlusIcon,
  InformationCircleIcon,
  DocumentDuplicateIcon
} from '@heroicons/vue/24/outline'

// Types
interface PlanForm {
  name: string
  description: string
  price: number
  billingInterval: string
  trialPeriodDays: number
  features: string[]
  limits: PlanLimits
  isActive: boolean
}

// Props
interface Props {
  isOpen: boolean
  editingPlan?: AdminSubscriptionPlan | null
  submitting?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isOpen: false,
  editingPlan: null,
  submitting: false
})

// Emits
interface Emits {
  (e: 'close'): void
  (e: 'submit', data: any): void
}

const emit = defineEmits<Emits>()

// Form state
const form = reactive<PlanForm>({
  name: '',
  description: '',
  price: 0,
  billingInterval: 'month',
  trialPeriodDays: 0,
  features: [''],
  limits: { ...DEFAULT_PLAN_LIMITS },
  isActive: true,
})

// Features management - using the actual plan features from types
const selectedFeatures = ref<PlanFeatures>({ ...DEFAULT_PLAN_FEATURES })
const customFeatures = ref<string[]>([])

// Computed
const isFormValid = computed(() => {
  return form.name.trim() && form.price >= 0
})

// Storage conversion helpers for form display (GB) vs backend (bytes)
const storageInGB = computed({
  get: () => {
    if (form.limits.storage === -1 || form.limits.storage === null) {
      return form.limits.storage
    }
    // Convert bytes to GB for display
    return bytesToGB(form.limits.storage)
  },
  set: (value: number | null) => {
    if (value === -1 || value === null) {
      form.limits.storage = value
    } else {
      // Convert GB to bytes for backend
      form.limits.storage = gbToBytes(value)
    }
  }
})

// Watch for prop changes to update form
watch(() => props.editingPlan, (plan) => {
  if (plan) {
    // Reset features to defaults
    selectedFeatures.value = { ...DEFAULT_PLAN_FEATURES }
    customFeatures.value = []
    
    // Process existing features
    if (plan.features) {
      // Update selected features based on plan
      Object.entries(plan.features).forEach(([featureKey, isEnabled]) => {
        if (isEnabled && featureKey in selectedFeatures.value) {
          selectedFeatures.value[featureKey as PlanFeatureKey] = true
        } else if (isEnabled) {
          // It's a custom feature - add to custom features list
          const customFeatureName = featureKey.replace(/_/g, ' ')
            .replace(/\b\w/g, l => l.toUpperCase())
          customFeatures.value.push(customFeatureName)
        }
      })
    }
    
    Object.assign(form, {
      name: plan.name,
      description: plan.description || '',
      price: parseFloat(plan.monthly_price) || 0,
      billingInterval: 'month',
      trialPeriodDays: 0,
      features: [''], // Keep for compatibility, but we use selectedFeatures now
      limits: plan.limits || { ...DEFAULT_PLAN_LIMITS },
      isActive: plan.is_active,
    })
  }
}, { immediate: true })

// Watch for modal close to reset form
watch(() => props.isOpen, (isOpen) => {
  if (!isOpen && !props.editingPlan) {
    resetForm()
  }
})

// Methods
const resetForm = () => {
  Object.assign(form, {
    name: '',
    description: '',
    price: 0,
    billingInterval: 'month',
    trialPeriodDays: 0,
    features: [''],
    limits: { ...DEFAULT_PLAN_LIMITS },
    isActive: true,
  })
  
  // Reset features
  selectedFeatures.value = { ...DEFAULT_PLAN_FEATURES }
  customFeatures.value = []
}

const handleClose = () => {
  emit('close')
}

const handleSubmit = () => {
  if (!isFormValid.value) return

  // Combine selected predefined features and custom features
  const allFeatures: string[] = []
  
  // Add selected predefined features
  Object.entries(selectedFeatures.value).forEach(([key, isSelected]) => {
    if (isSelected) {
      const feature = PLAN_FEATURE_DEFINITIONS.find(f => f.key === key)
      if (feature) {
        allFeatures.push(feature.name)
      }
    }
  })
  
  // Add custom features
  customFeatures.value.forEach(feature => {
    if (feature.trim()) {
      allFeatures.push(feature.trim())
    }
  })
  
  // Prepare features as object for backend (using the actual feature keys)
  const featuresObject: PlanFeatures = { ...DEFAULT_PLAN_FEATURES }
  
  // Set selected features
  Object.entries(selectedFeatures.value).forEach(([key, isSelected]) => {
    if (key in featuresObject) {
      featuresObject[key as PlanFeatureKey] = isSelected
    }
  })
  
  // Add custom features as additional properties
  const customFeaturesObject: { [key: string]: boolean } = {}
  customFeatures.value.forEach(feature => {
    if (feature.trim()) {
      const key = feature.toLowerCase().replace(/\s+/g, '_')
      customFeaturesObject[key] = true
    }
  })
  
  const planData = {
    code: form.name.toLowerCase().replace(/\s+/g, '_'),
    name: form.name,
    description: form.description,
    monthly_price: form.price,
    yearly_price: form.price * 12 * 0.8, // 20% discount for yearly
    currency: 'USD',
    is_active: form.isActive,
    limits: Object.fromEntries(
      Object.entries(form.limits).filter(([_, value]) => value !== null)
    ),
    features: { ...featuresObject, ...customFeaturesObject }
  }

  emit('submit', planData)
}

const addCustomFeature = () => {
  customFeatures.value.push('')
}

const removeCustomFeature = (index: number) => {
  customFeatures.value.splice(index, 1)
}

const toggleUnlimited = (field: keyof PlanLimits, event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.checked) {
    // Set to -1 for unlimited
    form.limits[field] = -1
  } else {
    // Set to null so the input field shows
    form.limits[field] = null
  }
}

// Storage conversion utilities
const bytesToGB = (bytes: number): number => {
  const gb = bytes / (1024 * 1024 * 1024)
  // Round to 2 decimal places to avoid floating point issues
  return Math.round(gb * 100) / 100
}

const gbToBytes = (gb: number): number => {
  return Math.round(gb * 1024 * 1024 * 1024)
}
</script>
