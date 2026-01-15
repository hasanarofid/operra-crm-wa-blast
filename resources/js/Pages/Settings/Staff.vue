<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Array,
    roles: Array,
    whatsappAccounts: Array,
});

const form = useForm({
    id: null,
    name: '',
    email: '',
    password: '',
    role: 'sales',
    whatsapp_account_id: '',
});

const isEditing = ref(false);

const submit = () => {
    if (isEditing.value) {
        form.put(route('staff.update', form.id), {
            onSuccess: () => {
                resetForm();
                alert('Staff updated successfully!');
            },
        });
    } else {
        form.post(route('staff.store'), {
            onSuccess: () => {
                resetForm();
                alert('Staff created successfully!');
            },
        });
    }
};

const editUser = (user) => {
    isEditing.value = true;
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.password = ''; // Keep empty unless changing
    form.role = user.roles[0]?.name || 'sales';
    form.whatsapp_account_id = user.whatsapp_agents[0]?.whatsapp_account_id || '';
};

const deleteUser = (id) => {
    if (confirm('Are you sure you want to delete this staff member?')) {
        form.delete(route('staff.destroy', id), {
            onSuccess: () => alert('Staff deleted!'),
        });
    }
};

const resetForm = () => {
    isEditing.value = false;
    form.reset();
};
</script>

<template>
    <Head title="Staff Management" />

    <AuthenticatedLayout>
        <template #header>
            Staff Management
        </template>

        <div class="flex flex-wrap mt-4">
            <!-- Add/Edit Form -->
            <div class="w-full lg:w-1/3 px-4">
                <div class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded">
                    <div class="rounded-t mb-0 px-4 py-3 border-0">
                        <h3 class="font-bold text-base text-gray-700 dark:text-gray-200">
                            {{ isEditing ? 'Edit Staff Member' : 'Add New Staff' }}
                        </h3>
                    </div>
                    <div class="p-6 border-t border-gray-100 dark:border-gray-700">
                        <form @submit.prevent="submit">
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium">Full Name</label>
                                    <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Email Address</label>
                                    <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Password {{ isEditing ? '(Leave blank to keep current)' : '' }}</label>
                                    <input v-model="form.password" type="password" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white" :required="!isEditing">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Role</label>
                                    <Multiselect
                                        v-model="form.role"
                                        :options="roles.map(r => ({ value: r.name, label: r.name.toUpperCase() }))"
                                        placeholder="Select Role"
                                        searchable
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Linked WhatsApp Account (Optional)</label>
                                    <Multiselect
                                        v-model="form.whatsapp_account_id"
                                        :options="whatsappAccounts.map(a => ({ value: a.id, label: `${a.name} (${a.phone_number})` }))"
                                        placeholder="None / Unlinked"
                                        searchable
                                    />
                                    <p class="mt-1 text-xs text-gray-500 italic">Menghubungkan user ini dengan nomor WhatsApp tertentu untuk membalas chat.</p>
                                </div>
                            </div>
                            <div class="mt-6 flex gap-2">
                                <button type="submit" :disabled="form.processing" class="bg-operra-600 text-white px-4 py-2 rounded text-xs font-bold uppercase shadow hover:bg-operra-700 transition">
                                    {{ isEditing ? 'Update Staff' : 'Create Staff' }}
                                </button>
                                <button v-if="isEditing" @click="resetForm" type="button" class="bg-gray-500 text-white px-4 py-2 rounded text-xs font-bold uppercase shadow hover:bg-gray-600 transition">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- List Table -->
            <div class="w-full lg:w-2/3 px-4">
                <div class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded">
                    <div class="rounded-t mb-0 px-4 py-3 border-0">
                        <h3 class="font-bold text-base text-gray-700 dark:text-gray-200">Staff & Sales List</h3>
                    </div>
                    <div class="block w-full overflow-x-auto">
                        <table class="items-center w-full bg-transparent border-collapse">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-xs uppercase font-semibold text-left bg-gray-50 dark:bg-gray-700">Name</th>
                                    <th class="px-6 py-3 text-xs uppercase font-semibold text-left bg-gray-50 dark:bg-gray-700">Email</th>
                                    <th class="px-6 py-3 text-xs uppercase font-semibold text-left bg-gray-50 dark:bg-gray-700">Role</th>
                                    <th class="px-6 py-3 text-xs uppercase font-semibold text-left bg-gray-50 dark:bg-gray-700">WA Account</th>
                                    <th class="px-6 py-3 text-xs uppercase font-semibold text-left bg-gray-50 dark:bg-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users" :key="user.id" class="border-t border-gray-100 dark:border-gray-700">
                                    <td class="px-6 py-4 text-sm font-bold">{{ user.name }}</td>
                                    <td class="px-6 py-4 text-sm">{{ user.email }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <span v-for="role in user.roles" :key="role.id" class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full uppercase font-bold mr-1">
                                            {{ role.name }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div v-for="agent in user.whatsapp_agents" :key="agent.id">
                                            {{ agent.whatsapp_account?.name || 'Unknown' }}
                                        </div>
                                        <span v-if="user.whatsapp_agents.length === 0" class="text-gray-400 italic text-xs">Unlinked</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm flex gap-3">
                                        <button @click="editUser(user)" class="text-blue-600 hover:underline font-bold uppercase text-xs">Edit</button>
                                        <button @click="deleteUser(user.id)" class="text-red-600 hover:underline font-bold uppercase text-xs">Delete</button>
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

