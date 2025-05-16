<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <form @submit.prevent="submit" class="bg-gray-900 p-8 rounded shadow-md max-w-md mx-auto">
            <div>
                <InputLabel for="name" value="Name" class="text-gray-200" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full bg-gray-800 text-gray-100 border-gray-700 focus:border-blue-500 focus:ring-blue-500"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2 text-red-400" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" class="text-gray-200" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full bg-gray-800 text-gray-100 border-gray-700 focus:border-blue-500 focus:ring-blue-500"
                    v-model="form.email"
                    required
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
                    autocomplete="new-password"
                />

                <InputError class="mt-2 text-red-400" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="Confirm Password"
                    class="text-gray-200"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full bg-gray-800 text-gray-100 border-gray-700 focus:border-blue-500 focus:ring-blue-500"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2 text-red-400"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="mt-6 flex items-center justify-between">
                <Link
                    :href="route('login')"
                    class="text-sm text-blue-400 hover:text-blue-300 underline focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    Already registered?
                </Link>

                <PrimaryButton
                    class="ms-4 bg-blue-600 hover:bg-blue-700 text-white border-0"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Register
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
