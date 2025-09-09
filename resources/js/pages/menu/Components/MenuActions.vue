<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    menu: { id: number; slug: string; settings?: Record<string, any> | null };
}>();

const emit = defineEmits(['duplicate', 'ask-delete']);

const onDuplicate = () => emit('duplicate', props.menu);
const onDelete = () => emit('ask-delete', props.menu.id);
</script>

<template>
    <details class="relative inline-block">
        <summary
            class="list-none px-3 py-1.5 rounded-lg border cursor-pointer hover:bg-neutral-50 dark:hover:bg-neutral-900">
            Actions ▾
        </summary>
        <ul class="absolute right-0 mt-1 min-w-40 rounded-xl border bg-white dark:bg-neutral-950 shadow-xl p-1 z-10">
            <li>
                <Link :href="`/menus/${props.menu.id}/edit`"
                    class="block px-3 py-2 rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-900">
                Edit
                </Link>
            </li>
            <li>
                <button class="w-full text-left px-3 py-2 rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-900"
                    @click="onDuplicate">
                    Duplicate
                </button>
            </li>
            <li>
                <button
                    class="w-full text-left px-3 py-2 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40"
                    @click="onDelete">
                    Delete
                </button>
            </li>
        </ul>
    </details>
</template>
