<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { database } from '@/firebase';
import { ref as dbRef, onValue } from "firebase/database";

// Global variable to persist across page navigations (Inertia re-mounts)
let globalLastSoundPlayedMessageId = null;
let globalAudio = null;

const collapseShow = ref("hidden");
const isDark = ref(true); // Default dark
const realTimeUnreadCount = ref(null);
const showPermissionPrompt = ref(false);
let unsubInbox = null;
let unsubNotif = null;

const page = usePage();

const playNotificationSound = () => {
    if (!globalAudio) {
        globalAudio = new Audio('/sound/sound.mp3');
    }
    console.log('[PT. Tigasatu Cipta Solusi] Memutar suara notifikasi...');
    globalAudio.play().catch(e => {
        console.warn('[PT. Tigasatu Cipta Solusi] Suara gagal diputar (biasanya karena belum ada interaksi user di halaman ini):', e);
        showPermissionPrompt.value = true;
    });
};

const hasRole = (role) => page.props.auth.user.roles.includes(role);
const hasPermission = (permission) => page.props.auth.user.permissions.includes(permission);

const requestPermissions = async () => {
    // 1. Request Notification Permission
    if ("Notification" in window) {
        try {
            const permission = await Notification.requestPermission();
            console.log('[PT. Tigasatu Cipta Solusi] Notification permission:', permission);
        } catch (e) {
            console.error('[PT. Tigasatu Cipta Solusi] Error requesting notification permission:', e);
        }
    }

    // 2. Unlock Audio
    // Create audio if not exists
    if (!globalAudio) {
        globalAudio = new Audio('/sound/sound.mp3');
    }
    
    // Play a very short sound to unlock audio context
    globalAudio.muted = true; // Mute first to be safe
    globalAudio.play().then(() => {
        globalAudio.pause();
        globalAudio.muted = false;
        globalAudio.currentTime = 0;
        console.log('[PT. Tigasatu Cipta Solusi] Audio system unlocked successfully');
        showPermissionPrompt.value = false;
        
        // Optional: Save to session storage that user has dismissed/enabled for this session
        sessionStorage.setItem('tigasatu_audio_unlocked', 'true');
    }).catch(e => {
        console.error('[PT. Tigasatu Cipta Solusi] Gagal unlock audio:', e);
        // Even if it fails, we close the prompt to not annoy the user, 
        // it will reappear if playNotificationSound fails again later
        showPermissionPrompt.value = false;
    });
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

onMounted(() => {
    // Check if we should show prompt
    const isAudioUnlocked = sessionStorage.getItem('tigasatu_audio_unlocked');
    
    if (("Notification" in window && Notification.permission !== 'granted') || !isAudioUnlocked) {
        // Delay slightly for better UX
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

    // Global notification sound listener
    if (page.props.auth.user) {
        const userInboxRef = dbRef(database, `inbox/users/${page.props.auth.user.id}`);
        unsubInbox = onValue(userInboxRef, (snapshot) => {
            const data = snapshot.val();
            if (data) {
                const incomingData = Object.values(data);
                
                // Temukan pesan terbaru dari customer untuk play sound
                const latestCustomerMessage = incomingData
                    .map(d => d.message)
                    .filter(m => m.sender_type === 'customer')
                    .sort((a, b) => b.id - a.id)[0];

                if (latestCustomerMessage && latestCustomerMessage.id !== globalLastSoundPlayedMessageId) {
                    // Hanya bunyikan suara jika bukan loading pertama kali (saat web pertama kali dibuka)
                    if (globalLastSoundPlayedMessageId !== null) {
                        playNotificationSound();
                    }
                    globalLastSoundPlayedMessageId = latestCustomerMessage.id;
                }
            }
        });

        // Global unread count listener
        const userNotificationRef = dbRef(database, `notifications/users/${page.props.auth.user.id}`);
        unsubNotif = onValue(userNotificationRef, (snapshot) => {
            const data = snapshot.val();
            if (data && typeof data.unread_count !== 'undefined') {
                realTimeUnreadCount.value = data.unread_count;
            }
        });
    }
});

onUnmounted(() => {
    if (unsubInbox) unsubInbox();
    if (unsubNotif) unsubNotif();
});
</script>

<template>
  <div>
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
                <Link :href="route('whatsapp.settings.index')" 
                    class="text-xs uppercase py-2 font-bold block transition-colors duration-200"
                    :class="route().current('whatsapp.settings.*') ? 'text-operra-500' : 'text-gray-700 dark:text-gray-300 hover:text-operra-500'">
                    WA Multi-Account
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
                <Link :href="route('crm.chat.index')" class="text-white hover:text-operra-200 transition-colors duration-200 relative block">
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
                </Link>
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
