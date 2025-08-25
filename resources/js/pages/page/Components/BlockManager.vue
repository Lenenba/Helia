<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { PropType } from 'vue'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { SharedData } from '@/types'

/** Standardized item coming from backend */
interface ContentItem {
    id: number
    label: string
    type: string
}

/** Props */
const props = defineProps({
    open: { type: Boolean, required: true },
    availableElements: {
        type: Object as PropType<SharedData['availableElements']>,
        required: true,
    },
})

/** Emits */
const emit = defineEmits<{
    (e: 'update:open', value: boolean): void
    (e: 'select', payload: { content: ContentItem; type: keyof typeof props.availableElements }): void
}>()

/** Two-way open binding for <Dialog> */
const isDialogVisible = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
})

/** --- STATE --- */
const selectedType = ref<keyof typeof props.availableElements | null>(null)
const hoveredMediaItem = ref<ContentItem | null>(null)
const searchQuery = ref('') // client-side search on labels

/** --- COMPUTED --- */

/** Build the list of content types */
const contentTypes = computed(() => Object.keys(props.availableElements))

/** Items for the selected type */
const contentItems = computed<ContentItem[]>(() => {
    if (!selectedType.value) return []
    return (props.availableElements[selectedType.value] || []) as unknown as ContentItem[]
})

/** Filter items by search query (case-insensitive) */
const filteredItems = computed<ContentItem[]>(() => {
    const q = searchQuery.value.trim().toLowerCase()
    if (!q) return contentItems.value
    return contentItems.value.filter((it) => it.label.toLowerCase().includes(q))
})

/** Generate preview URL for hovered media */
const mediaPreviewUrl = computed(() => {
    if (!hoveredMediaItem.value) return null
    // Assumes files are accessible from Laravel public storage
    return `/storage/media/${hoveredMediaItem.value.label}`
})

/** --- METHODS --- */

/** Capitalize type name for display */
function formatTypeName(type: string): string {
    if (!type) return ''
    return type.charAt(0).toUpperCase() + type.slice(1)
}

/** Handle item selection and close dialog */
function handleItemSelect(item: ContentItem) {
    if (!selectedType.value) return
    emit('select', { content: item, type: selectedType.value })
    isDialogVisible.value = false
}

/** --- WATCHERS --- */

/** Reset state when closing */
watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            // small delay to allow close animation
            setTimeout(() => {
                selectedType.value = null
                hoveredMediaItem.value = null
                searchQuery.value = ''
            }, 150)
        }
    }
)

/** When type changes, reset hovered preview and search */
watch(selectedType, () => {
    hoveredMediaItem.value = null
    searchQuery.value = ''
})
</script>

<template>
    <Dialog v-model:open="isDialogVisible">
        <!--
      Bigger dialog:
      - width: up to ~95vw, with a sensible max (5xl/7xl)
      - height: ~80vh so the content breathes
      - remove padding (p-0) to manage our own interior spacing
    -->
        <DialogContent class="w-[95vw] sm:max-w-7xl h-[80vh] p-0 overflow-hidden">
            <!-- Sticky header inside the dialog for persistent controls -->
            <div
                class="sticky top-0 z-10 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <DialogHeader class="px-6 pt-6 pb-4">
                    <DialogTitle class="text-xl">Select Content to Add</DialogTitle>
                    <DialogDescription>
                        Choose a content type, then pick an element to add. Use search to quickly filter long lists.
                    </DialogDescription>
                </DialogHeader>

                <!-- Controls row: Type + Search -->
                <div class="px-6 pb-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="col-span-1">
                            <Label for="content-type" class="mb-1 block">Content Type</Label>
                            <Select id="content-type" v-model="selectedType">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Select a type..." />
                                </SelectTrigger>
                                <SelectContent class="max-h-80">
                                    <SelectItem v-for="type in contentTypes" :key="type" :value="type">
                                        {{ formatTypeName(type) }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="col-span-2">
                            <Label for="search" class="mb-1 block">Search</Label>
                            <Input id="search" v-model="searchQuery" type="text"
                                placeholder="Type to filter items by label…" class="w-full" :disabled="!selectedType" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main 2-pane layout: list (left) / preview (right) -->
            <div class="grid grid-cols-12 gap-6 px-6 py-6 h-[calc(80vh-9.5rem)]">
                <!-- LEFT: items list -->
                <div class="col-span-12 lg:col-span-5 flex flex-col min-h-0">
                    <Label class="mb-2">Element to Add</Label>

                    <div class="rounded-md border h-full min-h-0 flex flex-col" role="region"
                        aria-label="Available items">
                        <!-- List scroll area is tall to avoid cramped feeling -->
                        <ScrollArea class="h-full w-full">
                            <div class="p-1 space-y-1">
                                <template v-if="selectedType">
                                    <template v-if="filteredItems.length">
                                        <div v-for="item in filteredItems" :key="item.id">
                                            <Button variant="ghost"
                                                class="w-full justify-start text-left h-auto py-2 px-3 hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring rounded-md"
                                                @click="handleItemSelect(item)"
                                                @mouseenter="selectedType === 'medias' && (hoveredMediaItem = item)"
                                                @mouseleave="selectedType === 'medias' && (hoveredMediaItem = null)">
                                                {{ item.label }}
                                            </Button>
                                        </div>
                                    </template>

                                    <div v-else class="text-sm text-muted-foreground p-3 text-center">
                                        No results for "<strong>{{ searchQuery }}</strong>" in "{{ selectedType }}".
                                    </div>
                                </template>

                                <div v-else class="text-sm text-muted-foreground p-3 text-center">
                                    Choose a type to display items.
                                </div>
                            </div>
                        </ScrollArea>
                    </div>
                </div>

                <!-- RIGHT: large preview -->
                <div class="col-span-12 lg:col-span-7 min-h-0">
                    <div class="flex items-center justify-center rounded-md border border-dashed bg-muted/40 h-full">
                        <!-- Media preview only when type is medias and an URL exists -->
                        <div v-if="selectedType === 'medias' && mediaPreviewUrl" class="p-3 w-full h-full">
                            <!--
                Letterbox preview box that preserves aspect ratio and avoids overflow.
                object-contain ensures the whole media is visible without distortion.
              -->
                            <div
                                class="w-full h-full rounded-md bg-background border overflow-hidden flex items-center justify-center">
                                <img v-if="/\.(jpeg|jpg|gif|png|webp|bmp|svg)$/i.test(mediaPreviewUrl)"
                                    :src="mediaPreviewUrl" alt="Media Preview"
                                    class="max-w-full max-h-full object-contain" draggable="false" />

                                <video v-else-if="/\.(mp4|webm|ogg)$/i.test(mediaPreviewUrl)" controls autoplay muted
                                    loop class="max-w-full max-h-full object-contain outline-none">
                                    <source :src="mediaPreviewUrl" />
                                    Your browser does not support the video tag.
                                </video>

                                <div v-else class="text-sm text-muted-foreground p-4 text-center">
                                    Preview not available for this file type.
                                </div>
                            </div>

                            <!-- File name / hint line -->
                            <div class="mt-2 text-xs text-muted-foreground truncate">
                                {{ hoveredMediaItem?.label }}
                            </div>
                        </div>

                        <!-- Generic right pane placeholder -->
                        <div v-else class="text-center text-sm text-muted-foreground p-6">
                            <template v-if="selectedType === 'medias'">
                                Hover over a media file on the left to see a large preview here.
                            </template>
                            <template v-else>
                                Preview Area
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
