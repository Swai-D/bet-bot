<template>
    <Head title="Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-300 leading-tight">Settings</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Success Message -->
                <div v-if="showSuccess" class="mb-4 bg-green-500 text-white px-4 py-2 rounded-md">
                    Settings saved successfully!
                </div>

                <!-- Error Message -->
                <div v-if="showError" class="mb-4 bg-red-500 text-white px-4 py-2 rounded-md">
                    {{ errorMessage }}
                </div>

                <form @submit.prevent="saveSettings" class="space-y-6">
                    <!-- Automation Settings -->
                <div class="bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-300">
                            <h3 class="text-lg font-medium mb-4">Automation Settings</h3>

                            <!-- Auto Run Scraper -->
                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.auto_run_scraper"
                                        class="rounded border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <span class="ml-2">Auto Run Scraper</span>
                                </label>
                                <p class="mt-1 text-sm text-gray-400">Automatically run scraper at scheduled time</p>
                            </div>

                            <!-- Scraper Time -->
                            <div v-if="form.auto_run_scraper" class="mb-4">
                                <label for="scraper_time" class="block text-sm font-medium text-gray-300">Scraper Time</label>
                                <input
                                    type="time"
                                    id="scraper_time"
                                    v-model="form.scraper_time"
                                    class="mt-1 block w-full rounded-md border-gray-600 bg-gray-700 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <p class="mt-1 text-sm text-gray-400">Time to run scraper automatically</p>
                            </div>

                            <!-- Auto Place Bets -->
                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.auto_place_bets"
                                        class="rounded border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <span class="ml-2">Auto Place Bets</span>
                                </label>
                                <p class="mt-1 text-sm text-gray-400">Automatically place bets based on predictions</p>
                            </div>
                        </div>
                    </div>

                    <!-- Betting Strategy -->
                    <div class="bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-300">
                            <h3 class="text-lg font-medium mb-4">Betting Strategy</h3>
                            
                            <!-- Confidence Threshold -->
                            <div class="mb-4">
                                <label for="confidence_threshold" class="block text-sm font-medium text-gray-300">Confidence Threshold</label>
                                <select
                                    id="confidence_threshold"
                                    v-model="form.confidence_threshold"
                                    class="mt-1 block w-full rounded-md border-gray-600 bg-gray-700 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                                <p class="mt-1 text-sm text-gray-400">Minimum confidence level for predictions</p>
                            </div>

                            <!-- Bet Types -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-300 mb-2">Bet Types</label>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.bet_types.homeWin"
                                            class="rounded border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                        <span class="ml-2">Home Win</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.bet_types.draw"
                                            class="rounded border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                        <span class="ml-2">Draw</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.bet_types.awayWin"
                                            class="rounded border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                        <span class="ml-2">Away Win</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.bet_types.over2_5"
                                            class="rounded border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                        <span class="ml-2">Over 2.5 Goals</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div class="bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-300">
                            <h3 class="text-lg font-medium mb-4">Notification Settings</h3>

                            <!-- Enable Notifications -->
                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.enable_notifications"
                                        class="rounded border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <span class="ml-2">Enable Notifications</span>
                                </label>
                                <p class="mt-1 text-sm text-gray-400">Receive notifications for important events</p>
                            </div>
                        </div>
                            </div>

                    <!-- Save Button -->
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    :disabled="form.processing"
                                >
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ form.processing ? 'Saving...' : 'Save Settings' }}
                                </button>
                            </div>
                        </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    settings: Object
});

const form = useForm({
    auto_run_scraper: props.settings?.auto_run_scraper ?? false,
    scraper_time: props.settings?.scraper_time ?? '09:00',
    auto_place_bets: props.settings?.auto_place_bets ?? false,
    confidence_threshold: props.settings?.confidence_threshold ?? 'medium',
    bet_types: {
        homeWin: props.settings?.bet_types?.homeWin ?? true,
        draw: props.settings?.bet_types?.draw ?? true,
        awayWin: props.settings?.bet_types?.awayWin ?? true,
        over2_5: props.settings?.bet_types?.over2_5 ?? true
    },
    enable_notifications: props.settings?.enable_notifications ?? false
});

const showSuccess = ref(false);
const showError = ref(false);
const errorMessage = ref('');

const status = ref({
    session_valid: false,
    remaining_requests: 0,
    last_run: null
});

const strategy = ref({
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
});

const results = ref([]);
const isRunning = ref(false);
const error = ref(null);

// Reset scraper time when auto_run_scraper is disabled
watch(() => form.auto_run_scraper, (newValue) => {
    if (!newValue) {
        form.scraper_time = '09:00';
    }
});

const validateForm = () => {
    const errors = [];

    if (form.auto_run_scraper && !form.scraper_time) {
        errors.push('Scraper time is required when auto run scraper is enabled');
    }

    return errors;
};

const saveSettings = () => {
    const errors = validateForm();
    if (errors.length > 0) {
        showError.value = true;
        errorMessage.value = errors[0];
        setTimeout(() => {
            showError.value = false;
            errorMessage.value = '';
        }, 3000);
        return;
    }

    form.post(route('settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess.value = true;
            setTimeout(() => {
                showSuccess.value = false;
            }, 3000);
        },
        onError: (errors) => {
            showError.value = true;
            errorMessage.value = Object.values(errors)[0];
            setTimeout(() => {
                showError.value = false;
                errorMessage.value = '';
            }, 3000);
        }
    });
};

onMounted(() => {
    fetchStatus();
    fetchStrategy();
});

const fetchStatus = async () => {
    try {
        const response = await axios.get('/api/automation/status');
        status.value = response.data;
    } catch (error) {
        error.value = 'Failed to fetch status';
        console.error('Status fetch error:', error);
    }
};

const fetchStrategy = async () => {
    try {
        const response = await axios.get('/api/betting/strategy');
        strategy.value = response.data.strategy;
    } catch (error) {
        console.error('Strategy fetch error:', error);
    }
};

const saveStrategy = async () => {
    try {
        await axios.post('/api/betting/strategy', strategy.value);
        error.value = null;
    } catch (error) {
        error.value = 'Failed to save strategy';
        console.error('Strategy save error:', error);
    }
};

const startAutomation = async () => {
    isRunning.value = true;
    error.value = null;
    results.value = [];

    try {
        const response = await axios.post('/api/automation/start');
        if (response.data.success) {
            results.value = response.data.results;
        } else {
            error.value = response.data.message;
        }
    } catch (error) {
        error.value = error.response?.data?.message || 'Failed to start automation';
        console.error('Automation error:', error);
    } finally {
        isRunning.value = false;
        fetchStatus();
    }
};
</script> 