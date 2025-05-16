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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-1/3">Match</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-1/6">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-1/6">Tip</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-1/6">Odds</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-1/6">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-gray-800 divide-y divide-gray-700">
                        <tr v-for="prediction in paginatedPredictions" :key="prediction.id" class="hover:bg-gray-700">
                            <td class="px-6 py-4 text-sm font-medium text-white">
                                {{ prediction.match || prediction.teams }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-300">
                                {{ formatDate(prediction.date || prediction.match_date) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-300">
                                {{ formatTip(prediction.tips) }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium" :class="getOddsColorClass(prediction.odds)">
                                {{ formatOdds(prediction.odds) }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <span :class="prediction.selected ? 'text-green-400' : 'text-red-400'">
                                    {{ prediction.selected ? 'Selected' : 'Not Selected' }}
                                </span>
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
                        <h3 class="text-lg font-semibold text-white">{{ prediction.match || prediction.teams }}</h3>
                        <span :class="getOddsColorClass(prediction.odds)" class="text-sm font-medium">
                            {{ formatOdds(prediction.odds) }}
                        </span>
                    </div>
                    <div class="space-y-2">
                        <p class="text-sm text-gray-300">
                            <span class="font-medium">Date:</span> {{ formatDate(prediction.date || prediction.match_date) }}
                        </p>
                        <p class="text-sm text-gray-300">
                            <span class="font-medium">Tip:</span> {{ formatTip(prediction.tips) }}
                        </p>
                        <p class="text-sm" :class="prediction.selected ? 'text-green-400' : 'text-red-400'">
                            <span class="font-medium">Status:</span> 
                            {{ prediction.selected ? 'Selected' : 'Not Selected' }}
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
                        <p class="text-sm text-gray-300">Average Odds</p>
                        <p class="text-lg font-semibold text-white">
                            {{ calculateAverageOdds() }}
                        </p>
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

const toggleView = () => {
    isDetailedView.value = !isDetailedView.value;
};

const itemsPerPage = 10;
const currentPage = ref(1);

// Reset to first page when filters change
watch(filters, () => {
    currentPage.value = 1;
}, { deep: true });

const totalPages = computed(() => {
    return Math.ceil(filteredPredictions.value.length / itemsPerPage);
});

const paginatedPredictions = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredPredictions.value.slice(start, end);
});

const paginationStart = computed(() => {
    return (currentPage.value - 1) * itemsPerPage + 1;
});

const paginationEnd = computed(() => {
    return Math.min(currentPage.value * itemsPerPage, filteredPredictions.value.length);
});

const displayedPages = computed(() => {
    const pages = [];
    const maxVisiblePages = 5;
    
    if (totalPages.value <= maxVisiblePages) {
        // Show all pages if total pages is less than max visible
        for (let i = 1; i <= totalPages.value; i++) {
            pages.push(i);
        }
    } else {
        // Always show first page
        pages.push(1);
        
        // Calculate start and end of visible pages
        let start = Math.max(2, currentPage.value - 1);
        let end = Math.min(totalPages.value - 1, currentPage.value + 1);
        
        // Adjust if at the start
        if (currentPage.value <= 2) {
            end = 4;
        }
        // Adjust if at the end
        if (currentPage.value >= totalPages.value - 1) {
            start = totalPages.value - 3;
        }
        
        // Add ellipsis if needed
        if (start > 2) {
            pages.push('...');
        }
        
        // Add middle pages
        for (let i = start; i <= end; i++) {
            pages.push(i);
        }
        
        // Add ellipsis if needed
        if (end < totalPages.value - 1) {
            pages.push('...');
        }
        
        // Always show last page
        pages.push(totalPages.value);
    }
    
    return pages;
});

const filteredPredictions = computed(() => {
    return props.predictions.filter(prediction => {
        // Date filter
        if (filters.value.date) {
            const predictionDate = new Date(prediction.date || prediction.match_date);
            const filterDate = new Date(filters.value.date);
            if (predictionDate.toDateString() !== filterDate.toDateString()) {
                return false;
            }
        }

        // Team filter
        if (filters.value.team) {
            const teamSearch = filters.value.team.toLowerCase();
            const matchTeams = (prediction.match || prediction.teams || '').toLowerCase();
            if (!matchTeams.includes(teamSearch)) {
                return false;
            }
        }

        // Tip filter
        if (filters.value.tip) {
            const predictionTips = Array.isArray(prediction.tips) 
                ? prediction.tips.map(t => typeof t === 'object' ? t.option : t)
                : [prediction.tips];
            
            if (!predictionTips.includes(filters.value.tip)) {
                return false;
            }
        }

        // Odds Range filter
        if (filters.value.oddsRange) {
            const odds = typeof prediction.odds === 'object' ? prediction.odds.odd : prediction.odds;
            const oddsValue = parseFloat(odds) || 0;

            switch (filters.value.oddsRange) {
                case 'best':
                    if (oddsValue < 2.5) return false;
                    break;
                case 'moderate':
                    if (oddsValue < 1.5 || oddsValue >= 2.5) return false;
                    break;
                case 'low':
                    if (oddsValue >= 1.5) return false;
                    break;
            }
        }

        return true;
    });
});

const formatDate = (date) => {
    if (!date) return 'Invalid Date';
    try {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return 'Invalid Date';
    }
};

const formatTip = (tips) => {
    if (!tips) return 'N/A';
    if (typeof tips === 'string') return formatTipOption(tips);
    if (Array.isArray(tips)) {
        // Only take the first 2 tips
        return tips.slice(0, 2).map(tip => {
            if (typeof tip === 'object' && tip.option) {
                return formatTipOption(tip.option);
            }
            return formatTipOption(tip);
        }).join(' | ');
    }
    return 'N/A';
};

const formatTipOption = (option) => {
    const tipMap = {
        '1': '🏠 1',
        'X': '🤝 X',
        '2': '✈️ 2',
        'GG': '⚽ GG',
        '+2.5': '📈 +2.5',
        '-2.5': '📉 -2.5'
    };
    return tipMap[option] || option;
};

const formatOdds = (odds) => {
    if (!odds) return 'N/A';
    if (typeof odds === 'object' && odds.odd) {
        return odds.odd.toFixed(2);
    }
    if (typeof odds === 'number') {
        return odds.toFixed(2);
    }
    return 'N/A';
};

const getOddsColorClass = (odds) => {
    if (!odds) return 'text-gray-500';
    const oddValue = typeof odds === 'object' ? odds.odd : odds;
    if (oddValue >= 2.5) return 'text-green-400';
    if (oddValue >= 1.5) return 'text-yellow-400';
    return 'text-red-400';
};

const calculateAverageOdds = () => {
    const validOdds = filteredPredictions.value
        .map(p => {
            const odds = p.odds;
            return typeof odds === 'object' ? odds.odd : odds;
        })
        .filter(odds => odds !== null && odds !== undefined);
    
    if (validOdds.length === 0) return 'N/A';
    
    const average = validOdds.reduce((sum, odds) => sum + odds, 0) / validOdds.length;
    return average.toFixed(2);
};
</script> 