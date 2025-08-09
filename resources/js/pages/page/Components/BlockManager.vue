<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { PropType } from 'vue' // Correct import for PropType
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Label } from '@/components/ui/label'
import { SharedData } from '@/types'

// Define a type for the standardized items from the backend
interface ContentItem {
    id: number;
    label: string;
    type: string;
}

// Props
const props = defineProps({
    open: {
        type: Boolean,
        required: true
    },
    availableElements: {
        type: Object as PropType<SharedData['availableElements']>,
        required: true
    }
})

// Emits
const emit = defineEmits(['update:open', 'select'])

const isDialogVisible = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value)
})

// --- STATE ---
const selectedType = ref<keyof typeof props.availableElements | null>(null)
// State for hover-based preview, providing a better UX
const hoveredMediaItem = ref<ContentItem | null>(null)

// --- COMPUTED ---

// Dynamically generate the list of available content types from props
const contentTypes = computed(() => Object.keys(props.availableElements))

// Get the list of items to display based on the selected type
const contentItems = computed<ContentItem[]>(() => {
    if (!selectedType.value) return []
    return props.availableElements[selectedType.value] || []
})

// Generate a preview URL for the *hovered* media item
const mediaPreviewUrl = computed(() => {
    if (!hoveredMediaItem.value) return null
    // Construct the URL assuming files are in Laravel's public storage
    return `/storage/media/${hoveredMediaItem.value.label}`
})

// --- METHODS ---

// Capitalize the first letter for display purposes (e.g., "posts" -> "Posts")
function formatTypeName(type: string): string {
    if (!type) return ''
    return type.charAt(0).toUpperCase() + type.slice(1)
}

// Handle the selection of an item
function handleItemSelect(item: ContentItem) {
    if (!selectedType.value) return
    emit('select', { content: item, type: selectedType.value })
    isDialogVisible.value = false
}

// --- WATCHERS ---

// Reset state when the dialog closes
watch(() => props.open, (isOpen) => {
    if (!isOpen) {
        setTimeout(() => {
            selectedType.value = null
            hoveredMediaItem.value = null
        }, 150)
    }
})

// Reset hovered item when the selected type changes
watch(selectedType, () => {
    hoveredMediaItem.value = null
})
</script>

<template>
    <Dialog v-model:open="isDialogVisible">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Select Content to Add</DialogTitle>
                <DialogDescription>
                    Choose a content type, then select an element to add.
                </DialogDescription>
            </DialogHeader>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 py-4">
                <div class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="content-type">Content Type</Label>
                        <Select id="content-type" v-model="selectedType">
                            <SelectTrigger>
                                <SelectValue placeholder="Select a type..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="type in contentTypes" :key="type" :value="type">
                                    {{ formatTypeName(type) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div v-if="selectedType" class="grid gap-2">
                        <Label>Element to Add</Label>
                        <ScrollArea class="h-48 w-full rounded-md border">
                            <div class="p-1 space-y-1">
                                <div v-for="item in contentItems" :key="item.id">
                                    <Button variant="ghost" class="w-full justify-start text-left h-auto py-2"
                                        @click="handleItemSelect(item)"
                                        @mouseenter="selectedType === 'medias' && (hoveredMediaItem = item)"
                                        @mouseleave="selectedType === 'medias' && (hoveredMediaItem = null)">
                                        {{ item.label }}
                                    </Button>
                                </div>
                                <div v-if="!contentItems.length" class="text-sm text-muted-foreground p-2 text-center">
                                    No "{{ selectedType }}" elements available.
                                </div>
                            </div>
                        </ScrollArea>
                    </div>
                </div>

                <div class="flex items-center justify-center rounded-md border border-dashed bg-muted">
                    <div v-if="selectedType === 'medias' && mediaPreviewUrl" class="p-2 w-full">
                        <img v-if="mediaPreviewUrl.match(/\.(jpeg|jpg|gif|png|webp)$/i)" :src="mediaPreviewUrl"
                            alt="Media Preview" class="max-w-full max-h-48 object-contain mx-auto" />
                        <video v-else-if="mediaPreviewUrl.match(/\.(mp4|webm)$/i)" controls autoplay muted loop
                            class="max-w-full max-h-94 object-contain mx-auto">
                            <source :src="mediaPreviewUrl" type="video/mp4" />
                            Your browser does not support the video tag.
                        </video>
                        <div v-else class="text-sm text-muted-foreground">
                            Preview not available for this file type.
                        </div>
                    </div>
                    <div v-else class="text-center text-sm text-muted-foreground p-4">
                        <span v-if="selectedType === 'medias'">Hover over a media file to see a preview.</span>
                        <span v-else>Preview Area</span>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
