<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import StatCard from '@/Components/StatCard.vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    stats: Object,
    recentLeads: Array,
    recentChats: Array,
    chartData: Array,
    waAccounts: Array,
    userRole: String,
});

const chartOptions = {
    chart: {
        type: 'area',
        toolbar: { show: false },
        animations: { enabled: true },
        background: 'transparent'
    },
    stroke: { curve: 'smooth', width: 3 },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.45,
            opacityTo: 0.05,
            stops: [20, 100, 100, 100]
        }
    },
    dataLabels: { enabled: false },
    grid: { borderColor: '#f1f1f1', strokeDashArray: 4 },
    xaxis: {
        categories: props.chartData.map(d => d.date),
        labels: { style: { colors: '#9ca3af' } }
    },
    yaxis: { labels: { style: { colors: '#9ca3af' } } },
    colors: ['#0ea5e9'],
    theme: { mode: 'dark' }
};

const series = [{
    name: 'New Leads',
    data: props.chartData.map(d => d.count)
}];
</script>

<template>
    <Head title="Dashboard CRM" />

    <AuthenticatedLayout>
        <template #header>
            Dashboard CRM & WhatsApp Blast ({{ userRole }})
        </template>

        <template #stats>
            <!-- CRM KPI Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard 
                    title="Total Leads" 
                    :value="stats.total_leads"
                >
                    <template #icon>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </template>
                </StatCard>
                <StatCard 
                    title="Active Chats" 
                    :value="stats.active_chats"
                    :alert="stats.active_chats > 0"
                >
                    <template #icon>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                    </template>
                </StatCard>
                <StatCard 
                    title="New Leads Today" 
                    :value="stats.new_leads_today"
                >
                    <template #icon>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                    </template>
                </StatCard>
                <StatCard 
                    title="Messages Sent/Received" 
                    :value="stats.messages_today"
                >
                    <template #icon>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </template>
                </StatCard>
            </div>
        </template>

        <div class="flex flex-wrap mt-4 -mx-4">
            <div class="w-full xl:w-8/12 mb-12 xl:mb-0 px-4">
                <!-- Recent Leads Table -->
                <div class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded-xl overflow-hidden">
                    <div class="rounded-t mb-0 px-6 py-4 border-0">
                        <div class="flex flex-wrap items-center">
                            <div class="relative w-full max-w-full flex-grow flex-1">
                                <h3 class="font-bold text-lg text-gray-700 dark:text-gray-200">Recent Leads</h3>
                            </div>
                            <div class="relative w-full max-w-full flex-grow flex-1 text-right">
                                <Link :href="route('master.customers.index')" class="bg-operra-500 text-white active:bg-operra-600 text-xs font-bold uppercase px-4 py-2 rounded-lg shadow hover:shadow-md transition-all">
                                    Manage
                                </Link>
                            </div>
                        </div>
                    </div>
                    <div class="block w-full overflow-x-auto">
                        <table class="items-center w-full bg-transparent border-collapse">
                            <thead>
                                <tr>
                                    <th class="px-6 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-300 align-middle border border-solid border-gray-100 dark:border-gray-600 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-bold text-left">Name</th>
                                    <th class="px-6 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-300 align-middle border border-solid border-gray-100 dark:border-gray-600 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-bold text-left">Phone</th>
                                    <th class="px-6 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-300 align-middle border border-solid border-gray-100 dark:border-gray-600 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-bold text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="lead in recentLeads" :key="lead.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <th class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-left font-bold text-gray-700 dark:text-gray-200">{{ lead.name }}</th>
                                    <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-gray-600 dark:text-gray-400">{{ lead.phone }}</td>
                                    <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-gray-700 dark:text-gray-200">
                                        <span :class="[
                                            'px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                            lead.status === 'lead' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        ]">
                                            {{ lead.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Lead Growth Chart -->
                <div class="relative flex flex-col min-w-0 break-words bg-gray-800 w-full mb-6 shadow-lg rounded-xl overflow-hidden">
                    <div class="rounded-t mb-0 px-6 py-4 bg-transparent">
                        <div class="flex flex-wrap items-center">
                            <div class="relative w-full max-w-full flex-grow flex-1">
                                <h6 class="uppercase text-gray-400 mb-1 text-xs font-bold tracking-widest">Growth Analytics</h6>
                                <h2 class="text-white text-xl font-bold">New Leads Trend</h2>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 flex-auto">
                        <VueApexCharts height="350" :options="chartOptions" :series="series" />
                    </div>
                </div>
            </div>

            <div class="w-full xl:w-4/12 px-4">
                <!-- WA Accounts Status -->
                <div v-if="userRole !== 'sales'" class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded-xl overflow-hidden">
                    <div class="rounded-t mb-0 px-6 py-4 border-0">
                        <h3 class="font-bold text-lg text-gray-700 dark:text-gray-200">WhatsApp Accounts</h3>
                    </div>
                    <div class="px-6 pb-6">
                        <div v-for="account in waAccounts" :key="account.id" class="mb-4 p-4 bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-700 rounded-xl">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-bold text-sm text-gray-700 dark:text-gray-200">{{ account.name }}</p>
                                    <p class="text-xs text-gray-500">{{ account.phone_number }}</p>
                                </div>
                                <span :class="account.status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'" class="px-2 py-1 rounded-full text-[10px] font-bold uppercase">
                                    {{ account.status }}
                                </span>
                            </div>
                            <div class="mt-3 flex items-center gap-2 text-[10px] font-bold text-gray-500 uppercase tracking-tighter">
                                <svg class="w-3 h-3 text-operra-500" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" /></svg>
                                {{ account.agents_count }} Agents Connected
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Conversations -->
                <div class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded-xl overflow-hidden">
                    <div class="rounded-t mb-0 px-6 py-4 border-0">
                        <h3 class="font-bold text-lg text-gray-700 dark:text-gray-200">Recent Chats</h3>
                    </div>
                    <div class="px-6 pb-6">
                        <div v-for="chat in recentChats" :key="chat.id" class="mb-3 flex items-center gap-4 p-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors rounded-xl border border-transparent hover:border-gray-100 dark:hover:border-gray-700">
                            <div :class="['h-10 w-10 shrink-0 rounded-full flex items-center justify-center text-white text-sm font-black shadow-lg', chat.customer ? 'bg-gradient-to-br from-operra-500 to-indigo-600' : 'bg-gradient-to-br from-blue-500 to-indigo-700']">
                                {{ (chat.customer?.name || (chat.peer_user_id === $page.props.auth.user.id ? chat.assigned_user.name : chat.peer_user?.name) || 'S').charAt(0) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-700 dark:text-gray-200 truncate">
                                    {{ chat.customer?.name || (chat.peer_user_id === $page.props.auth.user.id ? chat.assigned_user.name : chat.peer_user?.name) }}
                                </p>
                                <p class="text-[10px] text-gray-500 truncate font-medium">By: {{ chat.assigned_user.name }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <span :class="[
                                    'px-2 py-1 rounded-full text-[9px] font-black uppercase tracking-wider',
                                    chat.status === 'open' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                ]">
                                    {{ chat.status }}
                                </span>
                                <p class="text-[9px] text-gray-400 mt-1 font-bold uppercase">{{ new Date(chat.last_message_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</p>
                            </div>
                        </div>
                        <div v-if="recentChats.length === 0" class="text-center py-10">
                            <div class="mb-2 flex justify-center text-gray-300 dark:text-gray-600">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                            </div>
                            <p class="text-gray-400 text-xs font-bold uppercase tracking-widest">No Active Conversations</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

