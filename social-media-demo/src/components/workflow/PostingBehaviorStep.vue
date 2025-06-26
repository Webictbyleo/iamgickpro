<template>
  <div class="h-full flex flex-col bg-gray-50">
    <!-- Clean Header -->
    <div class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center space-x-4">
        <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center">
          <span class="text-white font-semibold">🚀</span>
        </div>
        <div>
          <h1 class="text-xl font-semibold text-gray-900">Posting Behavior</h1>
          <p class="text-sm text-gray-600">Configure intelligent auto-posting to maximize viral potential</p>
        </div>
      </div>
    </div>

    <!-- Tab Navigation -->
    <div class="bg-white border-b border-gray-200 px-6">
      <nav class="flex space-x-8" role="tablist">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'py-3 px-1 border-b-2 font-medium text-sm transition-colors',
            activeTab === tab.id
              ? 'border-green-500 text-green-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 overflow-auto">
      <!-- Content Summary Tab -->
      <div v-show="activeTab === 'summary'" class="p-6">
        <div class="max-w-4xl mx-auto space-y-6">
          <div>
            <h2 class="text-lg font-medium text-gray-900 mb-2">Content Summary</h2>
            <p class="text-gray-600 mb-6">Review your content before configuring posting behavior.</p>
          </div>

          <!-- Content Preview -->
          <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="space-y-4">
              <div>
                <span class="font-medium text-gray-900">Content:</span>
                <p class="text-gray-700 mt-2 p-4 bg-gray-50 rounded-lg">{{ workflowStore.generatedContent?.content.text }}</p>
              </div>
              
              <div>
                <span class="font-medium text-gray-900">Target Platforms:</span>
                <div class="flex flex-wrap gap-2 mt-2">
                  <span
                    v-for="account in workflowStore.selectedAccounts"
                    :key="account.id"
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800"
                  >
                    <img :src="account.avatar" :alt="account.display_name" class="w-4 h-4 rounded-full mr-2">
                    {{ account.display_name }}
                  </span>
                </div>
              </div>
              
              <div v-if="workflowStore.generatedCreatives.length > 0">
                <span class="font-medium text-gray-900">Cover Image:</span>
                <div class="mt-2 text-sm text-green-600">✓ {{ workflowStore.generatedCreatives.length }} asset(s) ready</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Scheduling Tab -->
      <div v-show="activeTab === 'scheduling'" class="p-6">
        <div class="max-w-4xl mx-auto space-y-6">
          <div>
            <h2 class="text-lg font-medium text-gray-900 mb-2">Smart Posting Schedule</h2>
            <p class="text-gray-600 mb-6">Configure when and how your content should be posted for maximum viral potential.</p>
          </div>

          <!-- Posting Mode Selection -->
          <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <h3 class="font-medium text-gray-900 mb-4">Posting Mode</h3>
            <div class="space-y-4">
              <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                <input
                  v-model="behavior.mode"
                  value="immediate"
                  type="radio"
                  class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500 mt-1"
                >
                <div class="ml-3">
                  <span class="text-sm font-medium text-gray-900">Post Immediately</span>
                  <p class="text-sm text-gray-500">Publish your content right now across all selected platforms</p>
                </div>
              </label>
              
              <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                <input
                  v-model="behavior.mode"
                  value="scheduled"
                  type="radio"
                  class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500 mt-1"
                >
                <div class="ml-3">
                  <span class="text-sm font-medium text-gray-900">Schedule for Optimal Time</span>
                  <p class="text-sm text-gray-500">AI will determine the best time to post based on your audience engagement patterns</p>
                </div>
              </label>
              
              <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                <input
                  v-model="behavior.mode"
                  value="auto"
                  type="radio"
                  class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500 mt-1"
                >
                <div class="ml-3">
                  <span class="text-sm font-medium text-gray-900">Auto-Posting Campaign</span>
                  <p class="text-sm text-gray-500">Intelligent campaign that posts and reposts to maximize viral reach</p>
                </div>
              </label>
            </div>
          </div>

          <!-- Scheduled Posting Options -->
          <div v-if="behavior.mode === 'scheduled'" class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-medium text-gray-900 mb-4">Schedule Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Date & Time</label>
                <input
                  v-model="behavior.scheduleTime"
                  type="datetime-local"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                  :min="minDateTime"
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                <select
                  v-model="behavior.timezone"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                >
                  <option value="UTC">UTC</option>
                  <option value="America/New_York">Eastern Time</option>
                  <option value="America/Chicago">Central Time</option>
                  <option value="America/Denver">Mountain Time</option>
                  <option value="America/Los_Angeles">Pacific Time</option>
                  <option value="Europe/London">London</option>
                  <option value="Europe/Paris">Paris</option>
                  <option value="Asia/Tokyo">Tokyo</option>
                </select>
              </div>
            </div>
            
            <div class="mt-4">
              <label class="flex items-center">
                <input
                  v-model="behavior.optimizeTime"
                  type="checkbox"
                  class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                >
                <span class="ml-3 text-sm text-gray-700">Allow AI to adjust time for optimal engagement (±2 hours)</span>
              </label>
            </div>
          </div>

          <!-- Auto-Posting Campaign -->
          <div v-if="behavior.mode === 'auto'" class="space-y-6">
            <!-- Campaign Settings -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
              <h3 class="font-medium text-gray-900 mb-4">Campaign Settings</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Campaign Start</label>
                  <input
                    v-model="behavior.campaignStart"
                    type="datetime-local"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                    :min="minDateTime"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Posting Frequency</label>
                  <select
                    v-model="behavior.frequency"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                  >
                    <option value="optimal">AI Optimal (Recommended)</option>
                    <option value="hourly">Every Hour</option>
                    <option value="daily">Daily</option>
                    <option value="bidaily">Twice Daily</option>
                    <option value="weekly">Weekly</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Viral Success Criteria -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
              <h3 class="font-medium text-gray-900 mb-4">Viral Success Targets</h3>
              <p class="text-gray-600 mb-4">Define what success looks like and when to stop the campaign.</p>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Target Views/Impressions</label>
                  <input
                    v-model.number="criteria.targetViews"
                    type="number"
                    placeholder="e.g., 10000"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Target Engagement Rate (%)</label>
                  <input
                    v-model.number="criteria.targetEngagement"
                    type="number"
                    placeholder="e.g., 5"
                    min="0"
                    max="100"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Target Likes/Reactions</label>
                  <input
                    v-model.number="criteria.targetLikes"
                    type="number"
                    placeholder="e.g., 500"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Target Shares/Retweets</label>
                  <input
                    v-model.number="criteria.targetShares"
                    type="number"
                    placeholder="e.g., 100"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                  >
                </div>
              </div>
            </div>

            <!-- Campaign Stop Conditions -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
              <h3 class="font-medium text-gray-900 mb-4">Campaign Stop Conditions</h3>
              <div class="space-y-4">
                <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                  <input
                    v-model="criteria.stopCondition"
                    value="target_reached"
                    type="radio"
                    class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500 mt-1"
                  >
                  <div class="ml-3">
                    <span class="text-sm font-medium text-gray-900">Stop When Targets Are Reached</span>
                    <p class="text-sm text-gray-500">Campaign stops automatically when any success target is achieved</p>
                  </div>
                </label>
                
                <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                  <input
                    v-model="criteria.stopCondition"
                    value="date_reached"
                    type="radio"
                    class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500 mt-1"
                  >
                  <div class="ml-3">
                    <span class="text-sm font-medium text-gray-900">Stop on Specific Date</span>
                    <p class="text-sm text-gray-500">Campaign runs until a specified end date regardless of performance</p>
                  </div>
                </label>
                
                <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                  <input
                    v-model="criteria.stopCondition"
                    value="performance_decline"
                    type="radio"
                    class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500 mt-1"
                  >
                  <div class="ml-3">
                    <span class="text-sm font-medium text-gray-900">Stop on Performance Decline</span>
                    <p class="text-sm text-gray-500">Campaign stops when engagement drops below threshold for 24 hours</p>
                  </div>
                </label>
              </div>

              <!-- End Date Selection -->
              <div v-if="criteria.stopCondition === 'date_reached'" class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Campaign End Date</label>
                <input
                  v-model="criteria.endDate"
                  type="datetime-local"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                  :min="minDateTime"
                >
              </div>

              <!-- Performance Threshold -->
              <div v-if="criteria.stopCondition === 'performance_decline'" class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Engagement Rate (%)</label>
                <input
                  v-model.number="criteria.minEngagementRate"
                  type="number"
                  placeholder="e.g., 1"
                  min="0"
                  max="100"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                >
              </div>
            </div>

            <!-- Viral Acceleration Features -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
              <h3 class="font-medium text-gray-900 mb-4">Viral Acceleration</h3>
              <div class="space-y-4">
                <label class="flex items-start">
                  <input
                    v-model="criteria.viralBoost"
                    type="checkbox"
                    class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 mt-1"
                  >
                  <div class="ml-3">
                    <span class="text-sm font-medium text-gray-900">Viral Momentum Detection</span>
                    <p class="text-sm text-gray-500">Increase posting frequency when content shows viral potential</p>
                  </div>
                </label>
                
                <label class="flex items-start">
                  <input
                    v-model="criteria.influencerOutreach"
                    type="checkbox"
                    class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 mt-1"
                  >
                  <div class="ml-3">
                    <span class="text-sm font-medium text-gray-900">Auto Influencer Notifications</span>
                    <p class="text-sm text-gray-500">Notify relevant influencers when content gains traction</p>
                  </div>
                </label>
              </div>
            </div>
            </div>

            <!-- Viral Boost Options -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
              <h3 class="font-medium text-gray-900 mb-4">Viral Boost Features</h3>
              <div class="space-y-4">
                <label class="flex items-start">
                  <input
                    v-model="behavior.adaptivePosting"
                    type="checkbox"
                    class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 mt-1"
                  >
                  <div class="ml-3">
                    <span class="text-sm font-medium text-gray-900">Adaptive Posting</span>
                    <p class="text-sm text-gray-500">Adjust posting frequency based on engagement performance</p>
                  </div>
                </label>
                
                <label class="flex items-start">
                  <input
                    v-model="behavior.crossPlatform"
                    type="checkbox"
                    class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 mt-1"
                  >
                  <div class="ml-3">
                    <span class="text-sm font-medium text-gray-900">Cross-Platform Optimization</span>
                    <p class="text-sm text-gray-500">Optimize posting times individually for each platform</p>
                  </div>
                </label>
                
                <label class="flex items-start">
                  <input
                    v-model="behavior.trendingHashtags"
                    type="checkbox"
                    class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 mt-1"
                  >
                  <div class="ml-3">
                    <span class="text-sm font-medium text-gray-900">Dynamic Hashtag Optimization</span>
                    <p class="text-sm text-gray-500">Automatically add trending hashtags to boost discoverability</p>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Platform Settings Tab -->
      <div v-show="activeTab === 'platforms'" class="p-6">
        <div class="max-w-4xl mx-auto space-y-6">
          <div>
            <h2 class="text-lg font-medium text-gray-900 mb-2">Platform-Specific Settings</h2>
            <p class="text-gray-600 mb-6">Customize posting behavior for each social media platform.</p>
          </div>

          <!-- Platform Settings -->
          <div class="space-y-6">
            <div
              v-for="account in workflowStore.selectedAccounts"
              :key="account.id"
              class="bg-white rounded-lg border border-gray-200 p-6"
            >
              <div class="flex items-center mb-4">
                <img :src="account.avatar" :alt="account.display_name" class="w-8 h-8 rounded-full mr-3">
                <div>
                  <h3 class="font-medium text-gray-900">{{ account.display_name }}</h3>
                  <p class="text-sm text-gray-500">{{ getPlatformName(account.platform) }}</p>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Optimal Posting Times</label>
                  <select
                    v-model="platformSettings[account.platform].optimalTimes"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                  >
                    <option value="auto">AI Determined</option>
                    <option value="morning">Morning (6-9 AM)</option>
                    <option value="afternoon">Afternoon (12-3 PM)</option>
                    <option value="evening">Evening (6-9 PM)</option>
                    <option value="night">Night (9-11 PM)</option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Content Optimization</label>
                  <select
                    v-model="platformSettings[account.platform].optimization"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                  >
                    <option value="standard">Standard</option>
                    <option value="hashtag_heavy">Hashtag Heavy</option>
                    <option value="engagement_focused">Engagement Focused</option>
                    <option value="viral_optimized">Viral Optimized</option>
                  </select>
                </div>
              </div>

              <div class="mt-4 space-y-3">
                <label class="flex items-center">
                  <input
                    v-model="platformSettings[account.platform].autoHashtags"
                    type="checkbox"
                    class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                  >
                  <span class="ml-3 text-sm text-gray-700">Add trending hashtags automatically</span>
                </label>
                
                <label class="flex items-center">
                  <input
                    v-model="platformSettings[account.platform].engagementBoost"
                    type="checkbox"
                    class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                  >
                  <span class="ml-3 text-sm text-gray-700">Enable engagement boost features</span>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>

    <!-- Campaign Preview -->
    <div v-if="behavior.mode === 'auto' && activeTab === 'scheduling'" class="bg-white border-t border-gray-200 px-6 py-4">
      <div class="max-w-4xl mx-auto">
        <h3 class="font-medium text-gray-900 mb-3">Campaign Preview</h3>
        <div class="bg-gray-50 rounded-lg p-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
              <span class="font-medium text-gray-700">Mode:</span>
              <span class="ml-2 text-gray-600">{{ formatMode(behavior.mode) }}</span>
            </div>
            <div v-if="behavior.mode === 'auto'">
              <span class="font-medium text-gray-700">Duration:</span>
              <span class="ml-2 text-gray-600">{{ calculateDuration() }}</span>
            </div>
            <div>
              <span class="font-medium text-gray-700">Platforms:</span>
              <span class="ml-2 text-gray-600">{{ workflowStore.selectedAccounts.length }} selected</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="bg-white border-t border-gray-200 px-6 py-4">
      <div class="max-w-4xl mx-auto flex justify-center space-x-4">
        <button
          v-if="behavior.mode === 'immediate'"
          @click="publishNow"
          :disabled="isLoading"
          class="px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
        >
          <span v-if="isLoading" class="flex items-center">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Publishing...
          </span>
          <span v-else>🚀 Publish Now</span>
        </button>
        
        <button
          v-else-if="behavior.mode === 'scheduled'"
          @click="schedulePost"
          :disabled="isLoading || !isValidSchedule"
          class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
        >
          <span v-if="isLoading" class="flex items-center">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Scheduling...
          </span>
          <span v-else>📅 Schedule Post</span>
        </button>
        
        <button
          v-else-if="behavior.mode === 'auto'"
          @click="launchCampaign"
          :disabled="isLoading || !isValidCampaign"
          class="px-8 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
        >
          <span v-if="isLoading" class="flex items-center">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Launching...
          </span>
          <span v-else>🎯 Launch Campaign</span>
        </button>
        
        <button
          @click="saveDraft"
          :disabled="isLoading"
          class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
        >
          💾 Save Draft
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useWorkflowStore } from '@/stores/workflow'
import { useUiStore } from '@/stores/ui'
import type { SocialPlatform } from '@/types'

const workflowStore = useWorkflowStore()
const uiStore = useUiStore()

// Tab state
const activeTab = ref('summary')

// Tab configuration
const tabs = [
  { id: 'summary', name: 'Summary' },
  { id: 'scheduling', name: 'Scheduling & Viral' },
  { id: 'platforms', name: 'Platform Settings' }
]

// Posting behavior state
const behavior = reactive({
  mode: 'immediate', // 'immediate', 'scheduled', 'auto'
  scheduleTime: '',
  timezone: 'UTC',
  optimizeTime: true,
  campaignStart: '',
  frequency: 'optimal',
  adaptivePosting: true,
  crossPlatform: true,
  trendingHashtags: true
})

// Viral criteria state
const criteria = reactive({
  targetViews: 10000,
  targetEngagement: 5,
  targetLikes: 500,
  targetShares: 100,
  stopCondition: 'target_reached', // 'target_reached', 'date_reached', 'performance_decline'
  endDate: '',
  minEngagementRate: 1,
  viralBoost: true,
  influencerOutreach: false
})

// Platform-specific settings
const platformSettings = reactive<Record<SocialPlatform, any>>({
  twitter: {
    optimalTimes: 'auto',
    optimization: 'viral_optimized',
    autoHashtags: true,
    engagementBoost: true
  },
  linkedin: {
    optimalTimes: 'auto',
    optimization: 'engagement_focused',
    autoHashtags: false,
    engagementBoost: true
  },
  instagram: {
    optimalTimes: 'auto',
    optimization: 'hashtag_heavy',
    autoHashtags: true,
    engagementBoost: true
  },
  facebook: {
    optimalTimes: 'auto',
    optimization: 'standard',
    autoHashtags: false,
    engagementBoost: true
  },
  tiktok: {
    optimalTimes: 'auto',
    optimization: 'viral_optimized',
    autoHashtags: true,
    engagementBoost: true
  },
  youtube: {
    optimalTimes: 'auto',
    optimization: 'engagement_focused',
    autoHashtags: false,
    engagementBoost: true
  }
})

// Loading state
const isLoading = ref(false)

// Computed properties
const minDateTime = computed(() => {
  const now = new Date()
  now.setMinutes(now.getMinutes() + 30) // Minimum 30 minutes from now
  return now.toISOString().slice(0, 16)
})

const isValidSchedule = computed(() => {
  if (behavior.mode !== 'scheduled') return true
  if (!behavior.scheduleTime) return false
  const scheduleDate = new Date(behavior.scheduleTime)
  const now = new Date()
  return scheduleDate > now
})

const isValidCampaign = computed(() => {
  if (behavior.mode !== 'auto') return true
  if (!behavior.campaignStart) return false
  const startDate = new Date(behavior.campaignStart)
  const now = new Date()
  return startDate > now
})

// Methods
const publishNow = async () => {
  if (!workflowStore.generatedContent) return

  isLoading.value = true
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    // Update workflow store
    const postingBehavior = {
      immediate: true,
      scheduled: false,
      auto_optimize: behavior.optimizeTime,
      cross_platform: behavior.crossPlatform,
      platforms: workflowStore.selectedAccounts.map(a => a.platform),
      timezone: behavior.timezone
    }
    
    workflowStore.setPostingBehavior(postingBehavior)
    
    uiStore.addNotification({
      type: 'success',
      title: 'Content Published Successfully! 🚀',
      message: `Your content has been published to ${workflowStore.selectedAccounts.length} platform(s). Monitor performance in your dashboard.`
    })

    // Simulate campaign tracking
    setTimeout(() => {
      uiStore.addNotification({
        type: 'info',
        title: 'Engagement Update 📈',
        message: 'Your post is gaining traction! 47 likes and 12 shares in the first hour.'
      })
    }, 5000)
    
  } catch (error) {
    console.error('Failed to publish:', error)
    uiStore.addNotification({
      type: 'error',
      title: 'Publishing Failed',
      message: 'Failed to publish content. Please try again.'
    })
  } finally {
    isLoading.value = false
  }
}

const schedulePost = async () => {
  if (!isValidSchedule.value) return

  isLoading.value = true
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    const postingBehavior = {
      immediate: false,
      scheduled: true,
      schedule_time: behavior.scheduleTime,
      timezone: behavior.timezone,
      auto_optimize: behavior.optimizeTime,
      cross_platform: behavior.crossPlatform,
      platforms: workflowStore.selectedAccounts.map(a => a.platform)
    }
    
    workflowStore.setPostingBehavior(postingBehavior)
    
    const scheduleDate = new Date(behavior.scheduleTime).toLocaleString()
    
    uiStore.addNotification({
      type: 'success',
      title: 'Content Scheduled Successfully! 📅',
      message: `Your content will be published on ${scheduleDate}. You'll receive notifications when it goes live.`
    })
    
  } catch (error) {
    console.error('Failed to schedule:', error)
    uiStore.addNotification({
      type: 'error',
      title: 'Scheduling Failed',
      message: 'Failed to schedule content. Please try again.'
    })
  } finally {
    isLoading.value = false
  }
}

const launchCampaign = async () => {
  if (!isValidCampaign.value) return

  isLoading.value = true
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 3000))
    
    const campaignData = {
      immediate: false,
      scheduled: true,
      schedule_time: behavior.campaignStart,
      auto_optimize: behavior.adaptivePosting,
      cross_platform: behavior.crossPlatform,
      platforms: workflowStore.selectedAccounts.map(a => a.platform),
      timezone: behavior.timezone
    }
    
    workflowStore.setPostingBehavior(campaignData)
    
    const startDate = new Date(behavior.campaignStart).toLocaleString()
    
    uiStore.addNotification({
      type: 'success',
      title: 'Viral Campaign Launched! 🎯',
      message: `Your auto-posting campaign will start on ${startDate}. AI will optimize posting for maximum viral potential.`
    })

    // Simulate campaign progress updates
    setTimeout(() => {
      uiStore.addNotification({
        type: 'info',
        title: 'Campaign Started 🚀',
        message: 'Your viral campaign is now active! First post scheduled for optimal engagement time.'
      })
    }, 3000)

    setTimeout(() => {
      uiStore.addNotification({
        type: 'success',
        title: 'Viral Momentum Detected! 📈',
        message: 'Your content is trending! Increasing posting frequency to maximize reach.'
      })
    }, 8000)
    
  } catch (error) {
    console.error('Failed to launch campaign:', error)
    uiStore.addNotification({
      type: 'error',
      title: 'Campaign Launch Failed',
      message: 'Failed to launch campaign. Please try again.'
    })
  } finally {
    isLoading.value = false
  }
}

const saveDraft = async () => {
  isLoading.value = true
  
  // Simulate saving draft
  setTimeout(() => {
    const draftData = {
      immediate: false,
      scheduled: behavior.mode === 'scheduled',
      schedule_time: behavior.scheduleTime,
      auto_optimize: behavior.optimizeTime || behavior.adaptivePosting,
      cross_platform: behavior.crossPlatform,
      platforms: workflowStore.selectedAccounts.map(a => a.platform),
      timezone: behavior.timezone
    }
    
    workflowStore.setPostingBehavior(draftData)
    
    uiStore.addNotification({
      type: 'info',
      title: 'Draft Saved 💾',
      message: 'Your posting configuration has been saved as a draft.'
    })
    
    isLoading.value = false
  }, 1000)
}

// Helper functions
const getPlatformName = (platform: SocialPlatform) => {
  const names: Record<SocialPlatform, string> = {
    twitter: 'Twitter',
    linkedin: 'LinkedIn',
    instagram: 'Instagram',
    facebook: 'Facebook',
    tiktok: 'TikTok',
    youtube: 'YouTube'
  }
  return names[platform] || platform
}

const formatMode = (mode: string) => {
  const modes: Record<string, string> = {
    immediate: 'Publish Now',
    scheduled: 'Scheduled Post',
    auto: 'Auto-Posting Campaign'
  }
  return modes[mode] || mode
}

const calculateDuration = () => {
  if (criteria.stopCondition === 'date_reached' && criteria.endDate) {
    const start = new Date(behavior.campaignStart)
    const end = new Date(criteria.endDate)
    const days = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24))
    return `${days} days`
  } else if (criteria.stopCondition === 'target_reached') {
    return 'Until targets reached'
  } else if (criteria.stopCondition === 'performance_decline') {
    return 'Until performance declines'
  }
  return 'Dynamic duration'
}

// Initialize default values
onMounted(() => {
  // Set default schedule time to 1 hour from now
  const defaultTime = new Date()
  defaultTime.setHours(defaultTime.getHours() + 1)
  behavior.scheduleTime = defaultTime.toISOString().slice(0, 16)
  
  // Set default campaign start to tomorrow
  const campaignStart = new Date()
  campaignStart.setDate(campaignStart.getDate() + 1)
  campaignStart.setHours(9, 0, 0, 0) // 9 AM tomorrow
  behavior.campaignStart = campaignStart.toISOString().slice(0, 16)
  
  // Set default end date to 7 days from campaign start
  const endDate = new Date(campaignStart)
  endDate.setDate(endDate.getDate() + 7)
  criteria.endDate = endDate.toISOString().slice(0, 16)
})
</script>

<style scoped>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
