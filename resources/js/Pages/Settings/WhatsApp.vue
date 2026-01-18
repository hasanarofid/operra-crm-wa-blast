<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    settings: Object,
    waStatus: Object,
    accounts: Array
});

const form = useForm({
    meta_access_token: props.settings.meta_access_token || '',
    meta_webhook_verify_token: props.settings.meta_webhook_verify_token || '',
    meta_app_id: props.settings.meta_app_id || '',
    meta_waba_id: props.settings.meta_waba_id || '',
});

// Form for Multi-Account CRUD
const accountForm = useForm({
    id: null,
    name: '',
    phone_number: '', // In Meta, this is often the Phone Number ID
    provider: 'official',
    token: '', // Override token if needed
    key: '',
    endpoint: '',
});

const isEditing = ref(false);
const showToken = ref(false);
const showVerifyToken = ref(false);
const showAppId = ref(false);
const showWabaId = ref(false);

const submit = () => {
    form.post(route('whatsapp.settings.update'), {
        preserveScroll: true,
        onSuccess: () => alert('WhatsApp configuration updated!'),
    });
};

const saveAccount = () => {
    if (isEditing.value) {
        accountForm.put(route('whatsapp.accounts.update', accountForm.id), {
            onSuccess: () => {
                resetAccountForm();
                alert('Account updated successfully!');
            }
        });
    } else {
        accountForm.post(route('whatsapp.accounts.store'), {
            onSuccess: () => {
                resetAccountForm();
                alert('Account added successfully!');
            }
        });
    }
};

const editAccount = (account) => {
    isEditing.value = true;
    accountForm.id = account.id;
    accountForm.name = account.name;
    accountForm.phone_number = account.phone_number;
    accountForm.provider = account.provider;
    accountForm.token = account.api_credentials?.token || '';
    accountForm.key = account.api_credentials?.key || '';
    accountForm.endpoint = account.api_credentials?.endpoint || '';
};

const deleteAccount = (id) => {
    if (confirm('Are you sure you want to delete this account?')) {
        accountForm.delete(route('whatsapp.accounts.destroy', id), {
            onSuccess: () => alert('Account deleted!')
        });
    }
};

const syncAccount = (id) => {
    accountForm.post(route('whatsapp.accounts.sync', id), {
        onSuccess: () => alert('Sync initiated!')
    });
};

const syncTemplates = (id) => {
    accountForm.post(route('whatsapp.accounts.sync-templates', id), {
        onSuccess: () => alert('Templates synced successfully!')
    });
};

const syncFromMeta = () => {
    if (confirm('Import all registered phone numbers from Meta WABA?')) {
        accountForm.post(route('whatsapp.accounts.sync-meta'), {
            onSuccess: () => alert('Accounts imported successfully!'),
            onError: (err) => alert('Sync failed: ' + (err.message || 'Check your WABA ID and Token'))
        });
    }
};

const resetAccountForm = () => {
    isEditing.value = false;
    accountForm.reset();
};
</script>

<template>
    <Head title="WhatsApp Settings" />

    <AuthenticatedLayout>
        <template #header>
            WhatsApp API Configuration
        </template>

        <div class="flex flex-wrap mt-4">
            <!-- Global Configuration -->
            <div class="w-full lg:w-1/2 px-4">
                <div class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded">
                    <div class="rounded-t mb-0 px-4 py-3 border-0 flex justify-between items-center">
                        <h3 class="font-bold text-base text-gray-700 dark:text-gray-200">Meta WhatsApp Global Configuration</h3>
                        <div v-if="waStatus" class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-bold">
                            Meta Cloud API v18.0
                        </div>
                    </div>
                    <div class="p-6 border-t border-gray-100 dark:border-gray-700">
                        <form @submit.prevent="submit">
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Permanent Access Token</label>
                                    <div class="relative mt-1">
                                        <input 
                                            v-model="form.meta_access_token" 
                                            :type="showToken ? 'text' : 'password'" 
                                            placeholder="EAABw..." 
                                            autocomplete="off"
                                            class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white pr-10 transition-all duration-200"
                                        >
                                        <button 
                                            type="button" 
                                            @click="showToken = !showToken"
                                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-operra-500 focus:outline-none"
                                        >
                                            <svg v-if="showToken" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                            </svg>
                                            <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 italic">Token utama dari Facebook Developer Console.</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Webhook Verify Token</label>
                                        <div class="relative mt-1">
                                            <input 
                                                v-model="form.meta_webhook_verify_token" 
                                                :type="showVerifyToken ? 'text' : 'password'" 
                                                placeholder="tigasatu_secret" 
                                                autocomplete="off"
                                                class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white pr-10"
                                            >
                                            <button 
                                                type="button" 
                                                @click="showVerifyToken = !showVerifyToken"
                                                class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-operra-500 focus:outline-none"
                                            >
                                                <svg v-if="showVerifyToken" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Meta App ID</label>
                                        <div class="relative mt-1">
                                            <input 
                                                v-model="form.meta_app_id" 
                                                :type="showAppId ? 'text' : 'password'" 
                                                placeholder="1234567890" 
                                                autocomplete="off"
                                                class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white pr-10"
                                            >
                                            <button 
                                                type="button" 
                                                @click="showAppId = !showAppId"
                                                class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-operra-500 focus:outline-none"
                                            >
                                                <svg v-if="showAppId" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">WhatsApp Business Account (WABA) ID</label>
                                    <div class="relative mt-1">
                                        <input 
                                            v-model="form.meta_waba_id" 
                                            :type="showWabaId ? 'text' : 'password'" 
                                            placeholder="Masukkan WABA ID" 
                                            autocomplete="off"
                                            class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white pr-10"
                                        >
                                        <button 
                                            type="button" 
                                            @click="showWabaId = !showWabaId"
                                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-operra-500 focus:outline-none"
                                        >
                                            <svg v-if="showWabaId" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 italic">Diperlukan untuk fitur Sync Otomatis nomor sales.</p>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" :disabled="form.processing" class="bg-operra-600 text-white px-4 py-2 rounded text-xs font-bold uppercase shadow hover:bg-operra-700 transition">
                                        Update Global Meta Config
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Multi-Account Management -->
            <div class="w-full lg:w-1/2 px-4">
                <div class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded">
                    <div class="rounded-t mb-0 px-4 py-3 border-0">
                        <h3 class="font-bold text-base text-gray-700 dark:text-gray-200">
                            {{ isEditing ? 'Edit WhatsApp Account' : 'Add New WhatsApp Account' }}
                        </h3>
                    </div>
                    <div class="p-6 border-t border-gray-100 dark:border-gray-700">
                        <form @submit.prevent="saveAccount">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="col-span-1">
                                    <label class="block text-sm font-medium">Display Name (Nama Sales/Divisi)</label>
                                    <input v-model="accountForm.name" type="text" placeholder="e.g. CS Utama" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-sm font-medium">Phone Number ID (dari Meta)</label>
                                    <input v-model="accountForm.phone_number" type="text" placeholder="10595..." class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
                                </div>
                                <!-- Provider disembunyikan karena sudah pasti Meta Official -->
                                <input type="hidden" v-model="accountForm.provider">
                            </div>
                            <div class="mt-4 flex gap-2">
                                <button type="submit" :disabled="accountForm.processing" class="bg-green-600 text-white px-6 py-2 rounded text-xs font-bold uppercase shadow hover:bg-green-700 transition">
                                    {{ isEditing ? 'Update Account' : 'Add WhatsApp Account' }}
                                </button>
                                <button v-if="isEditing" @click="resetAccountForm" type="button" class="bg-gray-500 text-white px-4 py-2 rounded text-xs font-bold uppercase shadow hover:bg-gray-600 transition">
                                    Cancel
                                </button>
                            </div>
                            <p class="text-[10px] text-gray-500 mt-2 italic">*Akun baru otomatis menggunakan Global Meta Token dan mendapatkan Trial 3 Bulan.</p>
                        </form>
                    </div>
                </div>
            </div>

            <!-- List of Accounts -->
            <div class="w-full px-4">
                <div class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded">
                    <div class="rounded-t mb-0 px-4 py-3 border-0 flex justify-between items-center">
                        <h3 class="font-bold text-base text-gray-700 dark:text-gray-200">WhatsApp Accounts List</h3>
                        <button @click="syncFromMeta" :disabled="!settings.meta_waba_id" class="bg-blue-600 text-white px-4 py-2 rounded text-xs font-bold uppercase shadow hover:bg-blue-700 transition flex items-center gap-2">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            Sync From Meta (WABA)
                        </button>
                    </div>
                    <div class="block w-full overflow-x-auto">
                        <table class="items-center w-full bg-transparent border-collapse">
                            <thead>
                                <tr>
                                    <th class="px-6 align-middle border border-solid py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left bg-gray-50 dark:bg-gray-700 dark:border-gray-600">Name</th>
                                    <th class="px-6 align-middle border border-solid py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left bg-gray-50 dark:bg-gray-700 dark:border-gray-600">Phone</th>
                                    <th class="px-6 align-middle border border-solid py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left bg-gray-50 dark:bg-gray-700 dark:border-gray-600">Provider</th>
                                    <th class="px-6 align-middle border border-solid py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left bg-gray-50 dark:bg-gray-700 dark:border-gray-600">Status</th>
                                    <th class="px-6 align-middle border border-solid py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left bg-gray-50 dark:bg-gray-700 dark:border-gray-600">Verified</th>
                                    <th class="px-6 align-middle border border-solid py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left bg-gray-50 dark:bg-gray-700 dark:border-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="account in accounts" :key="account.id">
                                    <td class="px-6 py-4 text-sm">{{ account.name }}</td>
                                    <td class="px-6 py-4 text-sm">{{ account.phone_number }}</td>
                                    <td class="px-6 py-4 text-sm capitalize">{{ account.provider }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <span :class="account.status === 'active' ? 'text-green-600' : 'text-red-600'" class="font-bold">
                                            {{ account.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span v-if="account.is_verified" class="text-blue-500">✅ Yes</span>
                                        <span v-else class="text-gray-400">❌ No</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm flex gap-2">
                                        <button @click="editAccount(account)" class="text-blue-600 hover:underline">Edit</button>
                                        <button @click="syncAccount(account.id)" class="text-green-600 hover:underline">Sync</button>
                                        <button v-if="account.provider === 'official'" @click="syncTemplates(account.id)" class="text-indigo-600 hover:underline">Templates</button>
                                        <button @click="deleteAccount(account.id)" class="text-red-600 hover:underline">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="accounts.length === 0">
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500 italic">No accounts found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

