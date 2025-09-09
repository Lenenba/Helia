<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { v4 as uuidv4 } from 'uuid'
import type { BreadcrumbItem } from '@/types'
import TreeEditor from './Components/TreeEditor.vue'
import axios from 'axios';
import MenuItemDialog from './Components/MenuItemDialog.vue'; // Importez le nouveau composant

type Node = {
    id: number | string
    label: string
    url?: string | null
    is_visible: boolean
    children?: Node[]
    linkable_type: 'custom' | 'App\\Models\\Page' | 'App\\Models\\Post' | 'none'
    linkable_id?: number | null
}

const props = defineProps<{
    menu: { id: number; name: string; slug: string; settings?: Record<string, any> },
    tree: Node[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Edit', href: `/menus/${props.menu.id}/edit` },
];

const tree = ref<Node[]>(props.tree ? JSON.parse(JSON.stringify(props.tree)) : []);
const pagesAndPosts = ref([]);

// State for the dialog
const showMenuItemDialog = ref(false);
const currentMenuItem = ref<Node | null>(null);

const fetchPagesAndPosts = async () => {
    try {
        const response = await axios.get(route('api.content.index'));
        pagesAndPosts.value = response.data;
    } catch (error) {
        console.error('Erreur lors de la récupération des pages et articles:', error);
    }
}

onMounted(fetchPagesAndPosts);

const form = useForm({
    name: props.menu.name,
    slug: props.menu.slug,
    settings: props.menu.settings ?? {},
    tree: tree.value,
})

watch(tree, (newTree) => {
    form.tree = newTree;
}, { deep: true });

const saveMenu = () => {
    form.put(route('menus.update', props.menu.id), {
        preserveScroll: true,
        onSuccess: () => {
            console.log('Menu and tree saved successfully!');
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
        }
    });
};

const openDialogForNewItem = () => {
    currentMenuItem.value = null; // Set to null to create a new item
    showMenuItemDialog.value = true;
};

const handleSaveNewItem = (item: Node) => {
    tree.value.push(item);
};

const jsonSettings = computed({
    get: () => JSON.stringify(form.settings ?? {}, null, 2),
    set: (val: string) => {
        try {
            form.settings = val.trim() === '' ? {} : JSON.parse(val);
        } catch { }
    }
})
</script>

<template>

    <Head title="Menus" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-8">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-semibold">Edit menu — {{ form.name }}</h1>
                <div class="flex gap-2">
                    <button class="px-3 py-2 rounded-sm border" @click="saveMenu">Update Menu</button>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-8">
                <div class="p-4 rounded-sm border">
                    <label class="block text-sm mb-1">Name</label>
                    <input v-model="form.name" class="w-full rounded-sm border px-3 py-2 mb-4" />

                    <label class="block text-sm mb-1">Slug</label>
                    <input v-model="form.slug" class="w-full rounded-sm border px-3 py-2 mb-4" />

                    <label class="block text-sm mb-1">Settings (JSON)</label>
                    <textarea v-model="jsonSettings"
                        class="w-full rounded-sm border px-3 py-2 h-40 font-mono"></textarea>

                    <div class="mt-4 flex items-end gap-2">
                        <button class="px-3 py-2 rounded border" @click="openDialogForNewItem">Ajouter un élément
                            racine</button>
                    </div>
                </div>

                <div class="p-4 rounded-sm border">
                    <TreeEditor v-model="tree" :pages-and-posts="pagesAndPosts" />
                </div>
            </div>
        </div>
    </AppLayout>

    <MenuItemDialog :show="showMenuItemDialog" :menu-item="currentMenuItem" :pages-and-posts="pagesAndPosts"
        @update:show="showMenuItemDialog = $event" @save="handleSaveNewItem" />
</template>
