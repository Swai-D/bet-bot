<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-400 bg-gray-800 p-2 rounded">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="bg-gray-900 p-8 rounded shadow-md max-w-md mx-auto">
            <div>
                <InputLabel for="email" value="Email" class="text-gray-200" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full bg-gray-800 text-gray-100 border-gray-700 focus:border-blue-500 focus:ring-blue-500"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2 text-red-400" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" class="text-gray-200" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full bg-gray-800 text-gray-100 border-gray-700 focus:border-blue-500 focus:ring-blue-500"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2 text-red-400" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-300">Remember me</span>
                </label>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-blue-400 hover:text-blue-300 underline focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    Forgot your password?
                </Link>

                <PrimaryButton
                    class="ms-4 bg-blue-600 hover:bg-blue-700 text-white border-0"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log in
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
