<script setup lang="ts">
/**
 * Modern Menus Index page — inspired by popular CMS UIs.
 * - Instant search + sort
 * - Card/table dual layout (responsive)
 * - Quick actions (edit/duplicate/delete)
 * - Create menu in modal
 * - Bulk selection + bulk delete (optional)
 * - Clean empty state
 *
 * NOTE: Adjust routes if your app prefixes differ.
 */

import { computed, ref, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue'
import Modal from './components/Modal.vue'
import MenuActions from './Components/MenuActions.vue'

type MenuRow = {
    id: number
    name: string
    slug: string
    roots_count: number
    settings?: Record<string, any> | null
}

const props = defineProps<{ menus: MenuRow[] }>()

// ----- UI State -----
const q = ref<string>('')                    // search query
const sortKey = ref<'name' | 'roots_count' | 'slug'>('name')
const sortDir = ref<'asc' | 'desc'>('asc')
const selected = ref<Set<number>>(new Set())
const viewMode = ref<'cards' | 'table'>('cards') // small screens → cards, large → user choice

// Modal: Create
const showCreate = ref(false)
const createForm = useForm<{ name: string; slug: string; location?: string | null }>({
    name: '',
    slug: '',
    location: 'header', // optional convention inside settings
})

// Modal: Delete
const toDeleteId = ref<number | null>(null)
const deleting = ref(false)

// ----- Derived -----
const normalized = computed<MenuRow[]>(() =>
    (props.menus || []).map(m => ({
        ...m,
        settings: m.settings ?? {}, // ensure object
    })),
)

const filtered = computed<MenuRow[]>(() => {
    const query = q.value.trim().toLowerCase()
    if (!query) return normalized.value
    return normalized.value.filter(m =>
        [m.name, m.slug].some(v => v?.toLowerCase().includes(query)) ||
        String(m.roots_count).includes(query)
    )
})

const sorted = computed<MenuRow[]>(() => {
    const arr = [...filtered.value]
    const key = sortKey.value
    arr.sort((a, b) => {
        const av = (a as any)[key]
        const bv = (b as any)[key]
        if (av === bv) return 0
        return av > bv ? 1 : -1
    })
    return sortDir.value === 'asc' ? arr : arr.reverse()
})

// Reset selection if list changes
watch(sorted, () => selected.value.clear())

// ----- Actions -----
function toggleSelect(id: number) {
    if (selected.value.has(id)) selected.value.delete(id)
    else selected.value.add(id)
}
function isSelected(id: number) { return selected.value.has(id) }
function selectAllCurrent() {
    if (selected.value.size === sorted.value.length) selected.value.clear()
    else sorted.value.forEach(m => selected.value.add(m.id))
}

function openCreate() {
    createForm.reset()
    createForm.location = 'header'
    showCreate.value = true
}
function submitCreate() {
    // Persist location into settings server-side if you want
    createForm.post('/menus', {
        onSuccess: () => { showCreate.value = false },
    })
}

function askDelete(id: number) {
    toDeleteId.value = id
    deleting.value = false
}
function confirmDelete() {
    if (!toDeleteId.value) return
    deleting.value = true
    router.delete(`/menus/${toDeleteId.value}`, {
        onFinish: () => {
            deleting.value = false
            toDeleteId.value = null
        },
    })
}

function bulkDelete() {
    if (!selected.value.size) return
    if (!confirm(`Delete ${selected.value.size} menu(s)? This cannot be undone.`)) return
    // Fire DELETE one by one (simple version). You can create a dedicated bulk route later.
    const ids = Array.from(selected.value)
    const next = () => {
        const id = ids.shift()
        if (!id) { selected.value.clear(); return }
        router.delete(`/menus/${id}`, { onFinish: next })
    }
    next()
}

function duplicateMenu(m: MenuRow) {
    // Simple client-side helper: post to a route you add server-side if needed.
    // Fallback idea: redirect to create with prefilled params via query.
    router.post('/menus', {
        name: `${m.name} (Copy)`,
        slug: `${m.slug}-copy`.replace(/--+/g, '-'),
        settings: m.settings,
    })
}

// Utility: badge from settings.location
function locationBadge(m: MenuRow): string | null {
    const loc = (m.settings as any)?.location ?? null
    if (!loc) return null
    const label = String(loc).toLowerCase()
    if (label === 'header') return 'Header'
    if (label === 'footer') return 'Footer'
    if (label === 'sidebar') return 'Sidebar'
    return label[0].toUpperCase() + label.slice(1)
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Menu tree',
        href: '/menu',
    },
];
</script>

<template>

    <Head title="Menus" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Menus</h1>
                    <p class="text-sm opacity-70">Manage navigation structures for your site.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        class="hidden md:inline-flex px-3 py-2 rounded-sm border hover:bg-neutral-50 dark:hover:bg-neutral-900"
                        :class="viewMode === 'table' ? 'bg-neutral-50 dark:bg-neutral-900' : ''"
                        @click="viewMode = 'table'">
                        Table
                    </button>
                    <button
                        class="hidden md:inline-flex px-3 py-2 rounded-sm border hover:bg-neutral-50 dark:hover:bg-neutral-900"
                        :class="viewMode === 'cards' ? 'bg-neutral-50 dark:bg-neutral-900' : ''"
                        @click="viewMode = 'cards'">
                        Cards
                    </button>
                    <button
                        class="px-3 py-2 rounded-sm border bg-neutral-900 text-white dark:bg-white dark:text-neutral-900 hover:opacity-90"
                        @click="openCreate">
                        Create menu
                    </button>
                </div>
            </div>

            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-2">
                    <div class="relative w-full sm:w-80">
                        <input v-model="q" type="search" placeholder="Search menus…"
                            class="w-full rounded-sm border px-3 py-2 pl-9 bg-white/80 dark:bg-neutral-900/80 backdrop-blur" />
                        <span class="absolute left-2.5 top-2.5 text-sm opacity-60">🔎</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <select v-model="sortKey" class="rounded-sm border px-3 py-2">
                            <option value="name">Name</option>
                            <option value="slug">Slug</option>
                            <option value="roots_count">Root items</option>
                        </select>
                        <button class="rounded-sm border px-3 py-2 hover:bg-neutral-50 dark:hover:bg-neutral-900"
                            @click="sortDir = sortDir === 'asc' ? 'desc' : 'asc'">
                            {{ sortDir === 'asc' ? '↑' : '↓' }}
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        class="px-3 py-2 rounded-sm border hover:bg-neutral-50 dark:hover:bg-neutral-900 disabled:opacity-50"
                        :disabled="!selected.size" @click="bulkDelete">
                        Delete selected ({{ selected.size }})
                    </button>
                </div>
            </div>

            <div v-if="!sorted.length" class="rounded-sml border border-dashed p-12 text-center">
                <div class="text-2xl mb-2">No menus yet</div>
                <p class="opacity-70 mb-6">Create your first menu for the header, footer, or sidebar.</p>
                <button
                    class="px-4 py-2 rounded-sm border bg-neutral-900 text-white dark:bg-white dark:text-neutral-900 hover:opacity-90"
                    @click="openCreate">
                    Create menu
                </button>
            </div>

            <div v-else-if="viewMode === 'cards'" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="m in sorted" :key="m.id"
                    class="rounded-sml border p-4 hover:shadow-sm transition bg-white/70 dark:bg-neutral-950/60">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" :checked="isSelected(m.id)" @change="toggleSelect(m.id)"
                                class="mt-1" />
                            <div>
                                <div class="font-semibold leading-tight">{{ m.name }}</div>
                                <div class="text-xs opacity-70">/{{ m.slug }}</div>
                            </div>
                        </div>
                        <MenuActions :menu="m" @duplicate="duplicateMenu" @ask-delete="askDelete" />
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <span class="text-xs px-2 py-1 rounded-smll border">
                            {{ m.roots_count }} root {{ m.roots_count === 1 ? 'item' : 'items' }}
                        </span>
                        <span v-if="locationBadge(m)" class="text-xs px-2 py-1 rounded-smll border">
                            {{ locationBadge(m) }}
                        </span>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <Link :href="`/menus/${m.id}/edit`"
                            class="px-3 py-1.5 rounded-sm border hover:bg-neutral-50 dark:hover:bg-neutral-900">
                        Edit
                        </Link>
                        <Link :href="`/menus/${m.id}/edit`"
                            class="px-3 py-1.5 rounded-sm border hover:bg-neutral-50 dark:hover:bg-neutral-900">
                        Manage items
                        </Link>
                    </div>
                </div>
            </div>

            <div v-else class="rounded-sml border overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-neutral-50 dark:bg-neutral-900/60">
                        <tr>
                            <th class="text-left px-4 py-3">
                                <input type="checkbox" :checked="selected.size === sorted.length && sorted.length > 0"
                                    @change="selectAllCurrent" />
                            </th>
                            <th class="text-left px-4 py-3">Name</th>
                            <th class="text-left px-4 py-3">Slug</th>
                            <th class="text-left px-4 py-3">Location</th>
                            <th class="text-left px-4 py-3">Root items</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in sorted" :key="m.id" class="border-t">
                            <td class="px-4 py-3">
                                <input type="checkbox" :checked="isSelected(m.id)" @change="toggleSelect(m.id)" />
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ m.name }}</div>
                            </td>
                            <td class="px-4 py-3">/{{ m.slug }}</td>
                            <td class="px-4 py-3">
                                <span v-if="locationBadge(m)" class="text-xs px-2 py-1 rounded-smll border">
                                    {{ locationBadge(m) }}
                                </span>
                                <span v-else class="text-xs opacity-60">—</span>
                            </td>
                            <td class="px-4 py-3">{{ m.roots_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <MenuActions :menu="m" @duplicate="duplicateMenu" @ask-delete="askDelete" />
                                <Link :href="`/menus/${m.id}/edit`"
                                    class="ml-2 px-3 py-1.5 rounded-sm border hover:bg-neutral-50 dark:hover:bg-neutral-900">
                                Edit
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Modal :show="showCreate" @close="showCreate = false">
                <h2 class="text-xl font-semibold mb-4">Create menu</h2>
                <form @submit.prevent="submitCreate">
                    <div class="grid gap-3">
                        <label class="text-sm">Name</label>
                        <input v-model="createForm.name" class="rounded-sm border px-3 py-2" placeholder="Main" />

                        <label class="text-sm">Slug</label>
                        <input v-model="createForm.slug" class="rounded-sm border px-3 py-2" placeholder="main" />

                        <label class="text-sm">Location (optional)</label>
                        <select v-model="createForm.location" class="rounded-sm border px-3 py-2">
                            <option value="">—</option>
                            <option value="header">Header</option>
                            <option value="footer">Footer</option>
                            <option value="sidebar">Sidebar</option>
                        </select>

                        <div class="mt-4 flex justify-end gap-2">
                            <button type="button" class="px-3 py-2 rounded-sm border"
                                @click="showCreate = false">Cancel</button>
                            <button
                                class="px-3 py-2 rounded-sm border bg-neutral-900 text-white dark:bg-white dark:text-neutral-900 hover:opacity-90"
                                :disabled="createForm.processing" @click="submitCreate">
                                Create
                            </button>
                        </div>
                    </div>
                </form>
            </Modal>

            <Modal :show="!!toDeleteId" @close="toDeleteId = null">
                <h2 class="text-lg font-semibold mb-2">Delete menu</h2>
                <p class="opacity-80 mb-6">This action cannot be undone. Are you sure?</p>
                <div class="flex justify-end gap-2">
                    <button type="button" class="px-3 py-2 rounded-sm border" @click="toDeleteId = null">Cancel</button>
                    <button
                        class="px-3 py-2 rounded-sm border bg-red-600 text-white hover:opacity-90 disabled:opacity-50"
                        :disabled="deleting" @click="confirmDelete">
                        Delete
                    </button>
                </div>
            </Modal>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Small polish */
table th,
table td {
    white-space: nowrap;
}

details>summary::-webkit-details-marker {
    display: none;
}
</style>
