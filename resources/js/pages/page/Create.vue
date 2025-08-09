<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem, type SharedData } from '@/types'
import { Head, usePage } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import SectionManager from './Components/SectionManager.vue'
// UI kit
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Separator } from '@/components/ui/separator'

// --- INTERFACES MISES À JOUR ---

// MODIFIÉ: Le bloc contient maintenant des informations sur le contenu lié.
interface Block {
    id: string; // ID unique du bloc dans la section
    contentId: number; // ID du Post ou autre contenu source
    contentType: 'post' | 'block'; // Type de contenu pour le backend
    title: string; // Titre du contenu pour l'affichage dans l'éditeur
}

interface Column {
    id: string;
    blocks: Block[];
}

interface Section {
    id: string;
    title: string;
    type: '1 column' | '2 columns' | '3 columns' | '4 columns';
    columns: Column[];
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Page creation', href: '/pages/create' }];

// Props & Formulaire principal
const page = usePage<SharedData>()
const form = reactive({
    title: '',
    slug: '',
    type: 'page',
    status: 'draft',
    parent_id: undefined as number | undefined,
    sections: [] as Section[],
});
const availableElements = computed(() => page.props.availableElements)

const addSection = (newSection: Section) => {
    form.sections.push(newSection)
}

const removeSection = (id: string) => {
    form.sections = form.sections.filter(s => s.id !== id)
}
</script>

<template>

    <Head title="page" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_1.5fr]">
                <Card class="rounded-md">
                    <CardHeader>
                        <CardTitle>Form</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div class="space-y-2">
                            <Label for="title">Title</Label>
                            <Input id="title" v-model="form.title" placeholder="e.g. About us" class="w-full" />
                        </div>
                        <!-- Use a 3-col grid on md+, each child forced to w-full to avoid shrinking -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
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

                            <div class="space-y-2 w-full">
                                <Label>Status</Label>
                                <Select v-model="form.status">
                                    <!-- Force the trigger to fill its grid cell -->
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem value="draft">Draft</SelectItem>
                                            <SelectItem value="review">Review</SelectItem>
                                            <SelectItem value="published">Published</SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2 w-full">
                                <Label>Parent page</Label>
                                <Select v-model="form.parent_id">
                                    <!-- Force the trigger to fill its grid cell -->
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="None" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem v-for="p in availableElements.pages" :key="p.id" :value="p.id">
                                                {{ p.title }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <!-- Sections -->
                        <Separator />

                        <div class="space-y-3">
                            <SectionManager :sections="form.sections" :availableElements="availableElements"
                                @addSection="addSection" @removeSection="removeSection" />
                        </div>
                    </CardContent>
                </Card>

                <Card class="rounded-md">
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
