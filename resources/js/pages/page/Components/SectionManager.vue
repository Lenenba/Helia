<script setup lang="ts">
import { ref, reactive } from 'vue'
import type { PropType } from 'vue' // Make sure you have this import
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { v4 as uuidv4 } from 'uuid'
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Label } from '@/components/ui/label'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Section, Block, SharedData } from '@/types'
import AddBlockDialog from './BlockManager.vue' // Renamed for clarity in my comments

// --- NEW: Define a correct interface for the event payload ---
interface AddBlockPayload {
    content: {
        id: number;
        label: string; // The property is 'label'
        type: string;  // The singular type like 'media', 'post'
    };
    type: string; // The selected type key like 'medias', 'posts'
}

// Props (Unchanged)
const props = defineProps({
    sections: {
        type: Array as PropType<Section[]>,
        required: true
    },
    availableElements: {
        type: Object as PropType<SharedData['availableElements']>,
        required: true
    }
})

// Emits (Unchanged)
const emit = defineEmits(['addSection', 'removeSection'])

// State (Unchanged)
const isSectionDialogVisible = ref(false)
const isBlockDialogVisible = ref(false)
const editingSectionId = ref<string | null>(null)
const sectionForm = reactive({
    title: '',
    type: '1 column' as Section['type']
})
const targetColumnInfo = ref<{ sectionId: string; columnId: string } | null>(null)

// --- Section Methods (Unchanged) ---
function openCreateSectionDialog() {
    editingSectionId.value = null
    sectionForm.title = ''
    sectionForm.type = '1 column'
    isSectionDialogVisible.value = true
}

function openEditSectionDialog(section: Section) {
    editingSectionId.value = section.id
    sectionForm.title = section.title
    sectionForm.type = section.type
    isSectionDialogVisible.value = true
}

function handleSaveSection() {
    const isEditing = !!editingSectionId.value
    if (isEditing) {
        const section = props.sections.find(s => s.id === editingSectionId.value)
        if (section) {
            const oldColumnCount = section.columns.length
            const newColumnCount = parseInt(sectionForm.type.split(' ')[0], 10)
            section.title = sectionForm.title
            section.type = sectionForm.type

            if (newColumnCount > oldColumnCount) {
                for (let i = 0; i < newColumnCount - oldColumnCount; i++) {
                    section.columns.push({ id: uuidv4(), blocks: [] })
                }
            } else if (newColumnCount < oldColumnCount) {
                const columnsToKeep = section.columns.slice(0, newColumnCount)
                const columnsToRemove = section.columns.slice(newColumnCount)
                const blocksToMove = columnsToRemove.flatMap(col => col.blocks)
                if (columnsToKeep.length > 0) {
                    columnsToKeep[0].blocks.push(...blocksToMove)
                }
                section.columns = columnsToKeep
            }
        }
    } else {
        const columnCount = parseInt(sectionForm.type.split(' ')[0], 10)
        const newSection: Section = {
            id: uuidv4(),
            title: sectionForm.title,
            type: sectionForm.type,
            columns: Array.from({ length: columnCount }, () => ({ id: uuidv4(), blocks: [] }))
        }
        emit('addSection', newSection)
    }
    isSectionDialogVisible.value = false
}

function removeSection(id: string) {
    emit('removeSection', id)
}

// --- Block Methods (CORRECTED) ---

function openAddBlockDialog(sectionId: string, columnId: string) {
    targetColumnInfo.value = { sectionId, columnId }
    isBlockDialogVisible.value = true
}

// This function now correctly handles the 'select' event payload
function handleAddBlock(payload: AddBlockPayload) {
    if (!targetColumnInfo.value) return

    const { content } = payload
    const section = props.sections.find(s => s.id === targetColumnInfo.value!.sectionId)
    const column = section?.columns.find(c => c.id === targetColumnInfo.value!.columnId)

    if (column) {
        const newBlock: Block = {
            id: uuidv4(),
            contentId: content.id,
            // FIX #1: Use the singular type from the content object
            contentType: content.type,
            // FIX #2: Use 'label' from the content object, not 'title'
            title: content.label
        }
        column.blocks.push(newBlock)
    }

    targetColumnInfo.value = null
}
</script>
<template>
    <div>
        <div class="flex items-center justify-between">
            <Label class="text-base">Sections</Label>
            <Button type="button" variant="outline" size="sm" @click="openCreateSectionDialog">
                Créer section
            </Button>
        </div>

        <div v-if="props.sections.length" class="space-y-4 mt-4">
            <div v-for="section in props.sections" :key="section.id"
                class="rounded-md border p-4 space-y-3 bg-slate-50">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold">{{ section.title }}</h3>
                    <div class="flex items-center gap-2">
                        <Button type="button" size="sm" variant="outline" @click="openEditSectionDialog(section)">
                            Éditer
                        </Button>
                        <Button type="button" size="sm" variant="destructive" @click="removeSection(section.id)">
                            Supprimer
                        </Button>
                    </div>
                </div>

                <div class="grid gap-4" :class="{
                    'grid-cols-1': section.type === '1 column',
                    'grid-cols-2': section.type === '2 columns',
                    'grid-cols-3': section.type === '3 columns',
                    'grid-cols-4': section.type === '4 columns',
                }">
                    <div v-for="column in section.columns" :key="column.id"
                        class="border-dashed border-2 p-4 min-h-[100px] bg-white flex flex-col items-start justify-start space-y-2">

                        <div v-for="block in column.blocks" :key="block.id"
                            class="w-full bg-slate-200 p-2 rounded-md text-sm font-medium flex flex-col gap-2">
                            <img v-if="block.contentType === 'medias'" :src="`/storage/media/${block.title}`"
                                :alt="block.title" class="rounded-md object-cover w-full h-auto max-h-48" />
                            <div class="text-left">
                                <span class="font-bold text-blue-600">[{{ block.contentType }}]</span>
                                {{ block.title }}
                            </div>
                        </div>

                        <Button type="button" variant="secondary" size="sm"
                            @click="openAddBlockDialog(section.id, column.id)">
                            + Add Block
                        </Button>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="text-sm text-muted-foreground">Aucune section pour le moment.</div>

        <Dialog v-model:open="isSectionDialogVisible">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>{{ editingSectionId ? 'Edit Section' : 'Add a New Section' }}</DialogTitle>
                    <DialogDescription>
                        Configurez votre section ici. Cliquez sur "Sauvegarder" lorsque vous avez terminé.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="section-title" class="text-right">Title</Label>
                        <Input id="section-title" v-model="sectionForm.title" class="col-span-3" />
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="section-type" class="text-right">Layout</Label>
                        <Select v-model="sectionForm.type">
                            <SelectTrigger class="col-span-3">
                                <SelectValue placeholder="Select a layout" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="1 column">1 Column</SelectItem>
                                    <SelectItem value="2 columns">2 Columns</SelectItem>
                                    <SelectItem value="3 columns">3 Columns</SelectItem>
                                    <SelectItem value="4 columns">4 Columns</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" @click="handleSaveSection">
                        Sauvegarder
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <AddBlockDialog v-model:open="isBlockDialogVisible" :available-elements="props.availableElements"
            @select="handleAddBlock" />
    </div>
</template>
