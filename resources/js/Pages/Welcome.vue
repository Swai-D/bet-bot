<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

function handleImageError() {
    document.getElementById('screenshot-container')?.classList.add('!hidden');
    document.getElementById('docs-card')?.classList.add('!row-span-1');
    document.getElementById('docs-card-content')?.classList.add('!flex-row');
    document.getElementById('background')?.classList.add('!hidden');
}
</script>

<template>
    <Head title="Betting Bot - Automated Sports Betting" />
    
    <div class="min-h-screen bg-gradient-to-b from-gray-900 to-black text-white">
        <!-- Navigation -->
        <nav class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <span class="text-2xl font-bold text-blue-500">BetBot</span>
                    </div>
                <div class="flex items-center space-x-4">
                            <Link
                                :href="route('login')"
                        class="rounded-md px-4 py-2 text-sm font-semibold text-white hover:text-blue-400 transition-colors"
                            >
                                Log in
                            </Link>
                    <Link
                        :href="route('register')"
                        class="rounded-md bg-blue-500 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-600 transition-colors"
                    >
                        Sign up
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="container mx-auto px-6 py-16">
            <div class="flex flex-col items-center text-center">
                <h1 class="text-5xl font-bold mb-6">
                    Automated Sports Betting
                    <span class="text-blue-500">Made Simple</span>
                </h1>
                <p class="text-xl text-gray-300 mb-8 max-w-2xl">
                    Let our advanced betting bot analyze matches, place bets, and maximize your winning potential with data-driven predictions.
                </p>
                <div class="flex space-x-4">
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                        class="rounded-md bg-blue-500 px-8 py-3 text-lg font-semibold text-white hover:bg-blue-600 transition-colors"
                            >
                        Get Started
                            </Link>
                    <a
                        href="#features"
                        class="rounded-md border border-blue-500 px-8 py-3 text-lg font-semibold text-white hover:bg-blue-500/10 transition-colors"
                    >
                        Learn More
                    </a>
                                    </div>
                                    </div>
                                </div>

        <!-- Features Section -->
        <div id="features" class="container mx-auto px-6 py-16">
            <h2 class="text-3xl font-bold text-center mb-12">Key Features</h2>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-gray-800 rounded-lg p-6 hover:bg-gray-700 transition-colors">
                    <div class="text-blue-500 text-4xl mb-4">🤖</div>
                    <h3 class="text-xl font-semibold mb-2">Automated Betting</h3>
                    <p class="text-gray-300">Set your preferences and let our bot handle the betting process automatically.</p>
                </div>
                <div class="bg-gray-800 rounded-lg p-6 hover:bg-gray-700 transition-colors">
                    <div class="text-blue-500 text-4xl mb-4">📊</div>
                    <h3 class="text-xl font-semibold mb-2">Smart Analysis</h3>
                    <p class="text-gray-300">Advanced algorithms analyze matches and provide data-driven predictions.</p>
                </div>
                <div class="bg-gray-800 rounded-lg p-6 hover:bg-gray-700 transition-colors">
                    <div class="text-blue-500 text-4xl mb-4">💰</div>
                    <h3 class="text-xl font-semibold mb-2">Profit Tracking</h3>
                    <p class="text-gray-300">Monitor your betting performance with detailed statistics and reports.</p>
                            </div>
                            </div>
                            </div>

        <!-- How It Works Section -->
        <div class="container mx-auto px-6 py-16">
            <h2 class="text-3xl font-bold text-center mb-12">How It Works</h2>
            <div class="grid md:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="text-blue-500 text-4xl mb-4">1</div>
                    <h3 class="text-xl font-semibold mb-2">Sign Up</h3>
                    <p class="text-gray-300">Create your account and set your preferences</p>
                            </div>
                <div class="text-center">
                    <div class="text-blue-500 text-4xl mb-4">2</div>
                    <h3 class="text-xl font-semibold mb-2">Configure</h3>
                    <p class="text-gray-300">Set your betting strategy and limits</p>
                            </div>
                <div class="text-center">
                    <div class="text-blue-500 text-4xl mb-4">3</div>
                    <h3 class="text-xl font-semibold mb-2">Monitor</h3>
                    <p class="text-gray-300">Watch the bot analyze and place bets</p>
                            </div>
                <div class="text-center">
                    <div class="text-blue-500 text-4xl mb-4">4</div>
                    <h3 class="text-xl font-semibold mb-2">Profit</h3>
                    <p class="text-gray-300">Track your winnings and adjust strategy</p>
                            </div>
                        </div>
                    </div>

        <!-- CTA Section -->
        <div class="container mx-auto px-6 py-16">
            <div class="bg-blue-500 rounded-lg p-12 text-center">
                <h2 class="text-3xl font-bold mb-4">Ready to Start Winning?</h2>
                <p class="text-xl mb-8">Join thousands of successful bettors using our automated betting system.</p>
                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="rounded-md bg-white px-8 py-3 text-lg font-semibold text-blue-500 hover:bg-gray-100 transition-colors"
                >
                    Start Free Trial
                </Link>
            </div>
        </div>

        <!-- Footer -->
        <footer class="container mx-auto px-6 py-8">
            <div class="border-t border-gray-800 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-gray-400 text-sm">
                        © 2024 BetBot. All rights reserved.
                    </div>
                    <div class="flex space-x-6 mt-4 md:mt-0">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Terms</a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Privacy</a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Contact</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
