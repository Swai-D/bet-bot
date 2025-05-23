<template>
    <div class="card">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-light-text dark:text-dark-text mb-2">Betting Predictions</h2>
            <p class="text-secondary dark:text-secondary-light">View and filter your betting predictions</p>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <button 
                @click="runScraper"
                class="btn-primary flex items-center justify-center space-x-2"
                :disabled="isLoading"
            >
                <!-- Loading Spinner -->
                <svg v-if="isLoading" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ isLoading ? 'Running Scraper...' : 'Run Scraper' }}</span>
            </button>
        </div>

        <!-- Betting Control Panel -->
        <div class="card mb-6">
            <h3 class="text-lg font-semibold text-light-text dark:text-dark-text mb-4">Betting Control Panel</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="label">Minimum Odds</label>
                    <input 
                        type="number" 
                        v-model="settings.minOdds"
                        step="0.1"
                        class="input"
                    >
                </div>
                <div>
                    <label class="label">Auto Select Matches</label>
                    <input 
                        type="number" 
                        v-model="settings.autoSelectCount"
                        class="input"
                    >
                </div>
                <div>
                    <label class="label">Bet Amount (TZS)</label>
                    <input 
                        type="number" 
                        v-model="settings.betAmount"
                        class="input"
                    >
                </div>
                <div>
                    <label class="label">Selection Mode</label>
                    <select 
                        v-model="settings.selectionMode"
                        class="input"
                    >
                        <option value="auto">Auto</option>
                        <option value="manual">Manual</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button 
                    @click="saveSettings"
                    class="btn-accent w-full"
                    :disabled="isSaving"
                >
                    {{ isSaving ? 'Saving...' : 'Save Settings' }}
                </button>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div v-if="isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="card text-center">
                <svg class="animate-spin h-12 w-12 text-primary mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-light-text dark:text-dark-text text-lg">Fetching predictions...</p>
                <p class="text-secondary dark:text-secondary-light text-sm mt-2">This may take a few moments</p>
            </div>
        </div>

        <!-- Predictions List -->
        <div :class="{ 'opacity-50 pointer-events-none': isLoading }">
            <PredictionsList 
                :predictions="predictions"
                @update-predictions="updatePredictions"
            />
        </div>

        <!-- Success Toast -->
        <div v-if="showSuccess" class="fixed bottom-4 right-4 bg-accent text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>Successfully updated predictions!</span>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import PredictionsList from './PredictionsList.vue';
import axios from 'axios';

const predictions = ref([]);
const isLoading = ref(false);
const isSaving = ref(false);
const showSuccess = ref(false);

const settings = ref({
    minOdds: 2.00,
    autoSelectCount: 3,
    betAmount: 1000,
    selectionMode: 'auto'
});

const fetchPredictions = async () => {
    try {
        const response = await axios.get('/api/predictions');
        if (response.data.success) {
            predictions.value = response.data.predictions;
        }
    } catch (error) {
        console.error('Failed to fetch predictions:', error);
    }
};

const fetchSettings = async () => {
    try {
        const response = await axios.get('/api/settings');
        if (response.data.success) {
            settings.value = response.data.settings;
        }
    } catch (error) {
        console.error('Failed to fetch settings:', error);
    }
};

const saveSettings = async () => {
    isSaving.value = true;
    try {
        const response = await axios.post('/api/settings', settings.value);
        if (response.data.success) {
            showSuccess.value = true;
            setTimeout(() => {
                showSuccess.value = false;
            }, 3000);
        }
    } catch (error) {
        console.error('Failed to save settings:', error);
        alert('Failed to save settings. Please try again.');
    } finally {
        isSaving.value = false;
    }
};

const runScraper = async () => {
    isLoading.value = true;
    showSuccess.value = false;
    
    try {
        const response = await axios.post('/api/predictions/run-scraper');
        if (response.data.success) {
            predictions.value = response.data.predictions;
            showSuccess.value = true;
            setTimeout(() => {
                showSuccess.value = false;
            }, 3000);
        } else {
            throw new Error(response.data.message || 'Failed to run scraper');
        }
    } catch (error) {
        console.error('Failed to run scraper:', error);
        alert(error.response?.data?.message || error.message || 'Failed to run scraper. Please try again.');
    } finally {
        isLoading.value = false;
    }
};

const updatePredictions = (updatedPredictions) => {
    predictions.value = updatedPredictions;
};

onMounted(() => {
    fetchPredictions();
    fetchSettings();
});
</script> 