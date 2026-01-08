<script setup>
import { ref, onMounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const collapseShow = ref("hidden");
const isDark = ref(true); // Default dark

const page = usePage();
const hasRole = (role) => page.props.auth.user.roles.includes(role);
const hasPermission = (permission) => page.props.auth.user.permissions.includes(permission);

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
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'light') {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    } else {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    }
});
</script>

<template>
  <div>
    <!-- Sidebar -->
    <nav class="md:left-0 md:block md:fixed md:top-0 md:bottom-0 md:overflow-y-auto md:flex-row md:flex-nowrap md:overflow-hidden shadow-xl bg-white dark:bg-gray-800 flex flex-wrap items-center justify-between relative md:w-64 z-10 py-4 px-6">
      <div class="md:flex-col md:items-stretch md:min-h-full md:flex-nowrap px-0 flex flex-wrap items-center justify-between w-full mx-auto">
        <!-- Toggler -->
        <button
          class="cursor-pointer text-black opacity-50 md:hidden px-3 py-1 text-xl leading-none bg-transparent rounded border border-solid border-transparent"
          type="button"
          v-on:click="toggleCollapseShow('bg-white m-2 py-3 px-6')"
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
            <span class="text-operra-600 dark:text-operra-400">Operra Admin</span>
          </div>
        </Link>
        <!-- User -->
        <ul class="md:hidden items-center flex flex-wrap list-none">
          <li class="inline-block relative">
            <button @click="toggleTheme" class="text-gray-500 block py-1 px-3">
                <svg v-if="isDark" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 18v1m9-9h1m-18 0H2m3.364-7.364l-.707-.707m12.728 12.728l-.707-.707M6.343 17.657l-.707.707M17.657 6.343l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
            </button>
          </li>
          <li class="inline-block relative">
            <Dropdown align="right" width="48">
                <template #trigger>
                    <button class="text-gray-500 block py-1 px-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
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
          <div class="md:min-w-full md:hidden block pb-4 mb-4 border-b border-solid border-gray-200">
            <div class="flex flex-wrap">
              <div class="w-6/12">
                <Link
                  class="md:block text-left md:pb-2 text-gray-600 dark:text-gray-200 mr-0 inline-block whitespace-nowrap text-sm uppercase font-bold p-4 px-0"
                  :href="route('dashboard')"
                >
                  Operra
                </Link>
              </div>
              <div class="w-6/12 flex justify-end">
                <button
                  type="button"
                  class="cursor-pointer text-black opacity-50 md:hidden px-3 py-1 text-xl leading-none bg-transparent rounded border border-solid border-transparent"
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
                :href="route('whatsapp.settings.index')"
                class="text-xs uppercase py-2 font-bold block transition-colors duration-200"
                :class="route().current('whatsapp.settings.*') ? 'text-operra-500' : 'text-gray-700 dark:text-gray-300 hover:text-operra-500'"
              >
                WhatsApp Config
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
      <!-- Top Navbar -->
      <nav class="absolute top-0 left-0 w-full z-10 bg-transparent md:flex-row md:flex-nowrap md:justify-start flex items-center p-4">
        <div class="w-full mx-autp items-center flex justify-between md:flex-nowrap flex-wrap md:px-10 px-4">
          <!-- Brand -->
          <span class="text-white text-sm uppercase hidden lg:inline-block font-semibold">
            <slot name="header" />
          </span>
          <!-- User & Theme -->
          <ul class="flex-col md:flex-row list-none items-center hidden md:flex gap-4">
             <li class="inline-block relative">
                <Link :href="route('crm.chat.index')" class="text-white hover:text-operra-200 transition-colors duration-200 relative block">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <!-- Notification Badge -->
                    <span v-if="$page.props.unreadCount > 0" class="absolute -top-1 -right-1 flex h-4 w-4">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 text-[10px] items-center justify-center font-bold text-white">
                            {{ $page.props.unreadCount }}
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
      <div class="relative bg-operra-600 md:pt-32 pb-32 pt-12 transition-colors duration-300">
        <div class="px-4 md:px-10 mx-auto w-full">
          <div>
            <!-- Header Stats Slots -->
            <slot name="stats" />
          </div>
        </div>
      </div>

      <!-- Main Page Content -->
      <div class="px-4 md:px-10 mx-auto w-full -m-24">
        <slot />
        
        <footer class="block py-4">
          <div class="container mx-auto px-4">
            <hr class="mb-4 border-b-1 border-gray-200 dark:border-gray-700" />
            <div class="flex flex-wrap items-center md:justify-between justify-center">
              <div class="w-full md:w-4/12 px-4">
                <div class="text-sm text-gray-500 dark:text-gray-400 font-semibold py-1 text-center md:text-left">
                  © 2026 Operra by <a href="https://hasanarofid.site" class="text-operra-500 hover:text-operra-700">hasanarofid</a>
                </div>
              </div>
            </div>
          </div>
        </footer>
      </div>
    </div>
  </div>
</template>
