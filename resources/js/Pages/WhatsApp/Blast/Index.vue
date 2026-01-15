<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    campaigns: Array,
    accounts: Array,
});

const form = useForm({
    name: '',
    whatsapp_account_id: '',
    customer_ids: [],
    message_template: '',
    template_name: '',
    template_data: null,
});

const isCreating = ref(false);

const submit = () => {
    form.post(route('whatsapp.blast.store'), {
        onSuccess: () => {
            isCreating.value = false;
            form.reset();
            alert('Campaign created successfully!');
        },
    });
};

const processCampaign = (id) => {
    if (confirm('Start sending messages for this campaign?')) {
        axios.post(route('whatsapp.blast.process', id))
            .then(response => {
                alert(response.data.message);
                window.location.reload();
            })
            .catch(error => {
                alert(error.response?.data?.message || 'Error processing campaign');
            });
    }
};
</script>

<template>
    <Head title="WhatsApp Blast" />

    <AuthenticatedLayout>
        <template #header>
            WhatsApp Marketing (Blast)
        </template>

        <div class="flex flex-wrap mt-4">
            <div class="w-full px-4">
                <div class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded">
                    <div class="rounded-t mb-0 px-4 py-3 border-0 flex justify-between items-center">
                        <h3 class="font-bold text-base text-gray-700 dark:text-gray-200">Marketing Campaigns</h3>
                        <button @click="isCreating = !isCreating" class="bg-operra-600 text-white px-4 py-2 rounded text-xs font-bold uppercase shadow hover:bg-operra-700 transition">
                            {{ isCreating ? 'Cancel' : 'Create New Blast' }}
                        </button>
                    </div>

                    <!-- Create Form -->
                    <div v-if="isCreating" class="p-6 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                        <form @submit.prevent="submit">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="col-span-1">
                                    <label class="block text-sm font-medium">Campaign Name</label>
                                    <input v-model="form.name" type="text" placeholder="Promo Ramadhan 2026" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white" required>
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-sm font-medium">Sender Account</label>
                                    <Multiselect
                                        v-model="form.whatsapp_account_id"
                                        :options="accounts.map(a => ({ value: a.id, label: `${a.name} (${a.phone_number})` }))"
                                        placeholder="Select Account"
                                        searchable
                                        class="mt-1"
                                    />
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium mb-1">Message Content / Template Name</label>
                                    <div class="bg-white dark:bg-gray-700 rounded-md overflow-hidden">
                                        <QuillEditor 
                                            v-model:content="form.message_template" 
                                            contentType="html" 
                                            theme="snow" 
                                            placeholder="Tulis pesan blast Anda di sini..."
                                            class="min-h-[150px] dark:text-white"
                                        />
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 italic">*Jika menggunakan Meta Official, isi dengan nama template.</p>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium">Recipient IDs (Comma separated for now)</label>
                                    <input type="text" @change="form.customer_ids = $event.target.value.split(',')" placeholder="1, 2, 3" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
                                    <p class="text-xs text-gray-500 mt-1 italic">*Masukkan ID customer, dipisahkan koma. Di versi selanjutnya akan menggunakan filter group.</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" :disabled="form.processing" class="bg-green-600 text-white px-6 py-2 rounded text-xs font-bold uppercase shadow hover:bg-green-700 transition w-full md:w-auto">
                                    Save Campaign Draft
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Campaigns List -->
                    <div class="block w-full overflow-x-auto">
                        <table class="items-center w-full bg-transparent border-collapse">
                            <thead>
                                <tr>
                                    <th class="px-6 align-middle border border-solid py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left bg-gray-50 dark:bg-gray-700 dark:border-gray-600">Campaign Name</th>
                                    <th class="px-6 align-middle border border-solid py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left bg-gray-50 dark:bg-gray-700 dark:border-gray-600">Sender</th>
                                    <th class="px-6 align-middle border border-solid py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left bg-gray-50 dark:bg-gray-700 dark:border-gray-600">Status</th>
                                    <th class="px-6 align-middle border border-solid py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left bg-gray-50 dark:bg-gray-700 dark:border-gray-600">Progress</th>
                                    <th class="px-6 align-middle border border-solid py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left bg-gray-50 dark:bg-gray-700 dark:border-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="campaign in campaigns" :key="campaign.id">
                                    <td class="px-6 py-4 text-sm font-semibold">{{ campaign.name }}</td>
                                    <td class="px-6 py-4 text-sm">{{ campaign.account?.name }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-2 py-1 rounded text-[10px] font-bold uppercase" :class="{
                                            'bg-gray-100 text-gray-600': campaign.status === 'draft',
                                            'bg-blue-100 text-blue-600': campaign.status === 'processing',
                                            'bg-green-100 text-green-600': campaign.status === 'completed',
                                        }">{{ campaign.status }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        {{ campaign.sent_count }} / {{ campaign.total_recipients }} Sent
                                        <div v-if="campaign.failed_count > 0" class="text-red-500 text-[10px]">({{ campaign.failed_count }} Failed)</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm flex gap-2">
                                        <button v-if="campaign.status === 'draft'" @click="processCampaign(campaign.id)" class="bg-blue-600 text-white px-3 py-1 rounded text-[10px] font-bold uppercase shadow hover:bg-blue-700">Process</button>
                                        <span v-else class="text-gray-400 italic">No Actions</span>
                                    </td>
                                </tr>
                                <tr v-if="campaigns.length === 0">
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-500 italic">No marketing campaigns found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

