<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    data: {
        title: string;
        excerpt: string;
        cta_label?: string;
        cta_url?: string;
    };
}>();
</script>

<template>
    <div class="bg-gray-900 text-white py-20 px-4 md:px-8">
        <div class="max-w-7xl mx-auto text-center">
            <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight">
                {{ data.title }}
            </h1>
            <p class="mt-4 text-xl text-gray-300 max-w-2xl mx-auto">
                {{ data.excerpt }}
            </p>
            <div v-if="data.cta_url && data.cta_label" class="mt-8">
                <Link :href="data.cta_url"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-gray-900 bg-white hover:bg-gray-200">
                {{ data.cta_label }}
                </Link>
            </div>
        </div>
    </div>
</template>
