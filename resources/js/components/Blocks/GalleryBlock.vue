<script setup lang="ts">
defineProps<{
    data: Array<{
        file_path: string;
        alt_text?: string;
        caption?: string;
    }>;
}>();
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 px-4 py-8">
        <div v-for="(image, index) in data" :key="index" class="relative group">
            <img :src="image.file_path" :alt="image.alt_text"
                class="w-full h-48 object-cover rounded-lg shadow-md transition-transform duration-300 group-hover:scale-105" />
            <div v-if="image.caption"
                class="absolute inset-x-0 bottom-0 bg-black bg-opacity-50 text-white p-2 text-sm opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                {{ image.caption }}
            </div>
        </div>
    </div>
</template>
