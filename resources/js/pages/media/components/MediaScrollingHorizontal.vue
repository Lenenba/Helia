<script setup lang="ts">
// ----------------------------
// MediaGallery.vue
// ----------------------------
// - Default to a responsive grid display
// - Toggle between Grid/List
// - Click on an image to open a modal with actions
// - Code comments are in ENGLISH per user's request
// ----------------------------

import { ref, computed, watchEffect } from 'vue'
import { router } from '@inertiajs/vue3'
import type { MediaItem } from '@/types'

// shadcn-vue components you already use
import { Button } from '@/components/ui/button'
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog'
import { Badge } from '@/components/ui/badge'

const props = defineProps<{
    items: MediaItem[];
}>()

// --- View mode (persisted in localStorage) ---
type ViewMode = 'grid' | 'list'
const VIEW_MODE_KEY = 'mediaGallery:viewMode'
const initialMode = ((): ViewMode => {
    const raw = localStorage.getItem(VIEW_MODE_KEY)
    return (raw === 'grid' || raw === 'list') ? raw : 'grid'
})()
const viewMode = ref<ViewMode>(initialMode)
watchEffect(() => {
    localStorage.setItem(VIEW_MODE_KEY, viewMode.value)
})

// --- Modal state ---
const dialogOpen = ref(false)
const selected = ref<MediaItem | null>(null)
const isBusy = ref(false) // Simple lock while network calls run

const openModal = (media: MediaItem) => {
    selected.value = media
    dialogOpen.value = true
}

const closeModal = () => {
    dialogOpen.value = false
    selected.value = null
}

// --- Pretty collection name example mapping ---
const displayCollection = (m: MediaItem) => {
    // Add/extend mapping logic here if needed
    if (m.is_profile) return 'Profile'
    if (m.collection_name === 'garde') return 'Collection de Garde'
    return m.collection_name ?? 'Default'
}

// --- Inertia actions (profile + delete) ---
const handleSetProfile = (media: MediaItem) => {
    isBusy.value = true
    router.post('/media/setAsProfile', { media_id: media.id }, {
        preserveScroll: true,
        onSuccess: () => {
            console.log(`Profile photo updated: ${media.id}`)
            closeModal()
        },
        onError: (errors: Record<string, string[]>) => {
            console.error('Failed to set profile photo:', errors)
        },
        onFinish: () => {
            isBusy.value = false
        },
    })
}

const handleDelete = (media: MediaItem) => {
    // Inline confirm to avoid native prompt cluttering the UI
    const ok = confirm(`Êtes-vous sûr de vouloir supprimer cette image ? (${media.collection_name} #${media.id})`)
    if (!ok) return

    isBusy.value = true
    router.delete(`/media/${media.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            console.log(`Media deleted: ${media.id}`)
            closeModal()
        },
        onError: (errors: Record<string, string[]>) => {
            console.error('Failed to delete media:', errors)
        },
        onFinish: () => {
            isBusy.value = false
        },
    })
}

// --- Extra client-side actions (no backend required) ---
const copyUrl = async (url: string) => {
    try {
        await navigator.clipboard.writeText(url)
        console.log('Copied to clipboard:', url)
    } catch (e) {
        console.error('Clipboard not available, fallback:', e)
        alert('Impossible de copier. Veuillez copier manuellement.')
    }
}

const openOriginal = (url: string) => {
    window.open(url, '_blank', 'noopener,noreferrer')
}

const downloadImage = (url: string, filename = 'media.jpg') => {
    // Note: The "download" attribute may be ignored on cross-origin files.
    const a = document.createElement('a')
    a.href = url
    a.setAttribute('download', filename)
    a.setAttribute('target', '_blank')
    document.body.appendChild(a)
    a.click()
    a.remove()
}

// --- Derived UI helpers ---
const isGrid = computed(() => viewMode.value === 'grid')
const itemCount = computed(() => props.items?.length ?? 0)
</script>

<template>
    <div class="w-full space-y-4">
        <!-- Header / toolbar -->
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <h2 class="text-base font-semibold">Médias</h2>
                <span class="text-sm text-muted-foreground">({{ itemCount }})</span>
            </div>

            <!-- Simple two-button view toggle -->
            <div class="inline-flex items-center gap-1 rounded-lg border p-1">
                <Button :variant="isGrid ? 'secondary' : 'ghost'" size="sm" class="rounded-md" aria-label="Vue grille"
                    @click="viewMode = 'grid'">
                    Grille
                </Button>
                <Button :variant="!isGrid ? 'secondary' : 'ghost'" size="sm" class="rounded-md" aria-label="Vue liste"
                    @click="viewMode = 'list'">
                    Liste
                </Button>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="!items || items.length === 0" class="rounded-lg border p-6 text-sm text-muted-foreground">
            No media uploaded yet.
        </div>

        <!-- Content area -->
        <ScrollArea v-else class="w-full h-[70vh] rounded-md border">
            <div class="p-4">
                <!-- GRID MODE -->
                <div v-if="isGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-6 gap-4">
                    <figure v-for="m in items" :key="m.id" class="group relative cursor-pointer" @click="openModal(m)">
                        <div class="relative overflow-hidden rounded-lg ring-1 ring-border">
                            <img :src="m.url" :alt="`${m.collection_name} #${m.id}`"
                                class="aspect-[3/4] w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]"
                                loading="lazy" decoding="async" />
                            <!-- Profile badge overlay -->
                            <div v-if="m.is_profile" class="absolute right-2 top-2">
                                <Badge variant="secondary" class="bg-green-100 text-green-700">Actif</Badge>
                            </div>
                        </div>

                        <!-- Meta line -->
                        <figcaption class="mt-2 text-xs">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-medium truncate" :title="displayCollection(m)">{{ displayCollection(m)
                                }}</span>
                                <span class="text-muted-foreground">#{{ m.id }}</span>
                            </div>
                        </figcaption>
                    </figure>
                </div>

                <!-- LIST MODE -->
                <div v-else class="space-y-3">
                    <div v-for="m in items" :key="m.id"
                        class="flex items-center gap-4 rounded-lg border p-3 hover:bg-muted/50 cursor-pointer"
                        @click="openModal(m)">
                        <img :src="m.url" :alt="`${m.collection_name} #${m.id}`"
                            class="h-20 w-20 rounded-md object-cover ring-1 ring-border" loading="lazy"
                            decoding="async" />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium truncate">{{ displayCollection(m) }}</p>
                                <Badge v-if="m.is_profile" variant="secondary" class="bg-green-100 text-green-700">Actif
                                </Badge>
                            </div>
                            <p class="text-xs text-muted-foreground truncate">ID: {{ m.id }}</p>
                            <p class="text-xs text-muted-foreground truncate">{{ m.url }}</p>
                        </div>
                        <div class="text-xs text-muted-foreground">Cliquez pour options</div>
                    </div>
                </div>
            </div>

            <ScrollBar orientation="vertical" />
        </ScrollArea>

        <!-- Actions Modal -->
        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-[700px]">
                <DialogHeader>
                    <DialogTitle>Actions</DialogTitle>
                    <DialogDescription>
                        Gérez ce média : définir en profil, supprimer, copier le lien, etc.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selected" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <!-- Preview -->
                    <div class="order-1 md:order-none">
                        <div class="overflow-hidden rounded-lg ring-1 ring-border">
                            <img :src="selected.url" :alt="`${selected.collection_name} #${selected.id}`"
                                class="w-full object-cover" loading="eager" />
                        </div>
                        <div class="mt-3 space-y-1 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="text-muted-foreground">Collection :</span>
                                <span class="font-medium">{{ displayCollection(selected) }}</span>
                                <Badge v-if="selected.is_profile" variant="secondary"
                                    class="bg-green-100 text-green-700">Actif</Badge>
                            </div>
                            <div class="text-muted-foreground">ID : #{{ selected.id }}</div>
                            <div class="truncate text-muted-foreground" :title="selected.url">URL : {{ selected.url }}
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="space-y-3">
                        <div class="rounded-lg border p-3">
                            <h4 class="text-sm font-semibold mb-2">Action principales</h4>
                            <div class="flex flex-wrap gap-2">
                                <Button size="sm" :disabled="isBusy || !!selected.is_profile"
                                    @click.stop="selected && handleSetProfile(selected)" aria-label="Mettre en profil">
                                    Mettre en profil
                                </Button>

                                <Button size="sm" variant="destructive" :disabled="isBusy"
                                    @click.stop="selected && handleDelete(selected)" aria-label="Supprimer">
                                    Supprimer
                                </Button>
                            </div>
                            <p class="mt-2 text-xs text-muted-foreground">
                                La suppression est définitive. Assurez-vous de vouloir continuer.
                            </p>
                        </div>

                        <div class="rounded-lg border p-3">
                            <h4 class="text-sm font-semibold mb-2">Autres options</h4>
                            <div class="flex flex-wrap gap-2">
                                <Button size="sm" variant="outline" :disabled="!selected"
                                    @click.stop="selected && copyUrl(selected.url)" aria-label="Copier l'URL">
                                    Copier l’URL
                                </Button>
                                <Button size="sm" variant="outline" :disabled="!selected"
                                    @click.stop="selected && openOriginal(selected.url)" aria-label="Ouvrir l'original">
                                    Ouvrir l’original
                                </Button>
                                <Button size="sm" variant="outline" :disabled="!selected"
                                    @click.stop="selected && downloadImage(selected.url, `media-${selected.id}.jpg`)"
                                    aria-label="Télécharger">
                                    Télécharger
                                </Button>
                            </div>
                            <p class="mt-2 text-xs text-muted-foreground">
                                Astuce : gardez la modale ouverte pour effectuer plusieurs actions.
                            </p>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="ghost" @click="closeModal">Fermer</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

<style scoped>
/* No extra styles required; Tailwind utility classes handle layout.
   Keep this block for any tiny overrides if needed later. */
</style>
