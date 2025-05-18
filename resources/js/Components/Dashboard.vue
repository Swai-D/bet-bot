<template>
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-white mb-2">Betting Predictions</h2>
            <p class="text-gray-300">View and filter your betting predictions</p>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <button 
                @click="runScraper"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex items-center justify-center space-x-2"
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

        <!-- Loading Overlay -->
        <div v-if="isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-gray-800 p-6 rounded-lg shadow-xl text-center">
                <svg class="animate-spin h-12 w-12 text-blue-500 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-white text-lg">Fetching predictions...</p>
                <p class="text-gray-400 text-sm mt-2">This may take a few moments</p>
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
        <div v-if="showSuccess" class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2">
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
const showSuccess = ref(false);

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
        // Show error message to user
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
});
</script> 