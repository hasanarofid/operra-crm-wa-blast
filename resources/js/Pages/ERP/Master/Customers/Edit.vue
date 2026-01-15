<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    customer: Object,
    sales: Array
});

const form = useForm({
    name: props.customer.name,
    email: props.customer.email,
    phone: props.customer.phone,
    address: props.customer.address,
    status: props.customer.status,
    lead_source: props.customer.lead_source,
    assigned_to: props.customer.assigned_to,
});

const submit = () => {
    form.put(route('master.customers.update', props.customer.id));
};
</script>

<template>
    <Head title="Edit Lead Assignment" />

    <AuthenticatedLayout>
        <template #header>
            CRM: Edit Lead Assignment
        </template>

        <div class="flex flex-wrap mt-4">
            <div class="w-full px-4">
                <div class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded">
                    <div class="rounded-t mb-0 px-4 py-3 border-0">
                        <h3 class="font-bold text-base text-gray-700 dark:text-gray-200">Lead & Sales Assignment</h3>
                    </div>
                    <div class="p-6 border-t border-gray-100 dark:border-gray-700">
                        <form @submit.prevent="submit" class="max-w-2xl">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                                    <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-operra-500 focus:ring-operra-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Address</label>
                                    <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-operra-500 focus:ring-operra-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone Number</label>
                                    <input v-model="form.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-operra-500 focus:ring-operra-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Lead Source</label>
                                    <Multiselect
                                        v-model="form.lead_source"
                                        :options="[
                                            { value: 'manual', label: 'Manual Entry' },
                                            { value: 'whatsapp', label: 'WhatsApp' },
                                            { value: 'website', label: 'Website' },
                                            { value: 'organic', label: 'Organic' }
                                        ]"
                                        placeholder="Select Source"
                                        searchable
                                    />
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign to Sales</label>
                                    <Multiselect
                                        v-model="form.assigned_to"
                                        :options="sales.map(s => ({ value: s.id, label: s.name }))"
                                        placeholder="Select Sales"
                                        searchable
                                    />
                                    <p class="mt-1 text-[10px] text-operra-600 font-semibold italic">*Ubah bagian ini untuk memindahkan lead ke sales lain.</p>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
                                <textarea v-model="form.address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-operra-500 focus:ring-operra-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                            </div>

                            <div class="mt-6 flex gap-4">
                                <button type="submit" :disabled="form.processing" class="bg-operra-600 text-white px-6 py-2 rounded-md font-bold uppercase text-xs shadow hover:bg-operra-700 transition-colors">
                                    Update & Reassign
                                </button>
                                <Link :href="route('master.customers.index')" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-md font-bold uppercase text-xs shadow hover:bg-gray-300 transition-colors">
                                    Cancel
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

