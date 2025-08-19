<script setup lang="ts">
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { Check, Plus, X } from 'lucide-vue-next'

type TagItem = { id: number; name: string };

const props = defineProps<{
    existingTags: TagItem[]
}>();

const emit = defineEmits<{
    (e: 'change', payload: { selected_ids: number[]; new_names: string[] }): void
}>();

const tagPopoverOpen = ref(false);
const tagQuery = ref('');
const selected_tag_ids = ref<number[]>([]);
const new_tags = ref<string[]>([]);

const allExisting = computed(() => props.existingTags ?? []);

const selectedDisplay = computed<{ id?: number; name: string; isNew: boolean }[]>(() => {
    const fromIds = selected_tag_ids.value
        .map(id => allExisting.value.find(t => t.id === id))
        .filter(Boolean)
        .map(t => ({ id: t!.id, name: t!.name, isNew: false }));
    const fromNew = new_tags.value.map(n => ({ name: n, isNew: true }));
    return [...fromIds, ...fromNew];
});

function publish() {
    emit('change', { selected_ids: [...selected_tag_ids.value], new_names: [...new_tags.value] });
}

function toggleExisting(tag: TagItem) {
    const i = selected_tag_ids.value.indexOf(tag.id);
    if (i >= 0) selected_tag_ids.value.splice(i, 1);
    else selected_tag_ids.value.push(tag.id);
    publish();
}

function addNewFromQuery() {
    const name = tagQuery.value.trim();
    if (!name) return;

    // si existe déjà => le toggler
    const exists = allExisting.value.find(t => t.name.toLowerCase() === name.toLowerCase());
    if (exists) {
        if (!selected_tag_ids.value.includes(exists.id)) selected_tag_ids.value.push(exists.id);
        tagQuery.value = '';
        publish();
        return;
    }

    // éviter doublons
    if (!new_tags.value.some(n => n.toLowerCase() === name.toLowerCase())) {
        new_tags.value.push(name);
        publish();
    }
    tagQuery.value = '';
}

function removeChip(chip: { id?: number; name: string; isNew: boolean }) {
    if (chip.isNew) {
        const idx = new_tags.value.findIndex(n => n.toLowerCase() === chip.name.toLowerCase());
        if (idx >= 0) new_tags.value.splice(idx, 1);
    } else if (chip.id) {
        const i = selected_tag_ids.value.indexOf(chip.id);
        if (i >= 0) selected_tag_ids.value.splice(i, 1);
    }
    publish();
}
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <Label>Tags</Label>
            <Popover v-model:open="tagPopoverOpen">
                <PopoverTrigger as-child>
                    <Button variant="outline" type="button" class="h-9">Manage tags</Button>
                </PopoverTrigger>
                <PopoverContent class="w-80 p-0" align="end">
                    <Command>
                        <div class="p-2">
                            <CommandInput v-model="tagQuery" placeholder="Search or create…"
                                @keydown.enter.prevent="addNewFromQuery" />
                        </div>

                        <CommandList class="max-h-64">
                            <CommandEmpty>
                                <div class="p-3 text-sm">
                                    No results. Press <kbd class="px-1 border rounded">Enter</kbd> to create
                                    <span v-if="tagQuery"> “{{ tagQuery }}”</span>.
                                </div>
                            </CommandEmpty>

                            <CommandGroup heading="Existing">
                                <CommandItem v-for="tag in allExisting" :key="tag.id" @select="toggleExisting(tag)"
                                    class="flex items-center justify-between">
                                    <span>{{ tag.name }}</span>
                                    <Check v-if="selected_tag_ids.includes(tag.id)" class="w-4 h-4 opacity-100" />
                                    <span v-else class="w-4 h-4"></span>
                                </CommandItem>
                            </CommandGroup>

                            <CommandGroup
                                v-if="tagQuery.trim() && !allExisting.some(t => t.name.toLowerCase() === tagQuery.trim().toLowerCase())">
                                <CommandItem @select="addNewFromQuery">
                                    <Plus class="w-4 h-4 mr-2" />
                                    Create “{{ tagQuery.trim() }}”
                                </CommandItem>
                            </CommandGroup>
                        </CommandList>

                        <div class="border-t p-2 flex flex-wrap gap-2">
                            <Badge v-for="chip in selectedDisplay" :key="(chip.id ?? chip.name) + '-chip'"
                                variant="secondary" class="gap-1">
                                {{ chip.name }} <span v-if="chip.isNew" class="opacity-70">(new)</span>
                                <button type="button" class="ml-1 hover:text-red-600" @click="removeChip(chip)">
                                    <X class="w-3 h-3" />
                                </button>
                            </Badge>
                        </div>
                    </Command>
                </PopoverContent>
            </Popover>
        </div>

        <!-- Aperçu compact -->
        <div class="flex flex-wrap gap-2">
            <Badge v-for="chip in selectedDisplay" :key="(chip.id ?? chip.name) + '-chip-inline'"
                :variant="chip.isNew ? 'default' : 'outline'" class="gap-1" title="Use Manage tags to remove">
                {{ chip.name }} <span v-if="chip.isNew" class="opacity-70">(new)</span>
            </Badge>
            <span v-if="selectedDisplay.length === 0" class="text-sm text-muted-foreground">No tags yet</span>
        </div>
    </div>
</template>
