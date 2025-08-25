<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

// UI
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'

// TinyMCE
import Editor from '@tinymce/tinymce-vue';

// Composants locaux
import CoverImagePicker from './components/CoverImagePicker.vue';
import TagsSelector from './components/TagsSelector.vue';

import type { BreadcrumbItem, SharedData, MediaItem, TagItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Post Creation', href: '/posts/create' },
];

// NOUVELLES PROPS : `post` est maintenant optionnel.
const props = defineProps<{
    post?: Post; // Le post à éditer, s'il existe
    media?: MediaItem[];
    tags?: TagItem[];
}>();
// Détecter le mode édition
const isEditMode = computed(() => !!props.post);
const page = usePage<SharedData>();

// Preview state dérivé du picker
const coverPreview = ref<string | null>('/storage/' + props.media?.find(m => m.id === props.post?.cover_media_id)?.file_path || null);
const coverFromLibrary = ref<{ id: number | string; url: string } | null>(null);

const tinymceInit = {
    plugins:
        'advlist anchor autolink charmap code fullscreen help image insertdatetime link lists preview searchreplace table visualblocks wordcount',
    toolbar:
        'undo redo | styles | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code preview',
    height: 500,
    automatic_uploads: false,
};

// Form Inertia
const form = useForm({
    author_id: page.props.auth.user.id,
    slug: '',

    // image (un seul des deux part au back selon la source)
    cover_image_file: null as File | null,


    title: props.post?.title || '',
    type: props.post?.type || 'post',
    // ... autres champs simples
    image_position: props.post?.image_position || 'left',
    show_title: props.post?.show_title ?? true,
    content: props.post?.content || '',
    status: props.post?.status || 'draft',

    // Champs relationnels
    cover_image_id: props.post?.cover_media_id || null,
    selected_tag_ids: props.post?.tags?.map(tag => tag.id) || [],
    new_tags: [],
});


const canSubmit = computed(() => form.title.trim().length > 0);

// Grid dynamique pour la preview
const previewGridClasses = computed(() =>
    form.image_position === 'left'
        ? 'md:grid-cols-2'
        : 'md:grid-cols-2 md:[&>.preview-image]:order-2 md:[&>.preview-text]:order-1'
);

function onCoverChosenFromLibrary(payload: { id: number | string; url: string }) {
    form.cover_image_id = payload.id;
    form.cover_image_file = null;
    coverFromLibrary.value = payload;
    coverPreview.value = payload.url;
}

function onCoverPickedFile(file: File) {
    form.cover_image_file = file;
    form.cover_image_id = null;
    coverFromLibrary.value = null;

    const reader = new FileReader();
    reader.onload = () => (coverPreview.value = String(reader.result));
    reader.readAsDataURL(file);
}

function clearCover() {
    form.cover_image_file = null;
    form.cover_image_id = null;
    coverFromLibrary.value = null;
    coverPreview.value = null;
}

function onTagsChange(payload: { selected_ids: number[]; new_names: string[] }) {
    form.selected_tag_ids = payload.selected_ids;
    form.new_tags = payload.new_names;
}

const submitForm = () => {
    // s’assure de ne pas envoyer 2 sources d’image
    if (form.cover_image_id) form.cover_image_file = null;
    if (isEditMode.value) {
        form.transform((data: Record<string, any>) => ({
            ...data,
            _method: 'put', // Ajout crucial pour Laravel
        })).post(route('posts.update', props.post!.id), {
            preserveScroll: true,
            onSuccess: () => { /* ... */ },
        });
    } else {
        form.transform((data: Record<string, any>) => {
            const fd = new FormData();
            Object.entries(data).forEach(([k, v]) => {
                if (v === null || v === undefined) return;
                if (k === 'cover_image_file' && v instanceof File) {
                    fd.append('cover_image_file', v);
                } else if (Array.isArray(v)) {
                    v.forEach((item, idx) => fd.append(`${k}[${idx}]`, String(item)));
                } else {
                    fd.append(k, String(v));
                }
            });
            console.log('Submitting form:', form);

            return fd;
        }).post('/posts', {
            onStart: () => console.log('Submitting...'),
            onProgress: (e) => console.log('Progress', e),
            onError: (e) => console.error('Errors', e),
            onSuccess: () => console.log('OK'),
            // preserveScroll: true,
        });
    }
};
</script>

<template>

    <Head title="Create Post" />

    <AppLayout :breadcrumbs="isEditMode ? [{ title: 'Edit Post' }] : breadcrumbs">
        <div class="p-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_1.5fr]">
                <!-- Colonne gauche: Form -->
                <Card class="rounded-sm">
                    <CardHeader>
                        <CardTitle>{{ isEditMode ? 'Post edit form' : 'Post creation form' }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <form @submit.prevent="submitForm" class="space-y-6">
                            <!-- Title + show_title -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <Label for="title">Title</Label>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-muted-foreground select-none">Show title</span>
                                        <Switch v-model="form.show_title" />
                                    </div>
                                </div>
                                <Input id="title" v-model="form.title" placeholder="e.g. About us" class="w-full" />
                                <div v-if="form.errors.title" class="text-sm text-red-500">{{ form.errors.title }}</div>
                            </div>

                            <!-- Type -->
                            <div class="space-y-2 w-full">
                                <Label>Type</Label>
                                <Select v-model="form.type">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem value="page">Page</SelectItem>
                                            <SelectItem value="post">Post</SelectItem>
                                            <SelectItem value="custom">Custom</SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.type" class="text-sm text-red-500">{{ form.errors.type }}</div>
                            </div>

                            <!-- Image position -->
                            <div class="space-y-2">
                                <Label for="image_position">Image position</Label>
                                <Select v-model="form.image_position">
                                    <SelectTrigger id="image_position" class="w-full">
                                        <SelectValue placeholder="Choose position" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem value="left">Left</SelectItem>
                                            <SelectItem value="right">Right</SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Tags -->
                            <TagsSelector :existing-tags="props.tags ?? []"
                                :selectedTagIds="form.selected_tag_ids ?? []" @change="onTagsChange" />
                            <div v-if="form.errors.selected_tag_ids || form.errors.new_tags"
                                class="text-sm text-red-500">
                                {{ form.errors.selected_tag_ids || form.errors.new_tags }}
                            </div>

                            <!-- Cover Image -->
                            <CoverImagePicker :media="props.media ?? []" :preview-url="coverPreview"
                                :from-library="coverFromLibrary" @choose-from-library="onCoverChosenFromLibrary"
                                @pick-file="onCoverPickedFile" @clear="clearCover" />
                            <div v-if="form.errors.cover_image_file || form.errors.cover_image_id"
                                class="text-sm text-red-500">
                                {{ form.errors.cover_image_file || form.errors.cover_image_id }}
                            </div>

                            <!-- Content -->
                            <div class="space-y-2">
                                <Label for="content">Content</Label>
                                <Editor id="post-editor" api-key="low5yo5exm1jmvkjtco7uahrm2v97dor0ki39hxriv6rj13p"
                                    v-model="form.content" :init="tinymceInit" />
                                <div v-if="form.errors.content" class="text-sm text-red-500">{{ form.errors.content }}
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="space-y-2">
                                <Label for="status">Status</Label>
                                <Input id="status" v-model="form.status" placeholder="draft | published"
                                    class="w-full" />
                                <div v-if="form.errors.status" class="text-sm text-red-500">{{ form.errors.status }}
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="pt-2">
                                <Button type="submit" :disabled="form.processing || !canSubmit">
                                    {{ form.processing ? 'Saving…' : (isEditMode ? 'Update post' : 'Create post') }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Colonne droite: Preview -->
                <Card class="rounded-md">
                    <CardHeader>
                        <CardTitle>Live Preview</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="form.show_title" class="text-2xl font-semibold leading-tight">
                            {{ form.title || 'Untitled post' }}
                        </div>

                        <div :class="['grid gap-4', previewGridClasses]">
                            <div class="preview-image">
                                <div v-if="coverPreview" class="w-full">
                                    <img :src="coverPreview" alt=""
                                        class="w-full max-h-80 object-cover rounded-lg border" />
                                </div>
                                <div v-else
                                    class="rounded-lg border bg-muted/30 p-4 text-sm text-muted-foreground grid place-items-center h-40">
                                    No image
                                </div>
                            </div>

                            <div class="preview-text prose max-w-none">
                                <div
                                    v-html="form.content || '<p class=&quot;text-muted-foreground&quot;>Your content will appear here…</p>'">
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 text-sm text-muted-foreground space-y-1">
                            <div>Type: <b>{{ form.type }}</b></div>
                            <div>Status: <b>{{ form.status }}</b></div>
                            <div>Image position: <b class="capitalize">{{ form.image_position }}</b></div>
                            <div>Title visible: <b>{{ form.show_title ? 'Yes' : 'No' }}</b></div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
