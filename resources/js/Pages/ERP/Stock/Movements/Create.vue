<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    products: Array,
    warehouses: Array
});

const form = useForm({
    product_id: '',
    warehouse_id: '',
    quantity: 0,
    type: 'in',
    reference: '',
});

const submit = () => {
    form.post(route('stock.movements.store'));
};
</script>

<template>
    <Head title="Record Stock Movement" />

    <AuthenticatedLayout>
        <template #header>
            Record Stock Movement
        </template>

        <div class="flex flex-wrap mt-4">
            <div class="w-full px-4">
                <div class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded">
                    <div class="rounded-t mb-0 px-4 py-3 border-0">
                        <h3 class="font-bold text-base text-gray-700 dark:text-gray-200">Movement Details</h3>
                    </div>
                    <div class="p-6 border-t border-gray-100 dark:border-gray-700">
                        <form @submit.prevent="submit" class="max-w-xl">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product</label>
                                <select v-model="form.product_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-operra-500 focus:ring-operra-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                    <option value="">Select Product</option>
                                    <option v-for="product in products" :key="product.id" :value="product.id">
                                        {{ product.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.product_id" class="text-red-500 text-xs mt-1">{{ form.errors.product_id }}</div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Warehouse</label>
                                <select v-model="form.warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-operra-500 focus:ring-operra-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                    <option value="">Select Warehouse</option>
                                    <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                                        {{ warehouse.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.warehouse_id" class="text-red-500 text-xs mt-1">{{ form.errors.warehouse_id }}</div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quantity</label>
                                <input v-model="form.quantity" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-operra-500 focus:ring-operra-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                <p class="text-xs text-gray-500 mt-1">Gunakan angka negatif untuk stok keluar (out).</p>
                                <div v-if="form.errors.quantity" class="text-red-500 text-xs mt-1">{{ form.errors.quantity }}</div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                                <select v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-operra-500 focus:ring-operra-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                    <option value="in">In (Stok Masuk)</option>
                                    <option value="out">Out (Stok Keluar)</option>
                                    <option value="mutation">Mutation (Mutasi)</option>
                                </select>
                                <div v-if="form.errors.type" class="text-red-500 text-xs mt-1">{{ form.errors.type }}</div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reference</label>
                                <input v-model="form.reference" type="text" placeholder="e.g. Purchase Order #123, Initial Stock" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-operra-500 focus:ring-operra-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <div v-if="form.errors.reference" class="text-red-500 text-xs mt-1">{{ form.errors.reference }}</div>
                            </div>

                            <div class="mt-6 flex gap-4">
                                <button type="submit" :disabled="form.processing" class="bg-operra-500 text-white px-4 py-2 rounded-md font-bold uppercase text-xs shadow hover:bg-operra-600 transition-colors">
                                    Record Movement
                                </button>
                                <Link :href="route('stock.movements.index')" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md font-bold uppercase text-xs shadow hover:bg-gray-300 transition-colors">
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

