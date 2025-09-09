<!-- resources/js/components/nav/NavBar.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

type Item = { id: number; label: string; href?: string | null; visible: boolean; children?: Item[] }

const props = defineProps<{ menuSlug?: string }>()
const page = usePage()
const menu = computed(() => page.props.menus?.[props.menuSlug || 'main'] ?? null)
</script>

<template>
    <nav
        class="w-full backdrop-blur bg-white/80 dark:bg-neutral-900/80 border-b border-neutral-200 dark:border-neutral-800">
        <div class="container mx-auto px-4 h-14 flex items-center justify-between">
            <slot name="brand">
                <div class="font-semibold">MyCMS</div>
            </slot>

            <ul class="hidden md:flex items-center gap-6">
                <li v-for="item in (menu?.items || [])" :key="item.id" v-if="item.visible" class="relative group">
                    <component :is="item.href ? Link : 'span'" :href="item.href || '#'"
                        class="text-sm hover:opacity-80">
                        {{ item.label }}
                    </component>

                    <ul v-if="item.children?.length"
                        class="absolute left-0 top-full mt-2 min-w-44 rounded-xl shadow-xl p-2 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto transition">
                        <li v-for="child in item.children" :key="child.id"
                            class="px-3 py-2 rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-800">
                            <component :is="child.href ? Link : 'span'" :href="child.href || '#'" class="text-sm">{{
                                child.label }}</component>
                        </li>
                    </ul>
                </li>
            </ul>

            <slot name="actions" />
        </div>
    </nav>
</template>
