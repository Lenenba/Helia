<script setup lang="ts">
defineProps<{
    data: {
        html: string;
    };
}>();
</script>

<template>
    <div class="prose dark:prose-invert max-w-none px-4 py-8">
        <div v-html="data.html" class="text-gray-800 dark:text-gray-200"></div>
    </div>
</template>
