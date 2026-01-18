<script setup>
import { ref, onMounted } from 'vue';

const isDark = ref(true);

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
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300">
        <!-- No Sidebar, No Navbar, Just Content -->
        <main class="h-screen w-full overflow-hidden">
            <slot />
        </main>
    </div>
</template>

<style>
/* Ensure the body and html take full height for iframe use */
html, body, #app {
    height: 100%;
    margin: 0;
    padding: 0;
    overflow: hidden;
}
</style>

