<template>
    <div v-if="show" :class="alertClasses" class="rounded-md p-4 mb-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <CheckCircleIcon v-if="type === 'success'" class="h-5 w-5 text-green-400" />
                <ExclamationCircleIcon v-else-if="type === 'error'" class="h-5 w-5 text-red-400" />
                <InformationCircleIcon v-else class="h-5 w-5 text-blue-400" />
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium" :class="textClasses">
                    {{ message }}
                </p>
            </div>
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button
                        type="button"
                        class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
                        :class="buttonClasses"
                        @click="$emit('close')"
                    >
                        <span class="sr-only">Dismiss</span>
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import {
    CheckCircleIcon,
    ExclamationCircleIcon,
    InformationCircleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    type: {
        type: String,
        default: 'info',
        validator: (value) => ['success', 'error', 'info'].includes(value)
    },
    message: {
        type: String,
        required: true
    }
});

const alertClasses = computed(() => ({
    'bg-green-50': props.type === 'success',
    'bg-red-50': props.type === 'error',
    'bg-blue-50': props.type === 'info'
}));

const textClasses = computed(() => ({
    'text-green-800': props.type === 'success',
    'text-red-800': props.type === 'error',
    'text-blue-800': props.type === 'info'
}));

const buttonClasses = computed(() => ({
    'bg-green-50 text-green-500 hover:bg-green-100 focus:ring-green-600 focus:ring-offset-green-50': props.type === 'success',
    'bg-red-50 text-red-500 hover:bg-red-100 focus:ring-red-600 focus:ring-offset-red-50': props.type === 'error',
    'bg-blue-50 text-blue-500 hover:bg-blue-100 focus:ring-blue-600 focus:ring-offset-blue-50': props.type === 'info'
}));
</script> 