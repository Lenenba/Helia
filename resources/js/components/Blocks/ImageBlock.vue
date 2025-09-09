<!-- resources/js/components/blocks/ImageBlock.vue -->
<script setup lang="ts">
const props = defineProps<{ data?: { url?: string | null; alt?: string; caption?: string | null; ratio?: string | null } }>()
</script>
<template>
    <figure class="overflow-hidden rounded-lg border border-black/10 dark:border-white/10">
        <img :src="data?.url || ''" :alt="data?.alt || ''" class="w-full object-cover" />
        <figcaption v-if="data?.caption" class="p-2 text-sm text-neutral-500">{{ data.caption }}</figcaption>
    </figure>
</template>
