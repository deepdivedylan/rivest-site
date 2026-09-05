<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { SunIcon, MoonIcon } from '@heroicons/vue/24/solid'

const isDarkMode = ref(false)
const currentYear = new Date().getFullYear()

// Data refs
const serverName = ref('Station Identifier Unknown')
const hostData = ref<any>(null)
const storageData = ref<any>(null)
const upsData = ref<any>(null)

const applyTheme = (dark: boolean) => {
  if (dark) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}

const fetchData = async () => {
  try {
    const [serverRes, hostRes, storageRes, upsRes] = await Promise.all([
      fetch('/api/server.php').catch(() => null),
      fetch('/api/host.php').catch(() => null),
      fetch('/api/storage.php').catch(() => null),
      fetch('/api/ups.php').catch(() => null)
    ])
    
    if (serverRes?.ok) {
      const data = await serverRes.json()
      serverName.value = data.server_name || 'Station Identifier Unknown'
    }
    
    if (hostRes?.ok) hostData.value = await hostRes.json()
    if (storageRes?.ok) storageData.value = await storageRes.json()
    if (upsRes?.ok) upsData.value = await upsRes.json()
  } catch (error) {
    console.error('Error hailing APIs:', error)
  }
}

onMounted(() => {
  const storedTheme = sessionStorage.getItem('theme')
  if (storedTheme) {
    isDarkMode.value = storedTheme === 'dark'
  } else {
    isDarkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches
  }
  applyTheme(isDarkMode.value)

  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    if (!sessionStorage.getItem('theme')) {
      isDarkMode.value = e.matches
      applyTheme(isDarkMode.value)
    }
  })

  fetchData()
})

const toggleTheme = () => {
  isDarkMode.value = !isDarkMode.value
  sessionStorage.setItem('theme', isDarkMode.value ? 'dark' : 'light')
  applyTheme(isDarkMode.value)
}

// Helpers for displaying data
const formatBytes = (bytes: number) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatUptime = (seconds: number) => {
  if (!seconds) return '0 seconds'
  
  const years = Math.floor(seconds / 31536000)
  seconds %= 31536000
  const months = Math.floor(seconds / 2592000)
  seconds %= 2592000
  const days = Math.floor(seconds / 86400)
  seconds %= 86400
  const hours = Math.floor(seconds / 3600)
  seconds %= 3600
  const minutes = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  
  const parts = []
  if (years > 0) parts.push(`${years} year${years !== 1 ? 's' : ''}`)
  if (months > 0) parts.push(`${months} month${months !== 1 ? 's' : ''}`)
  if (days > 0) parts.push(`${days} day${days !== 1 ? 's' : ''}`)
  if (hours > 0) parts.push(`${hours} hour${hours !== 1 ? 's' : ''}`)
  if (minutes > 0) parts.push(`${minutes} minute${minutes !== 1 ? 's' : ''}`)
  if (secs > 0) parts.push(`${secs} second${secs !== 1 ? 's' : ''}`)
  
  return parts.join(', ')
}
</script>

<template>
  <div class="min-h-screen transition-colors duration-300 flex flex-col">
    <!-- Header -->
    <header class="p-4 flex justify-end shrink-0">
      <button 
        @click="toggleTheme" 
        class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 dark:focus:ring-gray-100"
        aria-label="Toggle Dark Mode"
      >
        <SunIcon v-if="isDarkMode" class="w-6 h-6 text-yellow-400" aria-hidden="true" />
        <MoonIcon v-else class="w-6 h-6 text-gray-800" aria-hidden="true" />
      </button>
    </header>

    <!-- Main Content -->
    <main class="flex-grow flex flex-col items-center px-4 w-full max-w-6xl mx-auto space-y-8 pb-12">
      <!-- Large Logo Card -->
      <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden flex flex-col md:flex-row items-center p-6 sm:p-10 text-center md:text-left transition-colors">
        <img src="https://www.placecats.com/g/512/512" alt="Server Kitty" class="w-48 h-48 md:w-64 md:h-64 object-cover rounded-full shadow-md mb-6 md:mb-0 md:mr-10" />
        <div>
          <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight mb-2 text-gray-900 dark:text-white">
            {{ serverName }}
          </h1>
          <p class="text-lg text-gray-600 dark:text-gray-300">
            Hailing frequencies open. Telemetry is flowing smoothly.
          </p>
        </div>
      </div>

      <!-- API Cards Grid -->
      <div class="w-full grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Host Card -->
        <div class="bg-gray-100 dark:bg-gray-800 p-6 rounded-2xl shadow-md transition-colors flex flex-col">
          <h2 class="text-2xl font-bold mb-4 border-b border-gray-300 dark:border-gray-700 pb-2">💻 Compute</h2>
          <div v-if="hostData" class="space-y-3 flex-grow text-gray-800 dark:text-gray-200">
            <p><strong class="font-semibold">Load:</strong> {{ hostData.load.join(', ') }}</p>
            <p><strong class="font-semibold">RAM Used:</strong> {{ formatBytes(hostData.memory.used) }} / {{ formatBytes(hostData.memory.total) }}</p>
            <p><strong class="font-semibold">Uptime:</strong> {{ formatUptime(hostData.uptime) }}</p>
          </div>
          <div v-else class="text-gray-500 italic flex-grow flex items-center">Awaiting subspace signals...</div>
        </div>

        <!-- Storage Card -->
        <div class="bg-gray-100 dark:bg-gray-800 p-6 rounded-2xl shadow-md transition-colors flex flex-col">
          <h2 class="text-2xl font-bold mb-4 border-b border-gray-300 dark:border-gray-700 pb-2">💾 Storage Array</h2>
          <div v-if="storageData" class="space-y-3 flex-grow text-gray-800 dark:text-gray-200">
            <p><strong class="font-semibold">Capacity:</strong> {{ formatBytes(storageData.capacity.used) }} / {{ formatBytes(storageData.capacity.total) }}</p>
            <p><strong class="font-semibold">Free Space:</strong> {{ formatBytes(storageData.capacity.available) }} ({{ Math.round((storageData.capacity.available / storageData.capacity.total) * 100) }}% free)</p>
            <div>
              <strong class="font-semibold block mb-1">SMART Health:</strong>
              <div class="flex flex-wrap gap-2">
                <span v-for="(passed, device) in storageData.smart" :key="device" 
                      class="px-2 py-1 rounded text-sm font-medium"
                      :class="passed ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'">
                  {{ device }}
                </span>
              </div>
            </div>
          </div>
          <div v-else class="text-gray-500 italic flex-grow flex items-center">Awaiting subspace signals...</div>
        </div>

        <!-- UPS Card -->
        <div class="bg-gray-100 dark:bg-gray-800 p-6 rounded-2xl shadow-md transition-colors flex flex-col">
          <h2 class="text-2xl font-bold mb-4 border-b border-gray-300 dark:border-gray-700 pb-2">🔌 Power (UPS)</h2>
          <div v-if="upsData" class="space-y-3 flex-grow text-gray-800 dark:text-gray-200">
            <p><strong class="font-semibold">Status:</strong> {{ upsData.data.load.status }}</p>
            <p><strong class="font-semibold">Battery:</strong> {{ upsData.data.battery.charge_percent }}% ({{ Math.floor(upsData.data.battery.runtime_seconds / 60) }}m runtime)</p>
            <p><strong class="font-semibold">Load:</strong> {{ upsData.data.load.realpower_watts }}W ({{ upsData.data.load.load_percent }}%)</p>
          </div>
          <div v-else class="text-gray-500 italic flex-grow flex items-center">Awaiting subspace signals...</div>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="p-6 shrink-0 text-center border-t border-gray-200 dark:border-gray-800 transition-colors">
      <div class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-6">
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
          &copy; {{ currentYear }} 
          <a href="https://www.deepdivedylan.io" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:underline">
            Deep Dive Dylan
          </a> 
          All rights reserved.
        </p>
        <a href="https://github.com/deepdivedylan/rivest-site" target="_blank" rel="noopener noreferrer" aria-label="GitHub Repository" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
          <!-- Inverting the GitHub icon in dark mode so it remains visible -->
          <img src="/images/github.svg" alt="GitHub" class="w-6 h-6 dark:invert" />
        </a>
      </div>
    </footer>
  </div>
</template>