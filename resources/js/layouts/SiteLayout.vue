<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

type Node = {
    id: number | string; label: string; is_visible: boolean;
    linkable_type: 'custom' | 'App\\Models\\Page' | 'App\\Models\\Post' | 'none';
    url?: string | null; slug?: string; children?: Node[];
}

const page = usePage<any>()

const rawTree = computed<Node[]>(() => page.props.menu?.tree ?? [])
const items = computed<Node[]>(() => (rawTree.value ?? []).filter(i => i.is_visible))

function href(n: Node): string {
    if (n.linkable_type === 'custom' && n.url) return n.url;

    if (n.linkable_type === 'App\\Models\\Page') {
        if ((n.slug ?? '').toLowerCase() === 'home' || (n.label ?? '').toLowerCase() === 'accueil') {
            try { return route('pages.show'); } catch { return '/'; }
        }
        if (n.slug) {
            try { return route('pages.show', n.slug); } catch { return `/${n.slug}`; }
        }
    }
    if (n.linkable_type === 'App\\Models\\Post' && n.slug) {
        return route?.('posts.show', n.slug) ?? `/posts/${n.slug}`;
    }

    return '#';
}
</script>

<template>
    <div class="min-h-screen flex flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a]">
        <header class="border-b border-black/10 dark:border-white/10">
            <div class="mx-auto max-w-6xl px-4 py-3 flex items-center justify-between">
                <Link :href="route('home')" class="font-semibold"> {{ $page.props.name }} </Link>

                <nav class="flex items-center gap-2">
                    <Link v-for="it in items" :key="it.id" :href="href(it)"
                        class="rounded-sm px-3 py-1.5 hover:bg-black/5 dark:hover:bg-white/10">{{ it.label }}</Link>

                    <Link v-if="$page.props.auth?.user" :href="route('dashboard')"
                        class="ml-2 rounded-sm border px-3 py-1.5">Dashboard</Link>
                    <template v-else>
                        <Link :href="route('login')" class="ml-2 rounded-sm px-3 py-1.5">Log in</Link>
                        <Link :href="route('register')" class="rounded-sm border px-3 py-1.5">Register</Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-black/10 dark:border-white/10">
            <div class="mx-auto max-w-6xl px-4 py-6 text-sm text-neutral-500 dark:text-neutral-400">
                © {{ new Date().getFullYear() }} {{ $page.props.name }}.
            </div>
        </footer>
    </div>
</template>
