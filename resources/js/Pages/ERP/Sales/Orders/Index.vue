<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    orders: Object
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};
</script>

<template>
    <Head title="Sales Orders" />

    <AuthenticatedLayout>
        <template #header>
            Sales Orders
        </template>

        <div class="flex flex-wrap mt-4">
            <div class="w-full px-4">
                <div class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded">
                    <div class="rounded-t mb-0 px-4 py-3 border-0">
                        <div class="flex flex-wrap items-center">
                            <div class="relative w-full px-4 max-w-full flex-grow flex-1">
                                <h3 class="font-bold text-base text-gray-700 dark:text-gray-200">Orders List</h3>
                            </div>
                            <div class="relative w-full px-4 max-w-full flex-grow flex-1 text-right">
                                <Link :href="route('sales.orders.create')" class="bg-operra-500 text-white active:bg-operra-600 text-xs font-bold uppercase px-3 py-1 rounded outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150 shadow hover:shadow-md">
                                    Create Order
                                </Link>
                            </div>
                        </div>
                    </div>
                    <div class="block w-full overflow-x-auto">
                        <table class="items-center w-full bg-transparent border-collapse">
                            <thead>
                                <tr>
                                    <th class="px-6 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-300 align-middle border border-solid border-gray-100 dark:border-gray-600 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">SO Number</th>
                                    <th class="px-6 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-300 align-middle border border-solid border-gray-100 dark:border-gray-600 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">Customer</th>
                                    <th class="px-6 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-300 align-middle border border-solid border-gray-100 dark:border-gray-600 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">Date</th>
                                    <th class="px-6 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-300 align-middle border border-solid border-gray-100 dark:border-gray-600 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">Amount</th>
                                    <th class="px-6 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-300 align-middle border border-solid border-gray-100 dark:border-gray-600 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">Status</th>
                                    <th class="px-6 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-300 align-middle border border-solid border-gray-100 dark:border-gray-600 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="order in orders.data" :key="order.id">
                                    <th class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-left font-bold text-gray-700 dark:text-gray-200">{{ order.so_number }}</th>
                                    <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-gray-600 dark:text-gray-400">{{ order.customer?.name }}</td>
                                    <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-gray-700 dark:text-gray-200">{{ order.order_date }}</td>
                                    <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-gray-700 dark:text-gray-200">{{ formatCurrency(order.total_amount) }}</td>
                                    <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                                        <span :class="[
                                            'px-2 py-1 rounded-full text-xs font-semibold',
                                            order.status === 'confirmed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'
                                        ]">
                                            {{ order.status }}
                                        </span>
                                    </td>
                                    <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                                        <a v-if="order.invoice" :href="route('sales.invoices.pdf', order.invoice.id)" target="_blank" class="text-blue-500 hover:text-blue-700 font-bold uppercase text-xs">
                                            Print Invoice
                                        </a>
                                        <span v-else class="text-gray-400 italic">No Invoice</span>
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

