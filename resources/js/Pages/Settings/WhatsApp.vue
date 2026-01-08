<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    settings: Object,
    waStatus: Object
});

const form = useForm({
    wa_blast_provider: props.settings.wa_blast_provider || 'generic',
    wa_blast_number: props.settings.wa_blast_number || '',
    wa_blast_endpoint: props.settings.wa_blast_endpoint || '',
    wa_blast_token: props.settings.wa_blast_token || '',
    wa_blast_key: props.settings.wa_blast_key || '',
});

const submit = () => {
    form.post(route('whatsapp.settings.update'), {
        preserveScroll: true,
        onSuccess: () => alert('WhatsApp configuration updated!'),
    });
};
</script>

<template>
    <Head title="WhatsApp Settings" />

    <AuthenticatedLayout>
        <template #header>
            WhatsApp API Configuration
        </template>

        <div class="flex flex-wrap mt-4">
            <div class="w-full px-4">
                <div class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded">
                    <div class="rounded-t mb-0 px-4 py-3 border-0 flex justify-between items-center">
                        <h3 class="font-bold text-base text-gray-700 dark:text-gray-200">API Connection Details</h3>
                        <div v-if="waStatus" :class="waStatus.connection === 'connected' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-3 py-1 rounded-full text-xs font-bold">
                            Status: {{ waStatus.connection }}
                        </div>
                    </div>
                    <div class="p-6 border-t border-gray-100 dark:border-gray-700">
                        <form @submit.prevent="submit" class="max-w-2xl">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="w-full md:col-span-2 mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">WhatsApp Provider</label>
                                    <select v-model="form.wa_blast_provider" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-operra-500 focus:ring-operra-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="generic">Generic / Other API</option>
                                        <option value="fonnte">Fonnte</option>
                                        <option value="official">WhatsApp Official API (Cloud API)</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ form.wa_blast_provider === 'official' ? 'Phone Number ID' : 'WhatsApp Number (Sender)' }}
                                    </label>
                                    <input v-model="form.wa_blast_number" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-operra-500 focus:ring-operra-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" :placeholder="form.wa_blast_provider === 'official' ? '1234567890' : '628123456789'">
                                    <p v-if="form.wa_blast_provider === 'official'" class="mt-1 text-xs text-gray-500">Masukkan Phone Number ID dari Meta Developer Console.</p>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ form.wa_blast_provider === 'fonnte' ? 'API Key (Optional for Fonnte)' : 'API Key' }}
                                    </label>
                                    <input v-model="form.wa_blast_key" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-operra-500 focus:ring-operra-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>

                                <div class="w-full md:col-span-2 mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Endpoint URL</label>
                                    <input v-model="form.wa_blast_endpoint" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-operra-500 focus:ring-operra-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" :placeholder="form.wa_blast_provider === 'fonnte' ? 'https://api.fonnte.com/send' : (form.wa_blast_provider === 'official' ? 'https://graph.facebook.com/v18.0/' : 'https://api.wa-provider.com/v1')">
                                    <p class="mt-1 text-xs text-gray-500">Kosongkan untuk menggunakan default provider.</p>
                                </div>

                                <div class="w-full md:col-span-2 mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ form.wa_blast_provider === 'official' ? 'System User Access Token' : (form.wa_blast_provider === 'fonnte' ? 'Fonnte Token' : 'API Token / Secret') }}
                                    </label>
                                    <textarea v-model="form.wa_blast_token" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-operra-500 focus:ring-operra-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                </div>
                            </div>

                            <div class="mt-6">
                                <button type="submit" :disabled="form.processing" class="bg-operra-500 text-white px-6 py-2 rounded-md font-bold uppercase text-xs shadow hover:bg-operra-600 transition-colors">
                                    Save WA Configuration
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

