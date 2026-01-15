<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    statuses: Array
});

const form = useForm({
    id: null,
    name: '',
    color: '#94a3b8',
    order: 0,
});

const isEditing = ref(false);

const submit = () => {
    if (isEditing.value) {
        form.put(route('customer-statuses.update', form.id), {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(route('customer-statuses.store'), {
            onSuccess: () => resetForm(),
        });
    }
};

const editStatus = (status) => {
    isEditing.value = true;
    form.id = status.id;
    form.name = status.name;
    form.color = status.color;
    form.order = status.order;
};

const deleteStatus = (id) => {
    if (confirm('Are you sure you want to delete this status?')) {
        form.delete(route('customer-statuses.destroy', id));
    }
};

const resetForm = () => {
    isEditing.value = false;
    form.reset();
};
</script>

<template>
    <Head title="Lead Statuses" />

    <AuthenticatedLayout>
        <template #header>
            Manage Lead Statuses
        </template>

        <div class="flex flex-wrap mt-4">
            <!-- Form -->
            <div class="w-full lg:w-1/3 px-4">
                <div class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded">
                    <div class="rounded-t mb-0 px-4 py-3 border-0">
                        <h3 class="font-bold text-base text-gray-700 dark:text-gray-200">
                            {{ isEditing ? 'Edit Status' : 'Add New Status' }}
                        </h3>
                    </div>
                    <div class="p-6 border-t border-gray-100 dark:border-gray-700">
                        <form @submit.prevent="submit">
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium">Status Name</label>
                                    <input v-model="form.name" type="text" placeholder="e.g. prospect" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">UI Color</label>
                                    <div class="flex gap-2 mt-1">
                                        <input v-model="form.color" type="color" class="h-10 w-12 rounded border-gray-300">
                                        <input v-model="form.color" type="text" class="flex-1 rounded-md border-gray-300 dark:bg-gray-700 dark:text-white uppercase">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Display Order</label>
                                    <input v-model="form.order" type="number" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                            <div class="mt-6 flex gap-2">
                                <button type="submit" :disabled="form.processing" class="bg-operra-600 text-white px-4 py-2 rounded text-xs font-bold uppercase shadow hover:bg-operra-700 transition">
                                    {{ isEditing ? 'Update Status' : 'Create Status' }}
                                </button>
                                <button v-if="isEditing" @click="resetForm" type="button" class="bg-gray-500 text-white px-4 py-2 rounded text-xs font-bold uppercase shadow hover:bg-gray-600 transition">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- List -->
            <div class="w-full lg:w-2/3 px-4">
                <div class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded">
                    <div class="rounded-t mb-0 px-4 py-3 border-0">
                        <h3 class="font-bold text-base text-gray-700 dark:text-gray-200">Statuses List</h3>
                    </div>
                    <div class="block w-full overflow-x-auto">
                        <table class="items-center w-full bg-transparent border-collapse">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-xs uppercase font-semibold text-left bg-gray-50 dark:bg-gray-700">Order</th>
                                    <th class="px-6 py-3 text-xs uppercase font-semibold text-left bg-gray-50 dark:bg-gray-700">Name</th>
                                    <th class="px-6 py-3 text-xs uppercase font-semibold text-left bg-gray-50 dark:bg-gray-700">Preview</th>
                                    <th class="px-6 py-3 text-xs uppercase font-semibold text-left bg-gray-50 dark:bg-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="status in statuses" :key="status.id" class="border-t border-gray-100 dark:border-gray-700">
                                    <td class="px-6 py-4 text-sm">{{ status.order }}</td>
                                    <td class="px-6 py-4 text-sm font-bold uppercase">{{ status.name }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <span :style="{ backgroundColor: status.color }" class="px-2 py-1 rounded text-white text-[10px] font-bold uppercase shadow-sm">
                                            {{ status.name }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm flex gap-3">
                                        <button @click="editStatus(status)" class="text-blue-600 hover:underline font-bold uppercase text-xs">Edit</button>
                                        <button @click="deleteStatus(status.id)" class="text-red-600 hover:underline font-bold uppercase text-xs">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

