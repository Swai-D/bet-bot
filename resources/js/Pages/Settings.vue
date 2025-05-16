<template>
    <Head title="Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-white leading-tight">Settings</h2>
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

                <div class="bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-white">
                        <form @submit.prevent="saveSettings" class="space-y-6">
                            <!-- Minimum Odds -->
                            <div>
                                <label for="min_odds" class="block text-sm font-medium text-gray-300">Minimum Odds</label>
                                <input
                                    type="number"
                                    id="min_odds"
                                    v-model="form.min_odds"
                                    step="0.01"
                                    min="1.01"
                                    max="100"
                                    class="mt-1 block w-full rounded-md border-gray-600 bg-gray-700 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    :class="{ 'border-red-500': form.errors.min_odds }"
                                />
                                <p class="mt-1 text-sm text-gray-400">Minimum odds required for predictions (1.01 - 100)</p>
                                <p v-if="form.errors.min_odds" class="mt-1 text-sm text-red-500">{{ form.errors.min_odds }}</p>
                            </div>

                            <!-- Auto Select Count -->
                            <div>
                                <label for="auto_select_count" class="block text-sm font-medium text-gray-300">Auto Select Count</label>
                                <input
                                    type="number"
                                    id="auto_select_count"
                                    v-model="form.auto_select_count"
                                    min="1"
                                    max="10"
                                    class="mt-1 block w-full rounded-md border-gray-600 bg-gray-700 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    :class="{ 'border-red-500': form.errors.auto_select_count }"
                                />
                                <p class="mt-1 text-sm text-gray-400">Number of predictions to auto-select (1 - 10)</p>
                                <p v-if="form.errors.auto_select_count" class="mt-1 text-sm text-red-500">{{ form.errors.auto_select_count }}</p>
                            </div>

                            <!-- Bet Amount -->
                            <div>
                                <label for="bet_amount" class="block text-sm font-medium text-gray-300">Bet Amount (TZS)</label>
                                <input
                                    type="number"
                                    id="bet_amount"
                                    v-model="form.bet_amount"
                                    min="100"
                                    max="1000000"
                                    step="100"
                                    class="mt-1 block w-full rounded-md border-gray-600 bg-gray-700 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    :class="{ 'border-red-500': form.errors.bet_amount }"
                                />
                                <p class="mt-1 text-sm text-gray-400">Amount to bet per prediction (100 - 1,000,000 TZS)</p>
                                <p v-if="form.errors.bet_amount" class="mt-1 text-sm text-red-500">{{ form.errors.bet_amount }}</p>
                            </div>

                            <!-- Selection Mode -->
                            <div>
                                <label for="selection_mode" class="block text-sm font-medium text-gray-300">Selection Mode</label>
                                <select
                                    id="selection_mode"
                                    v-model="form.selection_mode"
                                    class="mt-1 block w-full rounded-md border-gray-600 bg-gray-700 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    :class="{ 'border-red-500': form.errors.selection_mode }"
                                >
                                    <option value="manual">Manual</option>
                                    <option value="auto">Automatic</option>
                                </select>
                                <p class="mt-1 text-sm text-gray-400">How predictions should be selected</p>
                                <p v-if="form.errors.selection_mode" class="mt-1 text-sm text-red-500">{{ form.errors.selection_mode }}</p>
                            </div>

                            <!-- Auto Run Scraper -->
                            <div>
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.auto_run_scraper"
                                        class="rounded border-gray-600 bg-gray-700 text-blue-500 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-300">Auto Run Scraper</span>
                                </label>
                                <p class="mt-1 text-sm text-gray-400">Automatically run the scraper at scheduled time</p>
                            </div>

                            <!-- Scraper Time -->
                            <div v-if="form.auto_run_scraper">
                                <label for="scraper_time" class="block text-sm font-medium text-gray-300">Scraper Time</label>
                                <input
                                    type="time"
                                    id="scraper_time"
                                    v-model="form.scraper_time"
                                    class="mt-1 block w-full rounded-md border-gray-600 bg-gray-700 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    :class="{ 'border-red-500': form.errors.scraper_time }"
                                />
                                <p class="mt-1 text-sm text-gray-400">Time to run the scraper automatically</p>
                                <p v-if="form.errors.scraper_time" class="mt-1 text-sm text-red-500">{{ form.errors.scraper_time }}</p>
                            </div>

                            <!-- Auto Place Bets -->
                            <div>
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.auto_place_bets"
                                        class="rounded border-gray-600 bg-gray-700 text-blue-500 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-300">Auto Place Bets</span>
                                </label>
                                <p class="mt-1 text-sm text-gray-400">Automatically place bets for selected predictions</p>
                            </div>

                            <!-- Enable Notifications -->
                            <div>
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.enable_notifications"
                                        class="rounded border-gray-600 bg-gray-700 text-blue-500 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-300">Enable Notifications</span>
                                </label>
                                <p class="mt-1 text-sm text-gray-400">Receive notifications for important events</p>
                            </div>

                            <!-- Submit Button -->
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
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    settings: Object
});

const form = useForm({
    min_odds: props.settings?.min_odds ?? 2.00,
    auto_select_count: props.settings?.auto_select_count ?? 3,
    bet_amount: props.settings?.bet_amount ?? 1000,
    selection_mode: props.settings?.selection_mode ?? 'manual',
    auto_run_scraper: props.settings?.auto_run_scraper ?? false,
    scraper_time: props.settings?.scraper_time ?? '09:00',
    auto_place_bets: props.settings?.auto_place_bets ?? false,
    enable_notifications: props.settings?.enable_notifications ?? false
});

const showSuccess = ref(false);
const showError = ref(false);
const errorMessage = ref('');

// Reset scraper time when auto_run_scraper is disabled
watch(() => form.auto_run_scraper, (newValue) => {
    if (!newValue) {
        form.scraper_time = '09:00';
    }
});

const validateForm = () => {
    const errors = [];

    if (form.min_odds < 1.01 || form.min_odds > 100) {
        errors.push('Minimum odds must be between 1.01 and 100');
    }

    if (form.auto_select_count < 1 || form.auto_select_count > 10) {
        errors.push('Auto select count must be between 1 and 10');
    }

    if (form.bet_amount < 100 || form.bet_amount > 1000000) {
        errors.push('Bet amount must be between 100 and 1,000,000 TZS');
    }

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
</script> 