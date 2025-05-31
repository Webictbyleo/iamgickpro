<template>
  <div class="space-y-8">
    <!-- General Overview -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-200/50">
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <CogIcon class="w-8 h-8 text-blue-600" />
        </div>
        <div class="ml-4">
          <h3 class="text-lg font-semibold text-blue-900">General Settings</h3>
          <p class="text-blue-700 mt-1">Configure editor preferences and export defaults to customize your design experience.</p>
        </div>
      </div>
    </div>

    <!-- Editor Preferences -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm">
      <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
          <PencilIcon class="w-5 h-5 mr-2 text-gray-600" />
          Editor Preferences
        </h3>
        <p class="text-sm text-gray-600 mt-1">Customize your design editor experience and workflow</p>
      </div>
      
      <div class="p-6 space-y-6">
        <!-- Grid and Guides -->
        <div>
          <label class="block text-sm font-medium text-gray-900 mb-3">Grid and Guides</label>
          <div class="space-y-3">
            <label class="flex items-center space-x-3">
              <input
                v-model="settings.editor.showGrid"
                type="checkbox"
                class="w-4 h-4 text-violet-600 border-gray-300 rounded focus:ring-violet-500/20"
              />
              <span class="text-sm text-gray-700">Show grid by default</span>
            </label>
            <label class="flex items-center space-x-3">
              <input
                v-model="settings.editor.snapToGrid"
                type="checkbox"
                class="w-4 h-4 text-violet-600 border-gray-300 rounded focus:ring-violet-500/20"
              />
              <span class="text-sm text-gray-700">Snap objects to grid</span>
            </label>
            <label class="flex items-center space-x-3">
              <input
                v-model="settings.editor.showRulers"
                type="checkbox"
                class="w-4 h-4 text-violet-600 border-gray-300 rounded focus:ring-violet-500/20"
              />
              <span class="text-sm text-gray-700">Show rulers</span>
            </label>
            <label class="flex items-center space-x-3">
              <input
                v-model="settings.editor.showGuides"
                type="checkbox"
                class="w-4 h-4 text-violet-600 border-gray-300 rounded focus:ring-violet-500/20"
              />
              <span class="text-sm text-gray-700">Show smart guides</span>
            </label>
          </div>
        </div>

        <!-- Default Units -->
        <div>
          <label for="defaultUnit" class="block text-sm font-medium text-gray-900 mb-2">
            Default Unit
          </label>
          <select
            id="defaultUnit"
            v-model="settings.editor.defaultUnit"
            class="w-full md:w-1/3 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-300 transition-all duration-200"
          >
            <option value="px">Pixels (px)</option>
            <option value="in">Inches (in)</option>
            <option value="cm">Centimeters (cm)</option>
            <option value="mm">Millimeters (mm)</option>
            <option value="pt">Points (pt)</option>
          </select>
        </div>

        <!-- Zoom Behavior -->
        <div>
          <label for="zoomStep" class="block text-sm font-medium text-gray-900 mb-2">
            Zoom Step (%)
          </label>
          <input
            id="zoomStep"
            v-model.number="settings.editor.zoomStep"
            type="number"
            min="5"
            max="50"
            step="5"
            class="w-full md:w-1/3 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-300 transition-all duration-200"
          />
        </div>

        <!-- Auto-save -->
        <div>
          <label class="flex items-center space-x-3 mb-2">
            <input
              v-model="settings.editor.autoSave"
              type="checkbox"
              class="w-4 h-4 text-violet-600 border-gray-300 rounded focus:ring-violet-500/20"
            />
            <span class="text-sm font-medium text-gray-900">Auto-save designs every</span>
          </label>
          <div v-if="settings.editor.autoSave" class="ml-7">
            <select
              v-model="settings.editor.autoSaveInterval"
              class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-300 transition-all duration-200"
            >
              <option value="30">30 seconds</option>
              <option value="60">1 minute</option>
              <option value="300">5 minutes</option>
              <option value="600">10 minutes</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Export Defaults -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm">
      <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
          <ArrowDownTrayIcon class="w-5 h-5 mr-2 text-gray-600" />
          Export Defaults
        </h3>
        <p class="text-sm text-gray-600 mt-1">Set default export settings for your designs</p>
      </div>
      
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label for="defaultFormat" class="block text-sm font-medium text-gray-900 mb-2">
              Default Format
            </label>
            <select
              id="defaultFormat"
              v-model="settings.export.defaultFormat"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-300 transition-all duration-200"
            >
              <option value="png">PNG</option>
              <option value="jpg">JPEG</option>
              <option value="svg">SVG</option>
              <option value="pdf">PDF</option>
            </select>
          </div>

          <div>
            <label for="defaultQuality" class="block text-sm font-medium text-gray-900 mb-2">
              Default Quality
            </label>
            <select
              id="defaultQuality"
              v-model="settings.export.defaultQuality"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-300 transition-all duration-200"
            >
              <option value="web">Web (72 DPI)</option>
              <option value="print">Print (300 DPI)</option>
              <option value="high">High (600 DPI)</option>
            </select>
          </div>

          <div>
            <label for="defaultBackground" class="block text-sm font-medium text-gray-900 mb-2">
              Default Background
            </label>
            <select
              id="defaultBackground"
              v-model="settings.export.defaultBackground"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-300 transition-all duration-200"
            >
              <option value="transparent">Transparent</option>
              <option value="white">White</option>
              <option value="black">Black</option>
              <option value="custom">Custom Color</option>
            </select>
          </div>

          <div class="flex items-center">
            <label class="flex items-center space-x-3">
              <input
                v-model="settings.export.includeBleed"
                type="checkbox"
                class="w-4 h-4 text-violet-600 border-gray-300 rounded focus:ring-violet-500/20"
              />
              <span class="text-sm text-gray-700">Include bleed area in exports</span>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
      <button
        @click="resetToDefaults"
        class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-violet-500/20 transition-colors"
      >
        Reset to Defaults
      </button>
      
      <div class="flex space-x-3">
        <button
          @click="saveSettings"
          :disabled="isSaving"
          class="px-6 py-2.5 bg-gradient-to-r from-violet-600 to-purple-600 text-white text-sm font-medium rounded-xl hover:from-violet-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-violet-500/20 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg transition-all duration-200"
        >
          <span v-if="isSaving" class="flex items-center">
            <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
            Saving...
          </span>
          <span v-else>Save Settings</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { CogIcon, PencilIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline'

// State
const isSaving = ref(false)

const settings = reactive({
  editor: {
    showGrid: true,
    snapToGrid: true,
    showRulers: true,
    showGuides: true,
    defaultUnit: 'px',
    zoomStep: 25,
    autoSave: true,
    autoSaveInterval: 300
  },
  export: {
    defaultFormat: 'png',
    defaultQuality: 'web',
    defaultBackground: 'transparent',
    includeBleed: false
  }
})

// Methods
const saveSettings = async () => {
  isSaving.value = true
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1500))
    console.log('Settings saved:', settings)
    // Show success notification
  } catch (error) {
    console.error('Error saving settings:', error)
    // Show error notification
  } finally {
    isSaving.value = false
  }
}

const resetToDefaults = () => {
  if (confirm('Are you sure you want to reset all settings to their default values?')) {
    Object.assign(settings, {
      editor: {
        showGrid: true,
        snapToGrid: true,
        showRulers: true,
        showGuides: true,
        defaultUnit: 'px',
        zoomStep: 25,
        autoSave: true,
        autoSaveInterval: 300
      },
      export: {
        defaultFormat: 'png',
        defaultQuality: 'web',
        defaultBackground: 'transparent',
        includeBleed: false
      }
    })
    console.log('Settings reset to defaults')
  }
}
</script>
