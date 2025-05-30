<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';
import PredictionsList from '@/Components/PredictionsList.vue';

// Props from backend
const props = defineProps({
    predictions: {
        type: Array,
        required: true
    },
    bettingHistory: {
        type: Array,
        required: true
    },
    settings: {
        type: Object,
        required: true
    }
});

// State
const botStatus = ref(false);
const stats = ref({
    scrapedMatches: props.predictions.length,
    selectedMatches: props.predictions.filter(p => p.selected).length
});

// Settings
const settings = ref({
    minOdds: props.settings?.min_odds || 2.00,
    autoSelectCount: props.settings?.auto_select_count || 3,
    betAmount: props.settings?.bet_amount || 1000,
    selectionMode: props.settings?.selection_mode || 'manual',
    autoRunScraper: props.settings?.auto_run_scraper || false,
    scraperTime: props.settings?.scraper_time || '09:00',
    autoPlaceBets: props.settings?.auto_place_bets || false,
    enableNotifications: props.settings?.enable_notifications || false
});

// Computed
const selectedBets = computed(() => {
    return props.predictions
        .filter(match => match.selected)
        .map(match => ({
            id: match.id,
            match: match.teams,
            tip: match.tips,
            odds: match.odds || 'Unknown',
            stake: settings.value.betAmount
        }));
});

// Methods
const toggleBot = async () => {
    if (!confirm(`Are you sure you want to ${botStatus.value ? 'stop' : 'start'} the bot?`)) {
        return;
    }

    try {
        const response = await axios.post('/api/automation/toggle');
        if (response.data.success) {
            botStatus.value = response.data.status;
            console.log('Bot status toggled:', botStatus.value);
            
            // Show success message
            alert(`Bot ${botStatus.value ? 'started' : 'stopped'} successfully!`);
        } else {
            alert('Failed to toggle bot: ' + response.data.message);
        }
    } catch (error) {
        console.error('Failed to toggle bot:', error);
        alert('Failed to toggle bot. Please try again.');
    }
};

const runScraper = async () => {
    if (!confirm('Are you sure you want to run the scraper now?')) {
        return;
    }

    try {
        const response = await axios.post('/dashboard/scraper/run');
        console.log('Scraper response:', response.data);
        alert('Scraper started successfully!');
        // Refresh predictions after scraping
        window.location.reload();
    } catch (error) {
        console.error('Failed to run scraper:', error);
        alert('Failed to run scraper. Please try again.');
    }
};

const placeBets = async () => {
    if (!selectedBets.value.length) {
        alert('Please select at least one bet to place.');
        return;
    }

    if (!confirm(`Are you sure you want to place ${selectedBets.value.length} bets?`)) {
        return;
    }

    try {
        const response = await axios.post('/dashboard/bets/place', {
            matches: selectedBets.value
        });
        console.log('Place bets response:', response.data);
        alert('Bets placed successfully!');
        // Refresh predictions after placing bets
        window.location.reload();
    } catch (error) {
        console.error('Failed to place bets:', error);
        alert('Failed to place bets. Please try again.');
    }
};

const stopBot = async () => {
    try {
        const response = await axios.post('/api/automation/stop');
        if (response.data.success) {
    botStatus.value = false;
    console.log('Bot stopped');
        } else {
            alert('Failed to stop bot: ' + response.data.message);
        }
    } catch (error) {
        console.error('Failed to stop bot:', error);
        alert('Failed to stop bot. Please try again.');
    }
};

const placeAllBets = async () => {
    try {
        const response = await axios.post('/dashboard/bets/place', {
            matches: selectedBets.value
        });
        console.log('Place all bets response:', response.data);
        // Refresh predictions after placing bets
        window.location.reload();
    } catch (error) {
        console.error('Failed to place all bets:', error);
        alert('Failed to place all bets. Please try again.');
    }
};

const updateSettings = async () => {
    try {
        const response = await axios.post('/dashboard/settings/update', settings.value);
        console.log('Settings update response:', response.data);
        
        // Show success message
        const successMessage = document.createElement('div');
        successMessage.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg';
        successMessage.textContent = 'Settings updated successfully!';
        document.body.appendChild(successMessage);
        
        // Remove message after 3 seconds
        setTimeout(() => {
            successMessage.remove();
        }, 3000);
    } catch (error) {
        console.error('Failed to update settings:', error);
        alert('Failed to update settings. Please try again.');
    }
};

// Watch settings changes with debounce
let settingsTimeout;
watch(settings, () => {
    clearTimeout(settingsTimeout);
    settingsTimeout = setTimeout(() => {
        updateSettings();
    }, 1000);
}, { deep: true });

// Add updatePredictions method
const updatePredictions = (newPredictions) => {
    predictions.value = newPredictions;
};

// Add status check on mount and every 30 seconds
onMounted(async () => {
    await checkBotStatus();
    setInterval(checkBotStatus, 30000);
});

const checkBotStatus = async () => {
    try {
        const response = await axios.get('/api/automation/status');
        if (response.data.success) {
            botStatus.value = response.data.bot_status;
        }
    } catch (error) {
        console.error('Failed to fetch bot status:', error);
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-300 leading-tight">Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Welcome Panel -->
                <div class="bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-white">
                        <h3 class="text-2xl font-bold mb-4">Welcome to Betting Bot</h3>
                        <p class="text-gray-300">{{ new Date().toLocaleDateString() }}</p>
                    </div>
                </div>

                <!-- Current Status -->
                <div class="bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-4 text-white">Current Status</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-gray-700 p-4 rounded-lg">
                                <p class="text-gray-300">Matches Scraped Today</p>
                                <p class="text-2xl font-bold text-white">{{ stats.scrapedMatches }}</p>
                            </div>
                            <div class="bg-gray-700 p-4 rounded-lg">
                                <p class="text-gray-300">Selected Matches</p>
                                <p class="text-2xl font-bold text-white">{{ stats.selectedMatches }}</p>
                            </div>
                            <div class="bg-gray-700 p-4 rounded-lg">
                                <p class="text-gray-300">Bot Status</p>
                                <div class="flex items-center mt-2">
                                    <button 
                                        @click="toggleBot"
                                        :class="[
                                            'px-4 py-2 rounded-full text-white font-semibold transition-colors duration-200',
                                            botStatus ? 'bg-green-500 hover:bg-green-600' : 'bg-red-500 hover:bg-red-600'
                                        ]"
                                    >
                                        {{ botStatus ? 'ON' : 'OFF' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-4 text-white">Quick Actions</h3>
                        <div class="flex flex-wrap gap-4">
                            <button 
                                @click="runScraper"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition-colors duration-200 flex items-center"
                            >
                                <span class="mr-2">🔄</span>
                                Run Scraper
                            </button>
                            <button 
                                @click="placeBets"
                                class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition-colors duration-200 flex items-center"
                            >
                                <span class="mr-2">💰</span>
                                Place Bets
                            </button>
                            <button 
                                @click="stopBot"
                                class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg transition-colors duration-200 flex items-center"
                            >
                                <span class="mr-2">⛔</span>
                                Stop Bot
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Predictions List -->
                <div class="bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-4 text-white">Predictions</h3>
                        <PredictionsList 
                            :predictions="predictions" 
                            @update-predictions="updatePredictions"
                        />
                    </div>
                </div>

                <!-- Betting Control Panel -->
                <div class="bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold text-white">Quick Settings</h3>
                            <a 
                                href="/settings" 
                                class="text-sm text-blue-400 hover:text-blue-300 flex items-center"
                            >
                                <span>Advanced Settings</span>
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                        
                        <!-- Essential Settings -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-300">Selection Mode</label>
                                <select 
                                    v-model="settings.selectionMode"
                                    class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-white focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                                >
                                    <option value="auto">Automatic</option>
                                    <option value="manual">Manual</option>
                                </select>
                                <p class="mt-1 text-sm text-gray-400">
                                    {{ settings.selectionMode === 'auto' 
                                        ? 'Bot will automatically select matches based on criteria' 
                                        : 'You will manually select matches for betting' }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300">Bet Amount (TZS)</label>
                                <input 
                                    type="number" 
                                    v-model="settings.betAmount"
                                    class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-white focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Betting History -->
                <div class="bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-4 text-white">Betting History</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-700">
                                <thead class="bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Match</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Tip</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Outcome</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Stake</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Win/Loss</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-gray-800 divide-y divide-gray-700">
                                    <tr v-for="log in bettingHistory" :key="log.id" class="text-gray-300">
                                        <td class="px-6 py-4 whitespace-nowrap">{{ log.date }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ log.match }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ log.tip }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span 
                                                :class="{
                                                    'px-2 py-1 rounded-full text-xs font-semibold': true,
                                                    'bg-green-900 text-green-300': log.outcome === 'W',
                                                    'bg-red-900 text-red-300': log.outcome === 'L',
                                                    'bg-yellow-900 text-yellow-300': log.outcome === 'P'
                                                }"
                                            >
                                                {{ log.outcome }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ log.stake }} TZS</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ log.win_loss }} TZS</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
