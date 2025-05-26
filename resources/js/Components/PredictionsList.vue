<template>
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-white mb-2">Betting Predictions</h2>
            <p class="text-gray-300">View and filter your betting predictions</p>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Date</label>
                <input 
                    type="date" 
                    v-model="filters.date"
                    class="w-full rounded-md bg-gray-700 border-gray-600 text-white focus:border-blue-500 focus:ring-blue-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Team</label>
                <input 
                    type="text" 
                    v-model="filters.team"
                    placeholder="Search by team..."
                    class="w-full rounded-md bg-gray-700 border-gray-600 text-white focus:border-blue-500 focus:ring-blue-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Tip Type</label>
                <select 
                    v-model="filters.tip"
                    class="w-full rounded-md bg-gray-700 border-gray-600 text-white focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="">All Tips</option>
                    <option value="1">Home Win</option>
                    <option value="X">Draw</option>
                    <option value="2">Away Win</option>
                    <option value="GG">Both Teams Score</option>
                    <option value="+2.5">Over 2.5 Goals</option>
                    <option value="-2.5">Under 2.5 Goals</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Odds Range</label>
                <select 
                    v-model="filters.oddsRange"
                    class="w-full rounded-md bg-gray-700 border-gray-600 text-white focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="">All Odds</option>
                    <option value="best">Best (≥ 2.5)</option>
                    <option value="moderate">Moderate (1.5 - 2.5)</option>
                    <option value="low">Low (< 1.5)</option>
                </select>
            </div>
        </div>

        <!-- View Toggle -->
        <div class="flex justify-end mb-4">
            <button 
                @click="toggleView"
                class="inline-flex items-center px-4 py-2 border border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-300 bg-gray-700 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <span v-if="isDetailedView">Switch to Compact View</span>
                <span v-else>Switch to Detailed View</span>
            </button>
        </div>

        <!-- Predictions List -->
        <div v-if="filteredPredictions.length > 0">
            <!-- Compact View -->
            <div v-if="!isDetailedView" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-1/12">Select</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-1/3">Match</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-1/6">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-1/6">Tip</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-1/6">Odds</th>
                        </tr>
                    </thead>
                    <tbody class="bg-gray-800 divide-y divide-gray-700">
                        <tr v-for="prediction in paginatedPredictions" :key="prediction.id" class="hover:bg-gray-700">
                            <td class="px-6 py-4 text-sm font-medium text-white">
                                <input 
                                    type="checkbox" 
                                    v-model="prediction.selected"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                >
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-white">
                                {{ prediction.match }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-300">
                                {{ formatDateTime(prediction.match_date, prediction.match_time) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-300">
                                {{ formatTip(prediction.tips) }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium" :class="getOddsColorClass(prediction.tips)">
                                {{ formatOdds(prediction.tips) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="flex items-center justify-between px-4 py-3 bg-gray-700 border-t border-gray-600 sm:px-6">
                    <div class="flex justify-between flex-1 sm:hidden">
                        <button 
                            @click="currentPage--" 
                            :disabled="currentPage === 1"
                            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-300 bg-gray-700 border border-gray-600 rounded-md hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Previous
                        </button>
                        <button 
                            @click="currentPage++" 
                            :disabled="currentPage === totalPages"
                            class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-300 bg-gray-700 border border-gray-600 rounded-md hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next
                        </button>
                    </div>
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-300">
                                Showing
                                <span class="font-medium">{{ paginationStart }}</span>
                                to
                                <span class="font-medium">{{ paginationEnd }}</span>
                                of
                                <span class="font-medium">{{ filteredPredictions.length }}</span>
                                results
                            </p>
                        </div>
                        <div>
                            <nav class="inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                <button 
                                    @click="currentPage--" 
                                    :disabled="currentPage === 1"
                                    class="relative inline-flex items-center px-2 py-2 text-gray-300 bg-gray-700 border border-gray-600 rounded-l-md hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span class="sr-only">Previous</span>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <button 
                                    v-for="page in displayedPages" 
                                    :key="page"
                                    @click="currentPage = page"
                                    :class="[
                                        currentPage === page 
                                            ? 'z-10 bg-blue-600 border-blue-600 text-white' 
                                            : 'bg-gray-700 border-gray-600 text-gray-300 hover:bg-gray-600',
                                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                                    ]"
                                >
                                    {{ page }}
                                </button>
                                <button 
                                    @click="currentPage++" 
                                    :disabled="currentPage === totalPages"
                                    class="relative inline-flex items-center px-2 py-2 text-gray-300 bg-gray-700 border border-gray-600 rounded-r-md hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span class="sr-only">Next</span>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed View -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="prediction in filteredPredictions" :key="prediction.id" 
                     class="bg-gray-700 rounded-lg border border-gray-600 shadow-sm p-4 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center space-x-3">
                            <input 
                                type="checkbox" 
                                v-model="prediction.selected"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            >
                            <h3 class="text-lg font-semibold text-white">{{ prediction.match }}</h3>
                        </div>
                        <span :class="getOddsColorClass(prediction.tips)" class="text-sm font-medium">
                            {{ formatOdds(prediction.tips) }}
                        </span>
                    </div>
                    <div class="space-y-2">
                        <p class="text-sm text-gray-300">
                            <span class="font-medium">Date:</span> {{ formatDateTime(prediction.match_date, prediction.match_time) }}
                        </p>
                        <p class="text-sm text-gray-300">
                            <span class="font-medium">Tip:</span> {{ formatTip(prediction.tips) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="mt-6 bg-gray-700 rounded-lg p-4">
                <h3 class="text-lg font-medium text-white mb-2">Summary</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-300">Total Predictions</p>
                        <p class="text-lg font-semibold text-white">{{ filteredPredictions.length }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-300">Selected Predictions</p>
                        <p class="text-lg font-semibold text-white">
                            {{ filteredPredictions.filter(p => p.selected).length }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-300">Total Odds</p>
                        <p class="text-lg font-semibold text-white">
                            {{ calculateTotalOdds() }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bet Placement Summary -->
            <div v-if="filteredPredictions.filter(p => p.selected).length > 0" class="mt-6 bg-gray-700 rounded-lg p-4">
                <h3 class="text-lg font-medium text-white mb-4">Bet Placement Summary</h3>
                <div class="space-y-4">
                    <!-- Selected Matches List -->
                    <div class="space-y-2">
                        <div v-for="prediction in filteredPredictions.filter(p => p.selected)" :key="prediction.id" 
                             class="bg-gray-800 rounded-lg p-3 flex justify-between items-center">
                            <div class="flex-1">
                                <h4 class="text-white font-medium">{{ prediction.match }}</h4>
                                <div class="text-sm text-gray-300 mt-1">
                                    <span>{{ formatDateTime(prediction.match_date, prediction.match_time) }}</span>
                                    <span class="mx-2">•</span>
                                    <span>{{ formatTip(prediction.tips) }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-lg font-semibold" :class="getOddsColorClass(prediction.tips)">
                                    {{ formatOdds(prediction.tips) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Bet Summary -->
                    <div class="border-t border-gray-600 pt-4 mt-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-300">Number of Selections</p>
                                <p class="text-lg font-semibold text-white">
                                    {{ filteredPredictions.filter(p => p.selected).length }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-300">Total Odds</p>
                                <p class="text-lg font-semibold text-white">
                                    {{ calculateTotalOdds() }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Potential Winnings Calculator -->
                    <div class="border-t border-gray-600 pt-4 mt-4">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Stake Amount (TZS)</label>
                                <input 
                                    type="number" 
                                    v-model="stakeAmount"
                                    min="0"
                                    step="1000"
                                    class="w-full rounded-md bg-gray-800 border-gray-600 text-white focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Enter stake amount"
                                >
                            </div>
                            <div class="bg-gray-800 rounded-lg p-3">
                                <p class="text-sm text-gray-300">Potential Winnings</p>
                                <p class="text-xl font-bold text-green-400">
                                    {{ calculatePotentialWinnings() }} TZS
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- No Results -->
        <div v-else class="text-center py-12">
            <div class="text-gray-400">
                <svg class="mx-auto h-12 w-12 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-white">No predictions found</h3>
                <p class="mt-1 text-sm text-gray-400">Try adjusting your filters to see more results.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    predictions: {
        type: Array,
        required: true
    }
});

const isDetailedView = ref(false);
const filters = ref({
    date: '',
    team: '',
    tip: '',
    oddsRange: ''
});

const currentPage = ref(1);
const itemsPerPage = 10;

// Add stake amount ref
const stakeAmount = ref(0);

// Format date and time
const formatDateTime = (date, time) => {
    if (!date || !time) return 'N/A';
    
    try {
        // Parse the ISO date strings
        const dateObj = new Date(date);
        const timeObj = new Date(time);
        
        // Check if dates are valid
        if (isNaN(dateObj.getTime()) || isNaN(timeObj.getTime())) {
            return 'Invalid Date';
        }
        
        // Format the date and time
        const formattedDate = dateObj.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
        
        const formattedTime = timeObj.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
        
        return `${formattedDate} ${formattedTime}`;
    } catch (error) {
        console.error('Error formatting date:', error);
        return 'Invalid Date';
    }
};

// Format tip
const formatTip = (tips) => {
    if (!tips || !Array.isArray(tips)) return 'N/A';
    return tips.map(tip => tip.prediction).join(', ');
};

// Format odds
const formatOdds = (tips) => {
    if (!tips || !Array.isArray(tips)) return 'N/A';
    return tips.map(tip => tip.odds).join(', ');
};

// Get odds color class
const getOddsColorClass = (tips) => {
    if (!tips || !Array.isArray(tips)) return 'text-gray-300';
    const maxOdds = Math.max(...tips.map(tip => parseFloat(tip.odds)));
    if (maxOdds >= 2.5) return 'text-green-400';
    if (maxOdds >= 1.5) return 'text-yellow-400';
    return 'text-red-400';
};

// Calculate total odds for selected predictions
const calculateTotalOdds = () => {
    const selectedPredictions = filteredPredictions.value.filter(p => p.selected);
    if (!selectedPredictions.length) return '0.00';
    
    let totalOdds = 1;
    selectedPredictions.forEach(prediction => {
        if (prediction.tips && Array.isArray(prediction.tips)) {
            prediction.tips.forEach(tip => {
                if (tip.odds) {
                    totalOdds *= parseFloat(tip.odds);
                }
            });
        }
    });
    
    return totalOdds.toFixed(2);
};

// Calculate potential winnings
const calculatePotentialWinnings = () => {
    if (!stakeAmount.value || stakeAmount.value <= 0) return '0.00';
    const totalOdds = parseFloat(calculateTotalOdds());
    return (stakeAmount.value * totalOdds).toFixed(2);
};

// Filtered predictions
const filteredPredictions = computed(() => {
    return props.predictions.filter(prediction => {
        const matchDate = prediction.match_date;
        const matchTime = prediction.match_time;
        const dateTime = new Date(`${matchDate}T${matchTime}`);
        
        // Date filter
        if (filters.value.date) {
            const filterDate = new Date(filters.value.date);
            if (dateTime.toDateString() !== filterDate.toDateString()) {
                return false;
            }
        }

        // Team filter
        if (filters.value.team && !prediction.match.toLowerCase().includes(filters.value.team.toLowerCase())) {
            return false;
        }

        // Tip filter
        if (filters.value.tip) {
            const hasTip = prediction.tips.some(tip => tip.prediction.toLowerCase().includes(filters.value.tip.toLowerCase()));
            if (!hasTip) return false;
        }

        // Odds range filter
        if (filters.value.oddsRange && prediction.tips) {
            const maxOdds = Math.max(...prediction.tips.map(tip => parseFloat(tip.odds)));
            switch (filters.value.oddsRange) {
                case 'best':
                    if (maxOdds < 2.5) return false;
                    break;
                case 'moderate':
                    if (maxOdds < 1.5 || maxOdds >= 2.5) return false;
                    break;
                case 'low':
                    if (maxOdds >= 1.5) return false;
                    break;
            }
        }

        return true;
    });
});

// Pagination
const totalPages = computed(() => Math.ceil(filteredPredictions.value.length / itemsPerPage));
const paginatedPredictions = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredPredictions.value.slice(start, end);
});

const paginationStart = computed(() => (currentPage.value - 1) * itemsPerPage + 1);
const paginationEnd = computed(() => Math.min(currentPage.value * itemsPerPage, filteredPredictions.value.length));

const displayedPages = computed(() => {
    const pages = [];
    const maxPages = 5;
    let start = Math.max(1, currentPage.value - Math.floor(maxPages / 2));
    let end = Math.min(totalPages.value, start + maxPages - 1);
    
    if (end - start + 1 < maxPages) {
        start = Math.max(1, end - maxPages + 1);
    }
    
    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    
    return pages;
});

// Toggle view
const toggleView = () => {
    isDetailedView.value = !isDetailedView.value;
};

// Watch filters
watch(filters, () => {
    currentPage.value = 1;
}, { deep: true });
</script> 