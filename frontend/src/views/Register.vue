<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-violet-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo and Header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-indigo-600 to-violet-600 rounded-2xl shadow-lg mb-4">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Create Account</h1>
        <p class="text-gray-600">Join {{ appTitle }} and start designing</p>
      </div>

      <!-- Register Form -->
      <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
        <form @submit.prevent="handleRegister" class="space-y-6">
          <!-- Name Fields -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2">
                First Name
              </label>
              <input
                id="firstName"
                v-model="registerForm.firstName"
                type="text"
                required
                autocomplete="given-name"
                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
                placeholder="John"
                :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-500/20': firstNameError }"
              />
              <p v-if="firstNameError" class="mt-1 text-sm text-red-600">{{ firstNameError }}</p>
            </div>
            <div>
              <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">
                Last Name
              </label>
              <input
                id="lastName"
                v-model="registerForm.lastName"
                type="text"
                required
                autocomplete="family-name"
                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
                placeholder="Doe"
                :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-500/20': lastNameError }"
              />
              <p v-if="lastNameError" class="mt-1 text-sm text-red-600">{{ lastNameError }}</p>
            </div>
          </div>

          <!-- Email Field -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
              Email Address
            </label>
            <div class="relative">
              <input
                id="email"
                v-model="registerForm.email"
                type="email"
                required
                autocomplete="email"
                class="w-full px-4 py-3 pl-12 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
                placeholder="john@example.com"
                :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-500/20': emailError }"
              />
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <EnvelopeIcon class="h-5 w-5 text-gray-400" />
              </div>
            </div>
            <p v-if="emailError" class="mt-1 text-sm text-red-600">{{ emailError }}</p>
          </div>

          <!-- Password Field -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
              Password
            </label>
            <div class="relative">
              <input
                id="password"
                v-model="registerForm.password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="new-password"
                class="w-full px-4 py-3 pl-12 pr-12 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
                placeholder="Create a secure password"
                :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-500/20': passwordError }"
              />
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <LockClosedIcon class="h-5 w-5 text-gray-400" />
              </div>
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
              >
                <EyeIcon v-if="!showPassword" class="h-5 w-5" />
                <EyeSlashIcon v-else class="h-5 w-5" />
              </button>
            </div>
            <p v-if="passwordError" class="mt-1 text-sm text-red-600">{{ passwordError }}</p>
            
            <!-- Password Strength Indicator -->
            <div v-if="registerForm.password" class="mt-2">
              <div class="flex items-center space-x-2">
                <div class="flex-1 bg-gray-200 rounded-full h-2">
                  <div 
                    class="h-2 rounded-full transition-all duration-300"
                    :class="passwordStrengthColor"
                    :style="{ width: passwordStrengthWidth }"
                  ></div>
                </div>
                <span class="text-xs font-medium" :class="passwordStrengthTextColor">
                  {{ passwordStrengthText }}
                </span>
              </div>
            </div>
          </div>

          <!-- Confirm Password Field -->
          <div>
            <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-2">
              Confirm Password
            </label>
            <div class="relative">
              <input
                id="confirmPassword"
                v-model="registerForm.confirmPassword"
                :type="showConfirmPassword ? 'text' : 'password'"
                required
                autocomplete="new-password"
                class="w-full px-4 py-3 pl-12 pr-12 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-300 transition-all duration-200"
                placeholder="Confirm your password"
                :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-500/20': confirmPasswordError }"
              />
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <LockClosedIcon class="h-5 w-5 text-gray-400" />
              </div>
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
              >
                <EyeIcon v-if="!showConfirmPassword" class="h-5 w-5" />
                <EyeSlashIcon v-else class="h-5 w-5" />
              </button>
            </div>
            <p v-if="confirmPasswordError" class="mt-1 text-sm text-red-600">{{ confirmPasswordError }}</p>
          </div>

          <!-- Terms and Privacy -->
          <div class="flex items-start">
            <input
              id="acceptTerms"
              v-model="registerForm.acceptTerms"
              type="checkbox"
              required
              class="w-4 h-4 mt-1 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500/20 focus:ring-2"
            />
            <label for="acceptTerms" class="ml-3 text-sm text-gray-600">
              I agree to the
              <a href="/terms" class="text-indigo-600 hover:text-indigo-700 font-medium">Terms of Service</a>
              and
              <a href="/privacy" class="text-indigo-600 hover:text-indigo-700 font-medium">Privacy Policy</a>
            </label>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="isLoading || !isFormValid"
            class="w-full bg-gradient-to-r from-indigo-600 to-violet-600 text-white py-3 px-4 rounded-xl font-medium hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg"
          >
            <span v-if="isLoading" class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Creating Account...
            </span>
            <span v-else>Create Account</span>
          </button>
        </form>

        <!-- Sign In Link -->
        <div class="mt-8 text-center">
          <p class="text-sm text-gray-600">
            Already have an account?
            <router-link
              to="/login"
              class="text-indigo-600 hover:text-indigo-700 font-medium transition-colors"
            >
              Sign in here
            </router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import {
  EnvelopeIcon,
  LockClosedIcon,
  EyeIcon,
  EyeSlashIcon,
} from '@heroicons/vue/24/outline'
import { useAuth } from '@/composables/useAuth'

// Router
const router = useRouter()

// Auth composable
const { register, isLoading } = useAuth()

// Form state
const registerForm = ref({
  firstName: '',
  lastName: '',
  email: '',
  password: '',
  confirmPassword: '',
  acceptTerms: false,
})

const showPassword = ref(false)
const showConfirmPassword = ref(false)

// Validation errors
const firstNameError = ref('')
const lastNameError = ref('')
const emailError = ref('')
const passwordError = ref('')
const confirmPasswordError = ref('')

// Computed properties
const appTitle = computed(() => import.meta.env.VITE_APP_TITLE || 'Design Studio')

// Password strength calculation
const passwordStrength = computed(() => {
  const password = registerForm.value.password
  if (!password) return 0
  
  let score = 0
  
  // Length check
  if (password.length >= 8) score += 25
  if (password.length >= 12) score += 15
  
  // Character type checks
  if (/[a-z]/.test(password)) score += 15
  if (/[A-Z]/.test(password)) score += 15
  if (/[0-9]/.test(password)) score += 15
  if (/[^A-Za-z0-9]/.test(password)) score += 15
  
  return Math.min(score, 100)
})

const passwordStrengthText = computed(() => {
  const strength = passwordStrength.value
  if (strength < 25) return 'Weak'
  if (strength < 50) return 'Fair'
  if (strength < 75) return 'Good'
  return 'Strong'
})

const passwordStrengthColor = computed(() => {
  const strength = passwordStrength.value
  if (strength < 25) return 'bg-red-500'
  if (strength < 50) return 'bg-orange-500'
  if (strength < 75) return 'bg-yellow-500'
  return 'bg-green-500'
})

const passwordStrengthTextColor = computed(() => {
  const strength = passwordStrength.value
  if (strength < 25) return 'text-red-600'
  if (strength < 50) return 'text-orange-600'
  if (strength < 75) return 'text-yellow-600'
  return 'text-green-600'
})

const passwordStrengthWidth = computed(() => {
  return `${passwordStrength.value}%`
})

// Form validation
const isFormValid = computed(() => {
  return registerForm.value.firstName.length > 0 &&
         registerForm.value.lastName.length > 0 &&
         registerForm.value.email.length > 0 &&
         registerForm.value.password.length > 0 &&
         registerForm.value.confirmPassword.length > 0 &&
         registerForm.value.acceptTerms &&
         !firstNameError.value &&
         !lastNameError.value &&
         !emailError.value &&
         !passwordError.value &&
         !confirmPasswordError.value
})

// Validation functions
const validateFirstName = () => {
  const name = registerForm.value.firstName.trim()
  if (!name) {
    firstNameError.value = 'First name is required'
    return false
  }
  if (name.length < 2) {
    firstNameError.value = 'First name must be at least 2 characters'
    return false
  }
  firstNameError.value = ''
  return true
}

const validateLastName = () => {
  const name = registerForm.value.lastName.trim()
  if (!name) {
    lastNameError.value = 'Last name is required'
    return false
  }
  if (name.length < 2) {
    lastNameError.value = 'Last name must be at least 2 characters'
    return false
  }
  lastNameError.value = ''
  return true
}

const validateEmail = () => {
  const email = registerForm.value.email
  if (!email) {
    emailError.value = 'Email is required'
    return false
  }
  
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(email)) {
    emailError.value = 'Please enter a valid email address'
    return false
  }
  
  emailError.value = ''
  return true
}

const validatePassword = () => {
  const password = registerForm.value.password
  if (!password) {
    passwordError.value = 'Password is required'
    return false
  }
  
  if (password.length < 8) {
    passwordError.value = 'Password must be at least 8 characters'
    return false
  }
  
  if (passwordStrength.value < 50) {
    passwordError.value = 'Password is too weak. Use a mix of letters, numbers, and symbols.'
    return false
  }
  
  passwordError.value = ''
  return true
}

const validateConfirmPassword = () => {
  const password = registerForm.value.password
  const confirmPassword = registerForm.value.confirmPassword
  
  if (!confirmPassword) {
    confirmPasswordError.value = 'Please confirm your password'
    return false
  }
  
  if (password !== confirmPassword) {
    confirmPasswordError.value = 'Passwords do not match'
    return false
  }
  
  confirmPasswordError.value = ''
  return true
}

// Handle registration
const handleRegister = async () => {
  // Validate all fields
  const isFirstNameValid = validateFirstName()
  const isLastNameValid = validateLastName()
  const isEmailValid = validateEmail()
  const isPasswordValid = validatePassword()
  const isConfirmPasswordValid = validateConfirmPassword()
  
  if (!isFirstNameValid || !isLastNameValid || !isEmailValid || !isPasswordValid || !isConfirmPasswordValid) {
    return
  }

  // Attempt registration
  const result = await register({
    firstName: registerForm.value.firstName.trim(),
    lastName: registerForm.value.lastName.trim(),
    email: registerForm.value.email,
    password: registerForm.value.password,
    confirmPassword: registerForm.value.confirmPassword,
  })

  if (result.success) {
    console.log('Registration successful')
  }
}

// Clear errors when form changes
const clearErrors = () => {
  firstNameError.value = ''
  lastNameError.value = ''
  emailError.value = ''
  passwordError.value = ''
  confirmPasswordError.value = ''
}

// Watch form changes
watch(() => registerForm.value.firstName, clearErrors)
watch(() => registerForm.value.lastName, clearErrors)
watch(() => registerForm.value.email, clearErrors)
watch(() => registerForm.value.password, clearErrors)
watch(() => registerForm.value.confirmPassword, clearErrors)
</script>

<style scoped>
/* Custom styles for enhanced visual appeal */
.bg-gradient-to-br {
  background-image: linear-gradient(to bottom right, var(--tw-gradient-stops));
}

/* Focus ring enhancement */
input:focus {
  box-shadow: 0 0 0 3px rgb(99 102 241 / 0.1);
}

/* Button hover effects */
button:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgb(0 0 0 / 0.15);
}

/* Animation for loading spinner */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
