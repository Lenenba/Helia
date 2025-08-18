<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import MediaUploadForm from '../media/Components/MediaUploadForm.vue';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { type BreadcrumbItem, type SharedData } from '@/types';
// 1. Import the official TinyMCE Vue component
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import Editor from '@tinymce/tinymce-vue';
import { Import } from 'lucide-vue-next';
// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Post Creation',
        href: '/posts/create',
    },
];

// Shared page props
const page = usePage<SharedData>();

// Form data remains the same
const form = useForm({
    title: '',
    type: 'post',
    author_id: page.props.auth.user.id,
    slug: '',
    cover_image: null as File | null,
    content: '', // This will be updated by the Editor component via v-model
    status: 'draft',
});
// Handle form submission (unchanged)
const submitForm = () => {
    form.post('/posts', {
        onFinish: () => {
            // Optional: handle success (e.g., redirect) or failure (e.g., show errors)
        }
    });
};
</script>

<template>

    <Head title="Create Post" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_1.5fr]">
                <Card class="rounded-sm">
                    <CardHeader>
                        <CardTitle>Post creation form</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <form @submit.prevent="submitForm" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="title">Title</Label>
                                <Input id="title" v-model="form.title" placeholder="e.g. About us" class="w-full" />
                            </div>
                            <div class="space-y-2 w-full">
                                <Label>Type</Label>
                                <Select v-model="form.type">
                                    <!-- Force the trigger to fill its grid cell -->
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
                            </div>
                            <MediaUploadForm />
                        </form>
                        <main id="sample">
                            <editor id="uuid" apiKey="low5yo5exm1jmvkjtco7uahrm2v97dor0ki39hxriv6rj13p" :init="{
                                plugins: 'advlist anchor autolink charmap code fullscreen help image insertdatetime link lists preview searchreplace table visualblocks wordcount',
                                toolbar: 'undo redo | styles | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link',
                                height: 500,
                            }" />
                        </main>
                    </CardContent>
                </Card>

                <Card class="rounded-md">
                </Card>
            </div>
        </div>

    </AppLayout>
</template>
