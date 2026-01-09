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
    wa_blast_provider: props.settings.wa_blast_provider || 'generic',
    wa_blast_number: props.settings.wa_blast_number || '',
    wa_blast_endpoint: props.settings.wa_blast_endpoint || '',
    wa_blast_token: props.settings.wa_blast_token || '',
    wa_blast_key: props.settings.wa_blast_key || '',
});

// Form for Multi-Account CRUD
const accountForm = useForm({
    id: null,
    name: '',
    phone_number: '',
    provider: 'fonnte',
    token: '',
    key: '',
    endpoint: '',
});

const isEditing = ref(false);

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
                        <h3 class="font-bold text-base text-gray-700 dark:text-gray-200">Global API Settings (Default)</h3>
                        <div v-if="waStatus" :class="waStatus.connection === 'connected' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-3 py-1 rounded-full text-xs font-bold">
                            Status: {{ waStatus.connection }}
                        </div>
                    </div>
                    <div class="p-6 border-t border-gray-100 dark:border-gray-700">
                        <form @submit.prevent="submit">
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Provider</label>
                                    <select v-model="form.wa_blast_provider" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
                                        <option value="generic">Generic / Other API</option>
                                        <option value="fonnte">Fonnte</option>
                                        <option value="official">Official API (Cloud API)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Number / ID</label>
                                    <input v-model="form.wa_blast_number" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Token</label>
                                    <textarea v-model="form.wa_blast_token" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white"></textarea>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" :disabled="form.processing" class="bg-blue-600 text-white px-4 py-2 rounded text-xs font-bold uppercase shadow hover:bg-blue-700 transition">
                                        Save Default Config
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
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-1">
                                    <label class="block text-sm font-medium">Account Name</label>
                                    <input v-model="accountForm.name" type="text" placeholder="e.g. CS Jakarta" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-sm font-medium">Phone Number</label>
                                    <input v-model="accountForm.phone_number" type="text" placeholder="628123xxx" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-sm font-medium">Provider</label>
                                    <select v-model="accountForm.provider" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
                                        <option value="fonnte">Fonnte</option>
                                        <option value="official">Official API</option>
                                        <option value="generic">Generic</option>
                                    </select>
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-sm font-medium">API Token</label>
                                    <input v-model="accountForm.token" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                            <div class="mt-4 flex gap-2">
                                <button type="submit" :disabled="accountForm.processing" class="bg-green-600 text-white px-4 py-2 rounded text-xs font-bold uppercase shadow hover:bg-green-700 transition">
                                    {{ isEditing ? 'Update Account' : 'Add Account' }}
                                </button>
                                <button v-if="isEditing" @click="resetAccountForm" type="button" class="bg-gray-500 text-white px-4 py-2 rounded text-xs font-bold uppercase shadow hover:bg-gray-600 transition">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- List of Accounts -->
            <div class="w-full px-4">
                <div class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded">
                    <div class="rounded-t mb-0 px-4 py-3 border-0">
                        <h3 class="font-bold text-base text-gray-700 dark:text-gray-200">WhatsApp Accounts List</h3>
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

