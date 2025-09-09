<!-- resources/js/components/blocks/PostTeaserBlock.vue -->
<script setup lang="ts">
const props = defineProps<{ data?: { title?: string; excerpt?: string; cover?: string | null; href?: string } }>()
</script>
<template>
    <a :href="data?.href || '#'" class="flex gap-4 rounded-lg border p-3 hover:bg-black/5 dark:hover:bg-white/10">
        <img v-if="data?.cover" :src="data.cover" :alt="data?.title || ''" class="h-20 w-28 rounded object-cover" />
        <div>
            <h3 class="font-medium">{{ data?.title }}</h3>
            <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ data?.excerpt }}</p>
        </div>
    </a>
</template>
