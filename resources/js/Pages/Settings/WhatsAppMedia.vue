<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    mediaMessages: Object
});

const selectedMedia = ref(null);
const showModal = ref(false);

const openPreview = (message) => {
    selectedMedia.value = message;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedMedia.value = null;
};

const deleteMedia = (id) => {
    if (confirm('Are you sure you want to delete this media file?')) {
        router.delete(route('whatsapp.media.destroy', id), {
            onSuccess: () => alert('Media deleted!')
        });
    }
};

const getFileExtension = (path) => {
    return path.split('.').pop().toLowerCase();
};

const isImage = (path) => {
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(getFileExtension(path));
};

const isVideo = (path) => {
    return ['mp4', 'webm', 'ogg'].includes(getFileExtension(path));
};

const isAudio = (path) => {
    return ['mp3', 'wav', 'ogg', 'aac', 'm4a'].includes(getFileExtension(path));
};

const isPdf = (path) => {
    return getFileExtension(path) === 'pdf';
};

const isExcel = (path) => {
    return ['xls', 'xlsx', 'csv'].includes(getFileExtension(path));
};

const getFileUrl = (path) => {
    return `/storage/${path}`;
};
</script>

<template>
    <Head title="WhatsApp Media Gallery" />

    <AuthenticatedLayout>
        <template #header>
            WhatsApp Media Gallery
        </template>

        <div class="flex flex-wrap mt-4">
            <div class="w-full px-4">
                <div class="relative flex flex-col min-w-0 break-words bg-white dark:bg-gray-800 w-full mb-6 shadow-lg rounded">
                    <div class="rounded-t mb-0 px-4 py-3 border-0">
                        <h3 class="font-bold text-base text-gray-700 dark:text-gray-200">Received Media Files</h3>
                    </div>
                    
                    <div class="p-6 border-t border-gray-100 dark:border-gray-700">
                        <div v-if="mediaMessages.data.length > 0" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            <div v-for="message in mediaMessages.data" :key="message.id" class="group relative bg-gray-50 dark:bg-gray-900 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 aspect-square flex flex-col shadow-sm hover:shadow-md transition-all">
                                <!-- Image Thumbnail -->
                                <div v-if="isImage(message.attachment_path)" class="w-full h-full">
                                    <img :src="getFileUrl(message.attachment_path)" class="w-full h-full object-cover cursor-pointer" @click="openPreview(message)">
                                </div>
                                
                                <!-- File Icons for non-images -->
                                <div v-else class="w-full h-full flex flex-col items-center justify-center cursor-pointer p-4" @click="openPreview(message)">
                                    <div v-if="isPdf(message.attachment_path)" class="text-red-500">
                                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 3.586A2 2 0 0012.586 3H9z"/><path d="M3 8a2 2 0 012-2v10h8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>
                                    </div>
                                    <div v-else-if="isExcel(message.attachment_path)" class="text-green-600">
                                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20"><path d="M3 3a1 1 0 011-1h12a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V3zm3 3v2h8V6H6zm0 4v2h8v-2H6zm0 4v2h8v-2H6z"/></svg>
                                    </div>
                                    <div v-else-if="isVideo(message.attachment_path)" class="text-blue-500">
                                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zm10 2v4l4-2-4-2z"/></svg>
                                    </div>
                                    <div v-else-if="isAudio(message.attachment_path)" class="text-indigo-500">
                                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20"><path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3V7.82l8-1.6v5.894A4.37 4.369 0 0015 12c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3V3z"/></svg>
                                    </div>
                                    <div v-else class="text-gray-400">
                                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
                                    </div>
                                    <span class="text-[10px] mt-2 font-bold uppercase text-gray-500 truncate w-full text-center px-2">
                                        {{ getFileExtension(message.attachment_path) }}
                                    </span>
                                </div>

                                <!-- Overlay Info -->
                                <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-2 text-white opacity-0 group-hover:opacity-100 transition-opacity">
                                    <p class="text-[10px] font-bold truncate">{{ message.chat_session?.customer?.name || 'Unknown' }}</p>
                                    <p class="text-[8px] opacity-80">{{ new Date(message.created_at).toLocaleString() }}</p>
                                </div>

                                <!-- Delete Button -->
                                <button @click.stop="deleteMedia(message.id)" class="absolute top-2 right-2 bg-red-500 text-white p-1.5 rounded-full opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600 shadow-lg">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h14"></path></svg>
                                </button>
                            </div>
                        </div>
                        <div v-else class="py-20 text-center text-gray-400 italic">
                            No media files found in database.
                        </div>

                        <!-- Pagination -->
                        <div v-if="mediaMessages.links.length > 3" class="mt-8 flex justify-center">
                            <nav class="flex gap-1">
                                <button v-for="(link, k) in mediaMessages.links" :key="k" 
                                    @click="router.visit(link.url)"
                                    :disabled="!link.url || link.active"
                                    class="px-3 py-1 rounded text-xs"
                                    :class="link.active ? 'bg-operra-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-operra-50'"
                                    v-html="link.label">
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <transition name="fade">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm" @click.self="closeModal">
                <div class="relative max-w-5xl w-full max-h-[90vh] bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-2xl flex flex-col">
                    <!-- Modal Header -->
                    <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900">
                        <div>
                            <h3 class="font-bold text-gray-800 dark:text-white">Media Preview</h3>
                            <p class="text-xs text-gray-500">From: {{ selectedMedia.chat_session?.customer?.name }} ({{ selectedMedia.chat_session?.customer?.phone }})</p>
                        </div>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- Modal Content -->
                    <div class="flex-1 overflow-auto flex items-center justify-center p-4 bg-gray-100 dark:bg-gray-950">
                        <div v-if="isImage(selectedMedia.attachment_path)" class="max-w-full">
                            <img :src="getFileUrl(selectedMedia.attachment_path)" class="max-h-[70vh] rounded-lg shadow-lg">
                        </div>
                        
                        <div v-else-if="isVideo(selectedMedia.attachment_path)" class="w-full max-w-3xl">
                            <video :src="getFileUrl(selectedMedia.attachment_path)" controls class="w-full rounded-lg shadow-lg"></video>
                        </div>

                        <div v-else-if="isAudio(selectedMedia.attachment_path)" class="w-full max-w-md bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-lg">
                            <audio :src="getFileUrl(selectedMedia.attachment_path)" controls class="w-full"></audio>
                        </div>

                        <div v-else-if="isPdf(selectedMedia.attachment_path)" class="w-full h-full min-h-[60vh]">
                            <iframe :src="getFileUrl(selectedMedia.attachment_path)" class="w-full h-full rounded-lg" frameborder="0"></iframe>
                        </div>

                        <div v-else class="text-center p-12">
                            <svg class="w-20 h-20 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            <p class="text-gray-500 dark:text-gray-400 mb-6">Preview not available for this file type.</p>
                            <a :href="getFileUrl(selectedMedia.attachment_path)" target="_blank" class="bg-operra-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-operra-700 transition shadow-lg">
                                Download File
                            </a>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                        <a :href="getFileUrl(selectedMedia.attachment_path)" download class="text-xs bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                            Download
                        </a>
                        <button @click="closeModal" class="text-xs bg-operra-600 text-white px-6 py-2 rounded-lg hover:bg-operra-700 transition shadow-md font-bold">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </AuthenticatedLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>

