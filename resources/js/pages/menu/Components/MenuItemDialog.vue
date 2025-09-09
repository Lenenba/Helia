<script setup lang="ts">
import { ref, watch, PropType, computed } from 'vue'; // <-- Add 'computed' here
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { v4 as uuidv4 } from 'uuid'
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
    show: {
        type: Boolean,
        required: true,
    },
    menuItem: {
        type: Object as PropType<Node | null>,
        default: null,
    },
    pagesAndPosts: {
        type: Array as PropType<{ id: number; title: string; type: string }[]>,
        required: true,
    },
});

const emit = defineEmits(['update:show', 'save']);

const localMenuItem = ref<Node | null>(null);

// Sync prop with local state
watch(() => props.menuItem, (newValue) => {
    if (newValue) {
        localMenuItem.value = JSON.parse(JSON.stringify(newValue));
    } else {
        // Reset form for a new item
        localMenuItem.value = {
            id: uuidv4(),
            label: '',
            url: null,
            is_visible: true,
            children: [],
            linkable_type: 'custom',
            linkable_id: null,
        };
    }
}, { immediate: true });

function handleSave() {
    if (localMenuItem.value) {
        emit('save', localMenuItem.value);
        handleClose(); // Call handleClose to close the modal after saving
    }
}

function handleClose() {
    emit('update:show', false);
}

// Watch linkable_type changes to reset values
watch(() => localMenuItem.value?.linkable_type, (newType) => {
    if (localMenuItem.value) {
        if (newType === 'custom') {
            localMenuItem.value.linkable_id = null;
        } else {
            localMenuItem.value.url = null;
        }
    }
});

const title = computed(() => props.menuItem ? `Edit item: ${props.menuItem.label}` : 'Add New Item');
</script>

<template>
    <Dialog :open="show" @update:open="handleClose">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    Make changes to your menu item here. Click save when you're done.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-4">
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="label" class="text-right">Label</Label>
                    <Input id="label" v-model="localMenuItem.label" class="col-span-3" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="linkType" class="text-right">Link Type</Label>
                    <Select v-model="localMenuItem.linkable_type">
                        <SelectTrigger class="col-span-3">
                            <SelectValue placeholder="Select a link type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">Aucun lien</SelectItem>
                            <SelectItem value="custom">Lien personnalisé</SelectItem>
                            <SelectItem value="App\Models\Page">Page</SelectItem>
                            <SelectItem value="App\Models\Post">Article</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div v-if="localMenuItem.linkable_type === 'custom'" class="grid grid-cols-4 items-center gap-4">
                    <Label for="url" class="text-right">URL</Label>
                    <Input id="url" v-model="localMenuItem.url" class="col-span-3" placeholder="/path or https://" />
                </div>
                <div v-if="localMenuItem.linkable_type !== 'custom' && localMenuItem.linkable_type !== 'none'"
                    class="grid grid-cols-4 items-center gap-4">
                    <Label for="linkableId" class="text-right">
                        {{ localMenuItem.linkable_type?.includes('Page') ? 'Page' : 'Article' }}
                    </Label>
                    <Select v-model="localMenuItem.linkable_id">
                        <SelectTrigger class="col-span-3">
                            <SelectValue placeholder="Select a content item" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="item in pagesAndPosts.filter(p => p.type === (localMenuItem.linkable_type?.includes('Page') ? 'page' : 'post'))"
                                :key="item.id" :value="item.id">
                                {{ item.title }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <DialogFooter>
                <Button type="button" variant="outline" @click="handleClose">Cancel</Button>
                <Button type="submit" @click="handleSave">Save changes</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
