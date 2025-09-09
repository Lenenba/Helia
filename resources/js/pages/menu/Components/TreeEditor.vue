<script setup lang="ts">
import { computed, defineAsyncComponent, ref } from 'vue';
import { Button } from '@/components/ui/button'; // Assurez-vous que cette importation est présente
import { PropType } from 'vue';
import { v4 as uuidv4 } from 'uuid';
import MenuItemDialog from './MenuItemDialog.vue';
import {
    LucideEye,
    LucideEyeOff,
    LucidePlus,
    LucidePencil,
    LucideTrash,
} from 'lucide-vue-next';

// Use a dynamic import for vuedraggable to keep the main component light
const Draggable = defineAsyncComponent(() => import('vuedraggable'))

type Node = {
    id: number | string
    label: string
    url?: string | null
    is_visible: boolean
    children?: Node[]
    linkable_type: 'custom' | 'App\\Models\\Page' | 'App\\Models\\Post' | 'none'
    linkable_id?: number | null
}

const props = defineProps({
    modelValue: {
        type: Array as PropType<Node[]>,
        required: true,
    },
    // New prop for the dropdown options
    pagesAndPosts: {
        type: Array as PropType<{ id: number; title: string; type: string }[]>,
        required: true,
    },
})

const emit = defineEmits(['update:modelValue'])

const list = computed({
    get: () => props.modelValue,
    set: (value: Node[]) => emit('update:modelValue', value),
})

// Dialog state
const showDialog = ref(false);
const editingItem = ref<Node | null>(null);
const parentList = ref<Node[] | null>(null);

function openDialogToAddChild(parent: Node) {
    editingItem.value = {
        id: uuidv4(),
        label: 'New item',
        url: '',
        is_visible: true,
        children: [],
        linkable_type: 'custom',
        linkable_id: null,
    };
    parentList.value = parent.children || [];
    showDialog.value = true;
}

function openDialogToEditItem(item: Node, currentList: Node[]) {
    editingItem.value = item;
    parentList.value = currentList;
    showDialog.value = true;
}

function handleSave(updatedItem: Node) {
    if (editingItem.value && parentList.value) {
        // Find the index of the item to update
        const index = parentList.value.findIndex(i => i.id === editingItem.value!.id);
        if (index !== -1) {
            // Update an existing item
            Object.assign(parentList.value[index], updatedItem);
        } else {
            // Add a new item to the correct parent list
            parentList.value.push(updatedItem);
        }
    }
    showDialog.value = false;
    editingItem.value = null;
    parentList.value = null;
}

function removeItem(list: Node[], id: number | string) {
    const idx = list.findIndex(n => n.id === id);
    if (idx >= 0) {
        list.splice(idx, 1);
    }
}

function toggle(node: Node) {
    node.is_visible = !node.is_visible;
}
</script>

<template>
    <div class="space-y-2">
        <Draggable v-model="list" item-key="id" group="menu" handle=".handle" animation="150" class="grid gap-2">
            <template #item="{ element }">
                <div class="p-3 rounded-sm border bg-white/70 dark:bg-neutral-900/60">
                    <div class="grid items-center gap-2 grid-cols-3">
                        <span class="handle cursor-grab select-none px-2 text-xl text-neutral-400">⋮</span>
                        <span class="font-medium">{{ element.label }}</span>

                        <div class="grid grid-cols-2 justify-end gap-2">
                            <div class="grid gap-2">
                                <Button variant="outline" size="default" @click="toggle(element)">
                                    <LucideEye v-if="element.is_visible" class="mr-1 h-4 w-4" />
                                    <LucideEyeOff v-else class="mr-1 h-4 w-4" />
                                    {{ element.is_visible ? 'Visible' : 'Caché' }}
                                </Button>
                                <Button variant="outline" size="default" @click="openDialogToAddChild(element)">
                                    <LucidePlus class="mr-1 h-4 w-4" />
                                    Enfant
                                </Button>
                            </div>
                            <div class="grid  gap-2">
                                <Button variant="outline" size="default" @click="openDialogToEditItem(element, list)">
                                    <LucidePencil class="mr-1 h-4 w-4" />
                                    Modifier
                                </Button>
                                <Button variant="outline" size="default"
                                    class="text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700"
                                    @click="removeItem(list, element.id)">
                                    <LucideTrash class="mr-1 h-4 w-4" />
                                    Supprimer
                                </Button>
                            </div>
                        </div>

                    </div>

                    <TreeEditor v-if="element.children && element.children.length" v-model="element.children"
                        :pages-and-posts="pagesAndPosts" class="mt-2 pl-6" />
                </div>
            </template>
        </Draggable>
    </div>

    <MenuItemDialog :show="showDialog" :menu-item="editingItem" :pages-and-posts="pagesAndPosts"
        @update:show="showDialog = $event" @save="handleSave" />
</template>
