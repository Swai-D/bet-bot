<template>
  <div class="min-h-screen bg-gray-100 py-6 flex flex-col justify-center sm:py-12">
    <div class="relative py-3 sm:max-w-xl sm:mx-auto">
      <div class="relative px-4 py-10 bg-white shadow-lg sm:rounded-3xl sm:p-20">
        <div class="max-w-md mx-auto">
          <div class="divide-y divide-gray-200">
            <div class="py-8 text-base leading-6 space-y-4 text-gray-700 sm:text-lg sm:leading-7">
              <h1 class="text-2xl font-bold mb-8 text-center text-gray-900">
                BetPawa Automation Dashboard
              </h1>

              <!-- Status Section -->
              <div class="bg-gray-50 p-4 rounded-lg mb-6">
                <h2 class="text-lg font-semibold mb-4">System Status</h2>
                <div class="space-y-2">
                  <div class="flex justify-between">
                    <span>Session Status:</span>
                    <span :class="status.session_valid ? 'text-green-600' : 'text-red-600'">
                      {{ status.session_valid ? 'Valid' : 'Invalid' }}
                    </span>
                  </div>
                  <div class="flex justify-between">
                    <span>API Requests Remaining:</span>
                    <span>{{ status.remaining_requests }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span>Last Run:</span>
                    <span>{{ status.last_run || 'Never' }}</span>
                  </div>
                </div>
              </div>

              <!-- Control Section -->
              <div class="space-y-4">
                <button
                  @click="startAutomation"
                  :disabled="isRunning"
                  class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                >
                  {{ isRunning ? 'Running...' : 'Start Automation' }}
                </button>
              </div>

              <!-- Results Section -->
              <div v-if="results.length > 0" class="mt-8">
                <h2 class="text-lg font-semibold mb-4">Latest Results</h2>
                <div class="space-y-4">
                  <div
                    v-for="(result, index) in results"
                    :key="index"
                    class="bg-white p-4 rounded-lg border border-gray-200"
                  >
                    <div class="flex justify-between items-start">
                      <div>
                        <h3 class="font-medium">{{ result.match }}</h3>
                        <p class="text-sm text-gray-600">
                          Selection: {{ result.selection }} @ {{ result.odds }}
                        </p>
                        <p class="text-sm text-gray-600">
                          Stake: {{ result.stake }}
                        </p>
                      </div>
                      <div class="text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="result.score >= 3 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                        >
                          Score: {{ result.score }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Error Message -->
              <div v-if="error" class="mt-4 p-4 bg-red-50 rounded-lg">
                <p class="text-sm text-red-600">{{ error }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'AutomationDashboard',
  data() {
    return {
      status: {
        session_valid: false,
        remaining_requests: 0,
        last_run: null
      },
      results: [],
      isRunning: false,
      error: null
    };
  },
  mounted() {
    this.fetchStatus();
  },
  methods: {
    async fetchStatus() {
      try {
        const response = await axios.get('/api/automation/status');
        this.status = response.data;
      } catch (error) {
        this.error = 'Failed to fetch status';
        console.error('Status fetch error:', error);
      }
    },
    async startAutomation() {
      this.isRunning = true;
      this.error = null;
      this.results = [];

      try {
        const response = await axios.post('/api/automation/start');
        if (response.data.success) {
          this.results = response.data.results;
        } else {
          this.error = response.data.message;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to start automation';
        console.error('Automation error:', error);
      } finally {
        this.isRunning = false;
        this.fetchStatus();
      }
    }
  }
};
</script> 