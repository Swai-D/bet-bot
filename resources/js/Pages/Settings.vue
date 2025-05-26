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
                    <!-- Basic Settings -->
                    <div class="bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-300 mb-4">Basic Settings</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300">Minimum Odds</label>
                                    <input 
                                        type="number" 
                                        v-model="form.min_odds"
                                        step="0.01"
                                        class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-white focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                                    >
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-300">Auto Select Count</label>
                                    <input 
                                        type="number" 
                                        v-model="form.auto_select_count"
                                        class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-white focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Automation Settings -->
                    <div class="bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-300 mb-4">Automation Settings</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            v-model="form.auto_run_scraper"
                                            class="rounded border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        >
                                        <span class="ml-2 text-gray-300">Auto Run Scraper</span>
                                    </label>
                                    <p class="mt-1 text-sm text-gray-400">Automatically run scraper at scheduled time</p>
                                </div>

                                <div v-if="form.auto_run_scraper">
                                    <label class="block text-sm font-medium text-gray-300">Scraper Time</label>
                                    <input 
                                        type="time" 
                                        v-model="form.scraper_time"
                                        class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-white focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                                    >
                                </div>

                                <div>
                                    <label class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            v-model="form.auto_place_bets"
                                            class="rounded border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        >
                                        <span class="ml-2 text-gray-300">Auto Place Bets</span>
                                    </label>
                                    <p class="mt-1 text-sm text-gray-400">Automatically place bets for selected matches</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div class="bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-300 mb-4">Notification Settings</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            v-model="form.enable_notifications"
                                            class="rounded border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        >
                                        <span class="ml-2 text-gray-300">Enable Notifications</span>
                                    </label>
                                    <p class="mt-1 text-sm text-gray-400">Receive notifications for important events</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end">
                        <button 
                            type="submit"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition-colors duration-200"
                            :disabled="isSaving"
                        >
                            {{ isSaving ? 'Saving...' : 'Save Settings' }}
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