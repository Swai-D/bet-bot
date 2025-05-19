<template>
  <div class="bg-gray-800 rounded-lg shadow-lg p-6">
    <!-- Strategy Header -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-white mb-2">Betting Strategy</h2>
      <p class="text-gray-300">Configure and manage your automated betting strategy</p>
    </div>

    <!-- Strategy Settings -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
      <!-- Basic Settings -->
      <div class="bg-gray-700 p-4 rounded-lg">
        <h3 class="text-lg font-semibold text-white mb-4">Basic Settings</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Minimum Odds</label>
            <input 
              type="number" 
              v-model="strategy.minOdds"
              step="0.1"
              class="w-full rounded-md bg-gray-600 border-gray-500 text-white focus:border-blue-500 focus:ring-blue-500"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Maximum Odds</label>
            <input 
              type="number" 
              v-model="strategy.maxOdds"
              step="0.1"
              class="w-full rounded-md bg-gray-600 border-gray-500 text-white focus:border-blue-500 focus:ring-blue-500"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Base Stake (TZS)</label>
            <input 
              type="number" 
              v-model="strategy.baseStake"
              class="w-full rounded-md bg-gray-600 border-gray-500 text-white focus:border-blue-500 focus:ring-blue-500"
            >
          </div>
        </div>
      </div>

      <!-- Advanced Settings -->
      <div class="bg-gray-700 p-4 rounded-lg">
        <h3 class="text-lg font-semibold text-white mb-4">Advanced Settings</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Confidence Threshold</label>
            <select 
              v-model="strategy.confidenceThreshold"
              class="w-full rounded-md bg-gray-600 border-gray-500 text-white focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="high">High Only</option>
              <option value="medium">Medium and Above</option>
              <option value="low">All Predictions</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Betting Types</label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="strategy.betTypes.homeWin"
                  class="rounded bg-gray-600 border-gray-500 text-blue-500 focus:ring-blue-500"
                >
                <span class="ml-2 text-gray-300">Home Win (1)</span>
              </label>
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="strategy.betTypes.draw"
                  class="rounded bg-gray-600 border-gray-500 text-blue-500 focus:ring-blue-500"
                >
                <span class="ml-2 text-gray-300">Draw (X)</span>
              </label>
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="strategy.betTypes.awayWin"
                  class="rounded bg-gray-600 border-gray-500 text-blue-500 focus:ring-blue-500"
                >
                <span class="ml-2 text-gray-300">Away Win (2)</span>
              </label>
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="strategy.betTypes.over2_5"
                  class="rounded bg-gray-600 border-gray-500 text-blue-500 focus:ring-blue-500"
                >
                <span class="ml-2 text-gray-300">Over 2.5 Goals</span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Strategy Status -->
    <div class="bg-gray-700 p-4 rounded-lg mb-6">
      <h3 class="text-lg font-semibold text-white mb-4">Strategy Status</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gray-600 p-3 rounded-lg">
          <p class="text-sm text-gray-300">Active Bets</p>
          <p class="text-xl font-bold text-white">{{ stats.activeBets }}</p>
        </div>
        <div class="bg-gray-600 p-3 rounded-lg">
          <p class="text-sm text-gray-300">Success Rate</p>
          <p class="text-xl font-bold text-white">{{ stats.successRate }}%</p>
        </div>
        <div class="bg-gray-600 p-3 rounded-lg">
          <p class="text-sm text-gray-300">Total Profit</p>
          <p class="text-xl font-bold" :class="stats.totalProfit >= 0 ? 'text-green-500' : 'text-red-500'">
            {{ formatCurrency(stats.totalProfit) }}
          </p>
        </div>
        <div class="bg-gray-600 p-3 rounded-lg">
          <p class="text-sm text-gray-300">Next Bet</p>
          <p class="text-xl font-bold text-white">{{ stats.nextBetTime || 'No pending bets' }}</p>
        </div>
      </div>
    </div>

    <!-- Control Buttons -->
    <div class="flex space-x-4">
      <button 
        @click="saveStrategy"
        class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
      >
        Save Strategy
      </button>
      <button 
        @click="toggleAutomation"
        :class="[
          'flex-1 px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2',
          isAutomationActive 
            ? 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500' 
            : 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500'
        ]"
      >
        {{ isAutomationActive ? 'Stop Automation' : 'Start Automation' }}
      </button>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'BettingStrategy',
  data() {
    return {
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
      stats: {
        activeBets: 0,
        successRate: 0,
        totalProfit: 0,
        nextBetTime: null
      },
      isAutomationActive: false
    };
  },
  methods: {
    async saveStrategy() {
      try {
        await axios.post('/api/betting/strategy', this.strategy);
        this.$emit('strategy-updated');
      } catch (error) {
        console.error('Failed to save strategy:', error);
      }
    },
    async toggleAutomation() {
      try {
        const action = this.isAutomationActive ? 'stop' : 'start';
        await axios.post(`/api/betting/automation/${action}`);
        this.isAutomationActive = !this.isAutomationActive;
      } catch (error) {
        console.error('Failed to toggle automation:', error);
      }
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat('en-TZ', {
        style: 'currency',
        currency: 'TZS'
      }).format(amount);
    },
    async fetchStats() {
      try {
        const response = await axios.get('/api/betting/stats');
        this.stats = response.data;
      } catch (error) {
        console.error('Failed to fetch stats:', error);
      }
    }
  },
  mounted() {
    this.fetchStats();
    // Set up periodic stats refresh
    setInterval(this.fetchStats, 30000);
  }
};
</script> 