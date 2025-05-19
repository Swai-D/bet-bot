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

              <!-- Betting Strategy Section -->
              <div class="bg-gray-50 p-4 rounded-lg mb-6">
                <h2 class="text-lg font-semibold mb-4">Betting Strategy</h2>
                <div class="space-y-4">
                  <!-- Odds Range -->
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Min Odds</label>
                      <input 
                        type="number" 
                        v-model="strategy.minOdds"
                        step="0.1"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      >
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Max Odds</label>
                      <input 
                        type="number" 
                        v-model="strategy.maxOdds"
                        step="0.1"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      >
                    </div>
                  </div>

                  <!-- Base Stake -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Base Stake (TZS)</label>
                    <input 
                      type="number" 
                      v-model="strategy.baseStake"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                  </div>

                  <!-- Confidence Threshold -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Confidence Threshold</label>
                    <select 
                      v-model="strategy.confidenceThreshold"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                      <option value="high">High Only</option>
                      <option value="medium">Medium and Above</option>
                      <option value="low">All Predictions</option>
                    </select>
                  </div>

                  <!-- Bet Types -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bet Types</label>
                    <div class="grid grid-cols-2 gap-2">
                      <label class="flex items-center">
                        <input 
                          type="checkbox" 
                          v-model="strategy.betTypes.homeWin"
                          class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">Home Win (1)</span>
                      </label>
                      <label class="flex items-center">
                        <input 
                          type="checkbox" 
                          v-model="strategy.betTypes.draw"
                          class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">Draw (X)</span>
                      </label>
                      <label class="flex items-center">
                        <input 
                          type="checkbox" 
                          v-model="strategy.betTypes.awayWin"
                          class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">Away Win (2)</span>
                      </label>
                      <label class="flex items-center">
                        <input 
                          type="checkbox" 
                          v-model="strategy.betTypes.over2_5"
                          class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">Over 2.5</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Control Section -->
              <div class="space-y-4">
                <button
                  @click="saveStrategy"
                  class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  Save Strategy
                </button>
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
      strategy: {
        minOdds: 1.5,
        maxOdds: 3.0,
        baseStake: 1000,
        confidenceThreshold: 'medium',
        betTypes: {
          homeWin: true,
          draw: true,
          awayWin: true,
          over2_5: true
        }
      },
      results: [],
      isRunning: false,
      error: null
    };
  },
  mounted() {
    this.fetchStatus();
    this.fetchStrategy();
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
    async fetchStrategy() {
      try {
        const response = await axios.get('/api/betting/strategy');
        this.strategy = response.data.strategy;
      } catch (error) {
        console.error('Strategy fetch error:', error);
      }
    },
    async saveStrategy() {
      try {
        await axios.post('/api/betting/strategy', this.strategy);
        this.error = null;
      } catch (error) {
        this.error = 'Failed to save strategy';
        console.error('Strategy save error:', error);
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