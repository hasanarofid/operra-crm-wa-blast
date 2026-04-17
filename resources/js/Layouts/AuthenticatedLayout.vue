<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { socket, joinUserRoom } from '@/socket';

// Global variable to persist across page navigations (Inertia re-mounts)
let globalLastSoundPlayedMessageId = null;

const collapseShow = ref("hidden");
const isDark = ref(true); // Default dark
const realTimeUnreadCount = ref(null);
const notifications = ref([]);
const showPermissionPrompt = ref(false);
let unsubInbox = null;
let unsubNotif = null;

const page = usePage();

const notificationAudio = ref(null);

const playNotificationSound = () => {
    if (notificationAudio.value) {
        notificationAudio.value.currentTime = 0;
        notificationAudio.value.play().catch(e => {
            console.warn('[PT. Tigasatu Cipta Solusi] Suara gagal diputar (biasanya karena belum ada interaksi user di halaman ini):', e);
            showPermissionPrompt.value = true;
        });
    }
};

const hasRole = (role) => page.props.auth.user.roles.includes(role);
const hasPermission = (permission) => page.props.auth.user.permissions.includes(permission);

const urlBase64ToUint8Array = (base64String) => {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
};

const subscribeToPush = async () => {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
    
    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(page.props.vapidPublicKey)
        });

        await axios.post(route('push.subscribe'), subscription);
        console.log('[Operra] Push Subscription successful');
    } catch (e) {
        console.warn('[Operra] Push Subscription failed:', e);
    }
};

const manualUnlockAudio = () => {
    if (notificationAudio.value && !sessionStorage.getItem('tigasatu_audio_unlocked')) {
        notificationAudio.value.muted = true;
        notificationAudio.value.play().then(() => {
            notificationAudio.value.pause();
            notificationAudio.value.muted = false;
            notificationAudio.value.currentTime = 0;
            console.log('[PT. Tigasatu Cipta Solusi] Audio system unlocked by user interaction');
            showPermissionPrompt.value = false;
            sessionStorage.setItem('tigasatu_audio_unlocked', 'true');
            // Remove listener after first successful unlock
            document.removeEventListener('click', manualUnlockAudio);
            document.removeEventListener('touchstart', manualUnlockAudio);
        }).catch(e => {
            console.warn('[PT. Tigasatu Cipta Solusi] Interaction occurred but audio still locked:', e);
        });
    }
};

const requestPermissions = async () => {
    // 1. Service Worker Registration
    if ('serviceWorker' in navigator) {
        await navigator.serviceWorker.register('/sw.js');
    }

    // 2. Request Notification Permission
    if ("Notification" in window) {
        try {
            const permission = await Notification.requestPermission();
            console.log('[Operra] Notification permission:', permission);
            if (permission === 'granted') {
                await subscribeToPush();
            }
        } catch (e) {
            console.error('[Operra] Error requesting notification permission:', e);
        }
    }

    // 2. Unlock Audio manually
    manualUnlockAudio();
};

function toggleCollapseShow(classes) {
  collapseShow.value = classes;
}

function toggleTheme() {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
}

const fetchNotifications = async () => {
    try {
        const response = await axios.get(route('notifications.recent'));
        notifications.value = response.data.notifications;
        realTimeUnreadCount.value = response.data.unread_count;
    } catch (error) {
        console.warn('[Operra] Failed to fetch notifications', error);
    }
};

const goToChat = (notif) => {
    window.location.href = route('crm.chat.index', { customer_id: notif.chat_session.customer_id });
};

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

onMounted(() => {
    const isAudioUnlocked = sessionStorage.getItem('tigasatu_audio_unlocked');
    
    if (("Notification" in window && Notification.permission !== 'granted') || !isAudioUnlocked) {
        setTimeout(() => {
            showPermissionPrompt.value = true;
        }, 1500);
    }

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'light') {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    } else {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    }

    if (page.props.auth.user) {
        joinUserRoom(page.props.auth.user.id);
        fetchNotifications();

        // Add one-time listeners to unlock audio on first interaction
        if (!sessionStorage.getItem('tigasatu_audio_unlocked')) {
            document.addEventListener('click', manualUnlockAudio);
            document.addEventListener('touchstart', manualUnlockAudio);
        }

        socket.on('new_message', (data) => {
            const payload = data.data || data;
            const { message, unread_count } = payload;
            
            console.log('[Operra] Event real-time diterima:', payload);
            
            // Update unread count global
            if (typeof unread_count !== 'undefined') {
                realTimeUnreadCount.value = unread_count;
            }

            // Hanya mainkan suara jika pengirim BUKAN diri sendiri
            if (message && message.sender_id !== page.props.auth.user.id) {
                console.log('[Operra] Memainkan suara notifikasi...');
                playNotificationSound();
                
                // Ensure notification has session info for the dropdown link
                if (!message.chat_session && payload.session) {
                    message.chat_session = payload.session;
                }
                
                notifications.value.unshift(message);
                if (notifications.value.length > 5) {
                    notifications.value.pop();
                }
            }
        });

        socket.on('messages_read', (data) => {
            if (typeof data.unread_count !== 'undefined') {
                realTimeUnreadCount.value = data.unread_count;
            }
        });
    }
});

onUnmounted(() => {
    socket.off('new_message');
    socket.off('messages_read');
});
</script>

<template>
  <div>
    <div style="position: absolute; left: -9999px; top: -9999px;">
        <input type="text" name="username_fake" autocomplete="username" tabindex="-1">
        <input type="password" name="password_fake" autocomplete="new-password" tabindex="-1">
    </div>

    <audio ref="notificationAudio" src="/sound/sound.mp3" preload="auto"></audio>

    <!-- Sidebar -->
    <nav class="md:left-0 md:block md:fixed md:top-0 md:bottom-0 md:overflow-y-auto md:flex-row md:flex-nowrap md:overflow-hidden shadow-xl bg-white dark:bg-gray-800 flex flex-wrap items-center justify-between relative md:w-64 z-50 py-4 px-6">
      <div class="md:flex-col md:items-stretch md:min-h-full md:flex-nowrap px-0 flex flex-wrap items-center justify-between w-full mx-auto">
        <!-- Toggler -->
        <button
          class="cursor-pointer text-gray-500 md:hidden px-3 py-1 text-xl leading-none bg-transparent rounded border border-solid border-transparent"
          type="button"
          v-on:click="toggleCollapseShow('bg-white dark:bg-gray-800 shadow-2xl p-6')"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
        </button>
        <!-- Brand -->
        <Link
          class="md:block text-left md:pb-2 text-gray-600 dark:text-gray-200 mr-0 inline-block whitespace-nowrap text-sm uppercase font-bold p-4 px-0"
          :href="route('dashboard')"
        >
          <div class="flex items-center gap-2">
            <ApplicationLogo class="h-8 w-auto" />
          </div>
        </Link>
        <!-- User Mobile -->
        <ul class="md:hidden items-center flex flex-wrap list-none gap-2">
          <li class="inline-block relative">
             <Link :href="route('crm.chat.index')" class="text-gray-500 hover:text-operra-500 block py-1 px-2 transition-colors relative">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                <!-- Notification Badge Mobile -->
                <span v-if="(realTimeUnreadCount ?? $page.props.unreadCount) > 0" class="absolute top-0 right-0 flex h-3.5 w-3.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-red-500 text-[8px] items-center justify-center font-bold text-white">
                        {{ realTimeUnreadCount ?? $page.props.unreadCount }}
                    </span>
                </span>
             </Link>
          </li>
          <li class="inline-block relative">
            <button @click="toggleTheme" class="text-gray-500 hover:text-operra-500 block py-1 px-2 transition-colors">
                <svg v-if="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 18v1m9-9h1m-18 0H2m3.364-7.364l-.707-.707m12.728 12.728l-.707-.707M6.343 17.657l-.707.707M17.657 6.343l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
            </button>
          </li>
          <li class="inline-block relative">
            <Dropdown align="right" width="48">
                <template #trigger>
                    <button class="text-gray-500 hover:text-operra-500 block py-1 px-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </button>
                </template>
                <template #content>
                    <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                    <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                </template>
            </Dropdown>
          </li>
        </ul>
        <!-- Collapse -->
        <div
          class="md:flex md:flex-col md:items-stretch md:opacity-100 md:relative md:mt-4 md:shadow-none shadow absolute top-0 left-0 right-0 z-40 overflow-y-auto overflow-x-hidden h-auto items-center flex-1 rounded"
          v-bind:class="collapseShow"
        >
          <!-- Collapse header -->
          <div class="md:min-w-full md:hidden block pb-4 mb-4 border-b border-solid border-gray-200 dark:border-gray-700">
            <div class="flex flex-wrap items-center">
              <div class="w-6/12">
                <Link
                  class="md:block text-left md:pb-2 text-gray-600 dark:text-gray-200 mr-0 inline-block whitespace-nowrap text-sm uppercase font-bold p-4 px-0"
                  :href="route('dashboard')"
                >
                  <div class="flex items-center gap-2">
                    <ApplicationLogo class="h-6 w-auto" />
                  </div>
                </Link>
              </div>
              <div class="w-6/12 flex justify-end">
                <button
                  type="button"
                  class="cursor-pointer text-gray-500 md:hidden px-3 py-1 text-xl leading-none bg-transparent rounded border border-solid border-transparent"
                  v-on:click="toggleCollapseShow('hidden')"
                >
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
              </div>
            </div>
          </div>
          <!-- Divider -->
          <hr class="my-4 md:min-w-full" />
          <!-- Heading -->
          <h6 class="md:min-w-full text-gray-500 text-xs uppercase font-bold block pt-1 pb-4 no-underline">
            Main Navigation
          </h6>
          <!-- Navigation -->
          <ul class="md:flex-col md:min-w-full flex flex-col list-none">
            <li class="items-center">
              <Link
                :href="route('dashboard')"
                class="text-xs uppercase py-3 font-bold block transition-colors duration-200"
                :class="route().current('dashboard') ? 'text-operra-500 hover:text-operra-600' : 'text-gray-700 dark:text-gray-300 hover:text-operra-500'"
              >
                Dashboard
              </Link>
            </li>

            <!-- CRM Section -->
            <hr class="my-4 md:min-w-full" />
            <h6 class="md:min-w-full text-gray-500 text-xs uppercase font-bold block pt-1 pb-4 no-underline">
              CRM & Leads
            </h6>
            <li class="items-center">
                <Link :href="route('crm.chat.index')" 
                    class="text-xs uppercase py-2 font-bold block transition-colors duration-200"
                    :class="route().current('crm.chat.*') ? 'text-operra-500' : 'text-gray-700 dark:text-gray-300 hover:text-operra-500'">
                    Chat Inbox
                </Link>
            </li>
            <li class="items-center">
                <Link :href="route('master.customers.index')" 
                    class="text-xs uppercase py-2 font-bold block transition-colors duration-200"
                    :class="route().current('master.customers.*') ? 'text-operra-500' : 'text-gray-700 dark:text-gray-300 hover:text-operra-500'">
                    Manage Leads
                </Link>
            </li>
            <li v-if="hasRole('super-admin')" class="items-center">
                <Link :href="route('whatsapp.blast.index')" 
                    class="text-xs uppercase py-2 font-bold block transition-colors duration-200"
                    :class="route().current('whatsapp.blast.*') ? 'text-operra-500' : 'text-gray-700 dark:text-gray-300 hover:text-operra-500'">
                    WhatsApp Blast
                </Link>
            </li>
            <li v-if="hasRole('super-admin')" class="items-center">
                <Link :href="route('whatsapp.settings.index')" 
                    class="text-xs uppercase py-2 font-bold block transition-colors duration-200"
                    :class="route().current('whatsapp.settings.*') ? 'text-operra-500' : 'text-gray-700 dark:text-gray-300 hover:text-operra-500'">
                    WA Multi-Account
                </Link>
            </li>
            <li v-if="hasRole('super-admin')" class="items-center">
                <Link :href="route('whatsapp.media.index')" 
                    class="text-xs uppercase py-2 font-bold block transition-colors duration-200"
                    :class="route().current('whatsapp.media.*') ? 'text-operra-500' : 'text-gray-700 dark:text-gray-300 hover:text-operra-500'">
                    WA Media Gallery
                </Link>
            </li>
            <li v-if="hasRole('super-admin')" class="items-center">
                <Link :href="route('customer-statuses.index')" 
                    class="text-xs uppercase py-2 font-bold block transition-colors duration-200"
                    :class="route().current('customer-statuses.*') ? 'text-operra-500' : 'text-gray-700 dark:text-gray-300 hover:text-operra-500'">
                    Lead Statuses
                </Link>
            </li>
          </ul>

          <!-- Divider -->
          <hr class="my-4 md:min-w-full" />
          <!-- Heading -->
          <h6 class="md:min-w-full text-gray-500 text-xs uppercase font-bold block pt-1 pb-4 no-underline">
            Administrative
          </h6>
          <ul class="md:flex-col md:min-w-full flex flex-col list-none md:mb-4">
            <li v-if="hasRole('super-admin') || hasRole('manager')" class="items-center">
              <Link
                :href="route('settings.index')"
                class="text-xs uppercase py-2 font-bold block transition-colors duration-200"
                :class="route().current('settings.index') ? 'text-operra-500' : 'text-gray-700 dark:text-gray-300 hover:text-operra-500'"
              >
                Company Settings
              </Link>
            </li>
            <li v-if="hasRole('super-admin')" class="items-center">
              <Link
                :href="route('staff.index')"
                class="text-xs uppercase py-2 font-bold block transition-colors duration-200"
                :class="route().current('staff.*') ? 'text-operra-500' : 'text-gray-700 dark:text-gray-300 hover:text-operra-500'"
              >
                Manage Staff
              </Link>
            </li>
            <li v-if="hasRole('super-admin')" class="items-center">
              <Link
                :href="route('external-apps.index')"
                class="text-xs uppercase py-2 font-bold block transition-colors duration-200"
                :class="route().current('external-apps.*') ? 'text-operra-500' : 'text-gray-700 dark:text-gray-300 hover:text-operra-500'"
              >
                External Apps (Embed)
              </Link>
            </li>
            <li class="items-center">
              <Link
                :href="route('profile.edit')"
                class="text-gray-700 dark:text-gray-300 hover:text-operra-500 text-xs uppercase py-2 font-bold block transition-colors duration-200"
              >
                Profile Settings
              </Link>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <div class="relative md:ml-64 bg-gray-100 dark:bg-gray-900 min-h-screen transition-colors duration-300">
      
      <!-- Notification & Sound Permission Prompt (Floating Banner) -->
      <transition
        enter-active-class="transform transition ease-out duration-300"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
      >
        <div v-if="showPermissionPrompt" class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-[100] w-[90%] max-w-md">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-operra-500/30 p-5 flex flex-col items-center text-center gap-4">
                <div class="h-14 w-14 bg-operra-100 dark:bg-operra-900/30 rounded-full flex items-center justify-center text-operra-600 dark:text-operra-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="text-lg font-bold text-gray-800 dark:text-gray-100">Aktifkan Notifikasi & Suara</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Izinkan sistem mengirim notifikasi dan suara agar Anda tidak melewatkan pesan dari customer.
                    </p>
                </div>
                <div class="flex gap-3 w-full">
                    <button @click="showPermissionPrompt = false" class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        Nanti Saja
                    </button>
                    <button @click="requestPermissions" class="flex-1 py-2.5 rounded-xl text-sm font-semibold bg-operra-600 hover:bg-operra-700 text-white shadow-lg shadow-operra-600/20 transition-all active:scale-95">
                        Aktifkan Sekarang
                    </button>
                </div>
            </div>
        </div>
      </transition>

      <!-- Top Navbar -->
      <nav class="absolute top-0 left-0 w-full z-10 bg-transparent md:flex-row md:flex-nowrap md:justify-start flex items-center p-4">
        <div class="w-full mx-auto items-center flex justify-between md:flex-nowrap flex-wrap md:px-10 px-4">
          <!-- Brand -->
          <span class="text-white text-sm uppercase hidden lg:inline-block font-semibold">
            <slot name="header" />
          </span>
          <!-- User & Theme -->
           <ul class="flex-row list-none items-center hidden md:flex gap-4">
             <li class="inline-block relative">
                <Dropdown align="right" width="80">
                    <template #trigger>
                        <button class="text-white hover:text-operra-200 transition-colors duration-200 relative block">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <!-- Notification Badge -->
                            <span v-if="(realTimeUnreadCount ?? $page.props.unreadCount) > 0" class="absolute -top-1 -right-1 flex h-4 w-4">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 text-[10px] items-center justify-center font-bold text-white">
                                    {{ realTimeUnreadCount ?? $page.props.unreadCount }}
                                </span>
                            </span>
                        </button>
                    </template>
                    <template #content>
                        <div class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center rounded-t-lg">
                            <span class="text-[10px] font-bold uppercase text-gray-500">Pesan Terbaru</span>
                            <Link :href="route('crm.chat.index')" class="text-[10px] font-bold text-operra-500 hover:underline">Lihat Semua</Link>
                        </div>
                        <div class="max-h-96 overflow-y-auto custom-scrollbar">
                            <div v-if="notifications.length === 0" class="p-6 text-center text-gray-500 text-xs italic">
                                Belum ada pesan masuk
                            </div>
                            <div v-for="notif in notifications" :key="notif.id" 
                                @click="goToChat(notif)" 
                                class="p-3 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer transition-colors group">
                                <div class="flex gap-3">
                                    <div :class="['h-9 w-9 rounded-full flex items-center justify-center font-bold text-sm shrink-0 group-hover:scale-110 transition-transform', notif.chat_session?.peer_user_id ? 'bg-blue-100 text-blue-600' : 'bg-operra-100 text-operra-600']">
                                        {{ (notif.chat_session?.customer?.name || notif.sender?.name || '?').charAt(0) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-center mb-0.5">
                                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate pr-2">
                                                {{ notif.chat_session?.customer?.name || notif.sender?.name || 'Staff' }}
                                            </span>
                                            <span class="text-[9px] text-gray-400 whitespace-nowrap">{{ formatTime(notif.created_at) }}</span>
                                        </div>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ notif.message_body }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Dropdown>
             </li>
             <li class="inline-block relative">
                <button @click="toggleTheme" class="text-white hover:text-operra-200 transition-colors duration-200">
                    <svg v-if="isDark" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 18v1m9-9h1m-18 0H2m3.364-7.364l-.707-.707m12.728 12.728l-.707-.707M6.343 17.657l-.707.707M17.657 6.343l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                </button>
             </li>
             <li class="inline-block relative">
                <Dropdown align="right" width="48">
                    <template #trigger>
                        <button class="text-white block py-1 px-3 font-semibold hover:text-operra-200 transition-colors duration-200">
                            {{ $page.props.auth.user.name }}
                        </button>
                    </template>
                    <template #content>
                        <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                        <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                    </template>
                </Dropdown>
              </li>
          </ul>
        </div>
      </nav>

      <!-- Header / Stats Container -->
      <div class="relative bg-operra-600 md:pt-32 pb-32 pt-16 transition-colors duration-300">
        <div class="px-4 md:px-10 mx-auto w-full">
          <div>
            <!-- Header Stats Slots -->
            <slot name="stats" />
          </div>
        </div>
      </div>

      <!-- Main Page Content -->
      <div class="px-4 md:px-10 mx-auto w-full -mt-24 pb-12">
        <div class="relative flex flex-col min-w-0 break-words w-full mb-6 rounded-lg">
          <slot />
        </div>
        
        <footer class="block py-4">
          <div class="container mx-auto px-4">
            <hr class="mb-4 border-b-1 border-gray-200 dark:border-gray-700" />
            <div class="flex flex-wrap items-center md:justify-between justify-center">
              <div class="w-full md:w-4/12 px-4">
                <div class="text-sm text-gray-500 dark:text-gray-400 font-semibold py-1 text-center md:text-left">
                  © 2026 <a href="https://31ciptasolusi.co.id/" class="text-operra-500 hover:text-operra-700">PT. Tigasatu Cipta Solusi</a>
                </div>
              </div>
            </div>
          </div>
        </footer>
      </div>
    </div>
  </div>
</template>
