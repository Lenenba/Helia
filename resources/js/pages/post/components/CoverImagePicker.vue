<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs'
import { ScrollArea } from '@/components/ui/scroll-area'
import { ImagePlus, Image as ImageIcon, Upload, Check, Trash2 } from 'lucide-vue-next'
import type { MediaItem } from '@/types';

const props = defineProps<{
    media: MediaItem[],
    previewUrl: string | null,
    fromLibrary: { id: number | string; url: string } | null,
}>();

const emit = defineEmits<{
    (e: 'choose-from-library', payload: { id: number | string; url: string }): void
    (e: 'pick-file', file: File): void
    (e: 'clear'): void
}>();

const dialogOpen = ref(false);
const isDragOver = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);

function chooseFromLibrary(item: any) {
    const id = item.id;
    const url = `/storage/${item.file_path}`;
    emit('choose-from-library', { id, url });
    dialogOpen.value = false;
}

function triggerBrowse() {
    fileInputRef.value?.click();
}

function onFilePicked(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    emit('pick-file', file);
    dialogOpen.value = false;
}

function onDrop(e: DragEvent) {
    e.preventDefault();
    e.stopPropagation();
    isDragOver.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (!file) return;
    emit('pick-file', file);
    dialogOpen.value = false;
}

function onDragOver(e: DragEvent) {
    e.preventDefault();
    isDragOver.value = true;
}
function onDragLeave(e: DragEvent) {
    e.preventDefault();
    isDragOver.value = false;
}
</script>

<template>
    <div class="space-y-2">
        <Label>Cover image</Label>

        <div class="flex items-center gap-3">
            <Dialog v-model:open="dialogOpen">
                <DialogTrigger as-child>
                    <Button variant="secondary" type="button" class="flex items-center gap-2">
                        <ImagePlus class="w-4 h-4" /> Add / Choose image
                    </Button>
                </DialogTrigger>

                <DialogContent class="sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>Choose a cover image</DialogTitle>
                    </DialogHeader>

                    <Tabs default-value="library" class="w-full">
                        <TabsList class="grid w-full grid-cols-2">
                            <TabsTrigger value="library" class="text-sm">From gallery</TabsTrigger>
                            <TabsTrigger value="upload" class="text-sm">Upload</TabsTrigger>
                        </TabsList>

                        <!-- Gallery -->
                        <TabsContent value="library" class="mt-4">
                            <ScrollArea class="h-[380px] pr-3">
                                <div v-if="media?.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                    <button v-for="item in media" :key="(item as any).id" type="button"
                                        class="relative rounded-lg overflow-hidden border hover:shadow focus:outline-none"
                                        @click="chooseFromLibrary(item)">
                                        <img :src="`/storage/${(item as any).file_path}`"
                                            :alt="(item as any).file_name ?? 'media'"
                                            class="aspect-[4/3] object-cover w-full" loading="lazy" />
                                        <div v-if="fromLibrary && fromLibrary.id === (item as any).id"
                                            class="absolute inset-0 bg-black/40 grid place-items-center">
                                            <Check class="w-7 h-7 text-white" />
                                        </div>
                                    </button>
                                </div>

                                <div v-else class="text-sm text-muted-foreground">
                                    No media yet. Switch to <b>Upload</b> tab to add one.
                                </div>
                            </ScrollArea>
                        </TabsContent>

                        <!-- Upload -->
                        <TabsContent value="upload" class="mt-4 space-y-4">
                            <div class="w-full rounded-lg border-2 border-dashed transition
                       p-6 grid place-items-center text-center cursor-pointer
                       hover:bg-muted/40"
                                :class="isDragOver ? 'border-primary bg-primary/5' : 'border-muted-foreground/30'"
                                @dragover="onDragOver" @dragleave="onDragLeave" @drop="onDrop" @click="triggerBrowse">
                                <div class="flex flex-col items-center gap-2">
                                    <Upload class="w-6 h-6" />
                                    <div class="text-sm">
                                        <b>Drag & drop</b> your image here<br />
                                        or <span class="underline">browse</span> your files
                                    </div>
                                    <div class="text-xs text-muted-foreground">PNG, JPG, WEBP – up to ~10MB</div>
                                </div>
                                <input ref="fileInputRef" id="cover_file" type="file" accept="image/*" class="hidden"
                                    @change="onFilePicked" />
                            </div>
                        </TabsContent>
                    </Tabs>
                </DialogContent>
            </Dialog>

            <Button v-if="previewUrl" type="button" variant="ghost" class="text-red-600" @click="$emit('clear')"
                title="Remove cover">
                <Trash2 class="w-4 h-4" />
                Remove
            </Button>
        </div>

        <!-- Aperçu -->
        <div class="mt-3">
            <template v-if="previewUrl">
                <img :src="previewUrl" alt="Cover preview" class="max-h-48 rounded-md border object-cover" />
                <div class="mt-1 text-xs text-muted-foreground">
                    <span v-if="fromLibrary">Selected from gallery</span>
                    <span v-else>Selected from upload</span>
                </div>
            </template>
            <template v-else>
                <div class="flex items-center gap-3 rounded-md border bg-muted/30 p-3 text-sm text-muted-foreground">
                    <ImageIcon class="w-4 h-4" />
                    <span>No cover selected yet</span>
                </div>
            </template>
        </div>
    </div>
</template>
