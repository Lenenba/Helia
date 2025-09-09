<script setup lang="ts">
import { Teleport } from 'vue';

const props = defineProps<{ show: boolean }>();
const emit = defineEmits(['close']);
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 transition-opacity" @click="$emit('close')"></div>
            <div class="relative w-full max-w-lg rounded-2xl border bg-white dark:bg-neutral-950 p-6 shadow-xl">
                <slot />
            </div>
        </div>
    </Teleport>
</template>
