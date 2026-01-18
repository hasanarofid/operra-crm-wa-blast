<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    apps: Array,
    whatsappAccounts: Array
});

const form = useForm({
    id: null,
    name: '',
    phone_number: '',
    webhook_url: '',
    is_active: true,
    widget_settings: {
        primary_color: '#25D366',
        position: 'right',
        welcome_text: 'Halo! Ada yang bisa kami bantu?',
    }
});

const isEditing = ref(false);
const showSecrets = ref({});
const previewAppKey = ref(null);

const togglePreview = (key) => {
    if (previewAppKey.value === key) {
        previewAppKey.value = null;
        // Remove widget elements if any
        const bubble = document.querySelector('.wa-widget-bubble');
        if (bubble) bubble.remove();
        return;
    }
    
    previewAppKey.value = key;
    // Inject the script for preview
    const script = document.createElement('script');
    script.src = `/js/wa-widget.js?t=${Date.now()}`;
    script.setAttribute('data-key', key);
    document.body.appendChild(script);
    
    alert('Simulasi diaktifkan! Lihat pojok layar Anda.');
};

const openPreviewPage = (key) => {
    window.open(route('external-apps.preview', { key: key }), '_blank');
};

const toggleSecret = (id) => {
    showSecrets.value[id] = !showSecrets.value[id];
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('external-apps.update', form.id), {
            onSuccess: () => {
                resetForm();
                alert('App updated successfully!');
            }
        });
    } else {
        form.post(route('external-apps.store'), {
            onSuccess: () => {
                resetForm();
                alert('App created successfully!');
            }
        });
    }
};

const editApp = (app) => {
    isEditing.value = true;
    form.id = app.id;
    form.name = app.name;
    form.phone_number = app.phone_number || '';
    form.webhook_url = app.webhook_url || '';
    form.is_active = !!app.is_active;
    form.widget_settings = app.widget_settings || {
        primary_color: '#25D366',
        position: 'right',
        welcome_text: 'Halo! Ada yang bisa kami bantu?',
    };
};

const deleteApp = (id) => {
    if (confirm('Are you sure you want to delete this app? Webhooks and embeds will stop working immediately.')) {
        form.delete(route('external-apps.destroy', id), {
            onSuccess: () => alert('App deleted!')
        });
    }
};

const resetForm = () => {
    isEditing.value = false;
    form.reset();
};

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    alert('Copied to clipboard!');
};
</script>

<template>
    <Head title="External Apps & Embedding" />

    <AuthenticatedLayout>
        <template #header>
            External Apps & WhatsApp Embedding
        </template>

        <div class="flex flex-wrap mt-4">
            <!-- Form App Baru / Edit -->
            <div class="w-full lg:w-1/3 px-4">
                <div class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded">
                    <div class="rounded-t mb-0 px-4 py-3 border-0">
                        <h3 class="font-bold text-base text-gray-700 dark:text-gray-200">
                            {{ isEditing ? 'Edit External App' : 'Register New App' }}
                        </h3>
                    </div>
                    <div class="p-6 border-t border-gray-100 dark:border-gray-700">
                        <form @submit.prevent="submit">
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium">App Name (System/Client Name)</label>
                                    <input v-model="form.name" type="text" placeholder="e.g. CodeIgniter POS System" autocomplete="off" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">WhatsApp Number (Official API)</label>
                                    <select v-model="form.phone_number" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white" required>
                                        <option value="">Select Account</option>
                                        <option v-for="acc in whatsappAccounts" :key="acc.id" :value="acc.phone_number">
                                            {{ acc.name }} ({{ acc.phone_number }})
                                        </option>
                                    </select>
                                    <p class="text-[10px] text-gray-500 mt-1">Nomor ini yang akan dihubungi melalui widget.</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Outbound Webhook URL (Optional)</label>
                                    <input v-model="form.webhook_url" type="url" placeholder="https://client-system.com/api/webhook" autocomplete="off" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
                                    <p class="text-[10px] text-gray-500 mt-1">URL ini akan menerima data setiap kali ada chat WA masuk ke CRM.</p>
                                </div>
                                
                                <div class="border-t pt-4 mt-2">
                                    <h4 class="text-xs font-bold uppercase text-gray-400 mb-2">Widget Settings (White Label)</h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium">Primary Color</label>
                                            <input v-model="form.widget_settings.primary_color" type="color" class="mt-1 block w-full h-10 rounded-md border-gray-300 dark:bg-gray-700">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium">Position</label>
                                            <select v-model="form.widget_settings.position" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
                                                <option value="right">Bottom Right</option>
                                                <option value="left">Bottom Left</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <label class="block text-sm font-medium">Welcome Text</label>
                                        <input v-model="form.widget_settings.welcome_text" type="text" autocomplete="off" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
                                    </div>
                                </div>

                                <div class="flex items-center mt-2">
                                    <input v-model="form.is_active" type="checkbox" id="is_active" class="rounded border-gray-300 text-operra-600 shadow-sm focus:border-operra-300 focus:ring focus:ring-operra-200 focus:ring-opacity-50">
                                    <label for="is_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">Active Status</label>
                                </div>
                            </div>

                            <div class="mt-6 flex gap-2">
                                <button type="submit" :disabled="form.processing" class="bg-operra-600 text-white px-6 py-2 rounded text-xs font-bold uppercase shadow hover:bg-operra-700 transition">
                                    {{ isEditing ? 'Update Configuration' : 'Create External App' }}
                                </button>
                                <button v-if="isEditing" @click="resetForm" type="button" class="bg-gray-500 text-white px-4 py-2 rounded text-xs font-bold uppercase shadow hover:bg-gray-600 transition">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- List Apps -->
            <div class="w-full lg:w-2/3 px-4">
                <div class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded">
                    <div class="rounded-t mb-0 px-4 py-3 border-0">
                        <h3 class="font-bold text-base text-gray-700 dark:text-gray-200">Registered External Apps</h3>
                    </div>
                    <div class="block w-full overflow-x-auto">
                        <table class="items-center w-full bg-transparent border-collapse">
                            <thead>
                                <tr>
                                    <th class="px-6 align-middle border border-solid py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left bg-gray-50 dark:bg-gray-700 dark:border-gray-600 text-gray-500">App Details</th>
                                    <th class="px-6 align-middle border border-solid py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left bg-gray-50 dark:bg-gray-700 dark:border-gray-600 text-gray-500">Security (Keys)</th>
                                    <th class="px-6 align-middle border border-solid py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left bg-gray-50 dark:bg-gray-700 dark:border-gray-600 text-gray-500">Embed Snippet</th>
                                    <th class="px-6 align-middle border border-solid py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left bg-gray-50 dark:bg-gray-700 dark:border-gray-600 text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="app in apps" :key="app.id" class="border-b dark:border-gray-700">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-gray-800 dark:text-gray-200">{{ app.name }}</span>
                                            <span class="text-[10px] text-blue-600 font-bold">WA: {{ app.phone_number }}</span>
                                            <span class="text-[10px] text-gray-500 truncate w-40">{{ app.webhook_url || 'No Webhook' }}</span>
                                            <span :class="app.is_active ? 'text-green-500' : 'text-red-500'" class="text-[10px] font-bold uppercase">
                                                {{ app.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-1">
                                                <span class="text-[10px] font-bold text-gray-400">KEY:</span>
                                                <code class="text-[10px] bg-gray-100 dark:bg-gray-900 px-1 rounded">{{ app.app_key }}</code>
                                                <button @click="copyToClipboard(app.app_key)" class="text-gray-400 hover:text-blue-500"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg></button>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <span class="text-[10px] font-bold text-gray-400">SEC:</span>
                                                <code class="text-[10px] bg-gray-100 dark:bg-gray-900 px-1 rounded">
                                                    {{ showSecrets[app.id] ? app.app_secret : '****************' }}
                                                </code>
                                                <button @click="toggleSecret(app.id)" class="text-gray-400 hover:text-blue-500">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-2">
                                            <button @click="copyToClipboard(`<script src='https://crm.hasanarofid.site/js/wa-widget.js' data-key='${app.app_key}'></script>`)" class="text-[10px] bg-operra-50 text-operra-700 px-2 py-1 rounded border border-operra-200 hover:bg-operra-100 transition text-center">
                                                Copy Snippet
                                            </button>
                                            <button @click="togglePreview(app.app_key)" :class="previewAppKey === app.app_key ? 'bg-red-500 text-white border-red-600' : 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100'" class="text-[10px] px-2 py-1 rounded border transition text-center">
                                                {{ previewAppKey === app.app_key ? 'Stop Simulation' : 'Quick Preview' }}
                                            </button>
                                            <button @click="openPreviewPage(app.app_key)" class="text-[10px] bg-blue-50 text-blue-700 px-2 py-1 rounded border border-blue-200 hover:bg-blue-100 transition text-center flex items-center justify-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                Full Preview Page
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs">
                                        <div class="flex gap-2">
                                            <button @click="editApp(app)" class="text-blue-600 hover:underline">Edit</button>
                                            <button @click="deleteApp(app.id)" class="text-red-600 hover:underline">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="apps.length === 0">
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-400 italic">No apps registered yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4 bg-blue-50 dark:bg-gray-700 p-4 rounded-lg border border-blue-100 dark:border-gray-600">
                    <h4 class="text-sm font-bold text-blue-800 dark:text-blue-200 mb-2 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Professional Embedding Tip
                    </h4>
                    <p class="text-xs text-blue-700 dark:text-blue-300 leading-relaxed">
                        Untuk sistem seperti CodeIgniter, Anda bisa menggunakan <b>Outbound Webhook</b> di atas. 
                        Setiap kali ada pesan WA masuk, CRM Operra akan mengirimkan POST request ke URL Anda dengan payload JSON yang aman (dilengkapi Signature HMAC-SHA256 menggunakan App Secret Anda).
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

