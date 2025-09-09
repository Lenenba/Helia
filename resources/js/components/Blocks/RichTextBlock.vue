<!-- resources/js/components/blocks/RichTextBlock.vue -->
<script setup lang="ts">
const props = defineProps<{ settings?: Record<string, any> }>()
// Expect settings.html (trusted HTML from your CMS/WYSIWYG already sanitized server-side)
</script>
<template>
    <div class="prose prose-neutral dark:prose-invert max-w-none" v-html="settings?.html || ''"></div>
</template>
