<script setup lang="ts">
/**
 * PageEditor.vue
 * Reusable page editor for both CREATE and EDIT flows.
 * - mode: 'create' | 'edit'
 * - initialPage: backend payload for edit (optional for create)
 * - availableElements: lookup lists (posts, blocks, medias, pages...)
 *
 * The component:
 * - Hydrates UI sections/columns/blocks from backend shape (defensive).
 * - Builds a compact payload for store/update (backend re-maps safely).
 * - Submits with Inertia router.post / router.put based on mode.
 *
 * IMPORTANT:
 * - All comments are in English (per user request).
 */

import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { computed, reactive, ref, watch } from 'vue'
import { v4 as uuidv4 } from 'uuid'
import SectionManager from '../Components/SectionManager.vue'
import PreviewBlock from '../Components/PreviewBlock.vue'

import { type BreadcrumbItem, type SharedData } from '@/types'

// UI kit
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Separator } from '@/components/ui/separator'
import { Button } from '@/components/ui/button'

/* -------------------------------------------
   TYPES USED BY THE EDITOR (UI model)
------------------------------------------- */
interface UiBlock {
    id: string
    contentId: number
    contentType: 'post' | 'media' | 'block'
    title: string
}
interface UiColumn {
    id: string
    blocks: UiBlock[]
}
interface UiSection {
    id: string
    title: string
    type: '1 column' | '2 columns' | '3 columns' | '4 columns'
    columns: UiColumn[]
}

/** Loose backend shapes (defensive typing) */
type AnyRecord = Record<string, any>
interface InitialPage {
    id?: number
    title?: string
    slug?: string | null
    type?: string
    status?: string
    parent_id?: number | null
    sections?: AnyRecord[]
}

/* -------------------------------------------
   PROPS & EMITS
------------------------------------------- */
const props = defineProps<{
    mode: 'create' | 'edit'
    /** Backend page model for edit; optional for create */
    initialPage?: InitialPage
    /** Shared available elements (posts, blocks, medias, pages) */
    availableElements: SharedData['availableElements']
    /** Route names used on submit (defaults assumed below if not provided) */
    storeRouteName?: string // e.g. 'pages.store'
    updateRouteName?: string // e.g. 'pages.update'
}>()

const emit = defineEmits<{
    (e: 'saved', data: { id?: number; mode: 'create' | 'edit' }): void
}>()

/* -------------------------------------------
   BREADCRUMBS / HEAD
------------------------------------------- */
const breadcrumbs: BreadcrumbItem[] = computed(() => {
    if (props.mode === 'edit') {
        return [{ title: 'Edit page', href: '#' }]
    }
    return [{ title: 'Page creation', href: '/pages/create' }]
})

/* -------------------------------------------
   FORM STATE
------------------------------------------- */
const form = reactive({
    title: (props.initialPage?.title ?? '') as string,
    slug: (props.initialPage?.slug ?? '') as string,
    type: (props.initialPage?.type ?? 'page') as string,
    status: (props.initialPage?.status ?? 'draft') as string,
    parent_id: (props.initialPage?.parent_id ?? null) as number | null,
    sections: [] as UiSection[],
})
const isSaving = ref(false)

/* -------------------------------------------
   AVAILABLE ELEMENTS LOOKUPS (for preview)
------------------------------------------- */
const postsById = computed<Record<number, any>>(
    () => Object.fromEntries((props.availableElements?.posts ?? []).map(p => [Number(p.id), p]))
)
const blocksById = computed<Record<number, any>>(
    () => Object.fromEntries((props.availableElements?.blocks ?? []).map(b => [Number(b.id), b]))
)
const mediasById = computed<Record<number, any>>(
    () => Object.fromEntries((props.availableElements?.medias ?? []).map(m => [Number(m.id), m]))
)

/* -------------------------------------------
   EDITOR ACTIONS (sections management)
------------------------------------------- */
const addSection = (s: UiSection) => form.sections.push(s)
const removeSection = (id: string) => (form.sections = form.sections.filter(sec => sec.id !== id))

/* -------------------------------------------
   PREVIEW HELPERS
------------------------------------------- */
function resolveBlockPayload(b: UiBlock) {
    if (b.contentType === 'media') {
        const m = mediasById.value[b.contentId]
        const url = m?.url ?? m?.src ?? (m?.path ? `/storage/${m.path}` : undefined)
        return { kind: 'media', url, mime: m?.mime, title: m?.title ?? m?.filename ?? '' }
    }
    if (b.contentType === 'post') {
        const p = postsById.value[b.contentId]
        const imageUrl = p?.cover_url ?? p?.image ?? p?.hero_image ?? undefined
        const imagePosition = (p?.image_position ?? 'top') as 'top' | 'left' | 'right' | 'background' | 'none'
        return {
            kind: 'post',
            title: p?.title ?? b.title ?? 'Untitled post',
            excerpt: p?.excerpt ?? p?.summary ?? '',
            bodyHtml: p?.body ?? p?.content ?? undefined, // ONLY render if sanitized (we enable in preview)
            imageUrl,
            imagePosition,
            raw: p,
        }
    }
    const blk = blocksById.value[b.contentId]
    return {
        kind: 'generic',
        title: blk?.title ?? b.title ?? 'Untitled block',
        excerpt: blk?.excerpt ?? '',
        image: blk?.image ?? blk?.cover ?? undefined,
        html: blk?.html ?? blk?.content ?? undefined,
        raw: blk,
    }
}

function sectionGridCols(type: UiSection['type']) {
    switch (type) {
        case '1 column': return 'grid-cols-1'
        case '2 columns': return 'grid-cols-1 md:grid-cols-2'
        case '3 columns': return 'grid-cols-1 md:grid-cols-3'
        case '4 columns': return 'grid-cols-1 md:grid-cols-4'
    }
}

/* -------------------------------------------
   DEVICE PREVIEW
------------------------------------------- */
type PreviewMode = 'desktop' | 'tablet' | 'mobile'
const previewMode = ref<PreviewMode>('desktop')
const previewWidthClass = computed(() =>
    previewMode.value === 'mobile' ? 'max-w-[380px] w-full'
        : previewMode.value === 'tablet' ? 'max-w-[820px] w-full'
            : 'max-w-[1200px] w-full'
)
const previewPageTitle = computed(() => form.title?.trim() || 'Untitled page')

/* -------------------------------------------
   HYDRATION: BACKEND -> UI
------------------------------------------- */
/**
 * Safely read number of columns from any backend section shape.
 */
function readColumnsCount(section: AnyRecord): number {
    // preferred in your previous payload: layout.columns_count
    const fromLayout = Number(section?.layout?.columns_count)
    if (Number.isInteger(fromLayout) && fromLayout > 0 && fromLayout <= 4) return fromLayout

    // fallback: try columns array in layout
    const arr = section?.layout?.columns
    if (Array.isArray(arr) && arr.length) return Math.min(arr.length, 4)

    // ultimate fallback
    return 1
}

/**
 * Convert any backend "section" into our UI UiSection.
 * Accepts shapes like:
 * - { title, ui_type/db_type_hint, layout: { columns_count, columns: [{ blocks: [...] }] } }
 * - or even minimal partial data.
 */
function toUiSection(section: AnyRecord): UiSection {
    // Read label type (fallback to '1 column')
    const uiTypeLabel: UiSection['type'] =
        (section?.ui_type as UiSection['type'])
        ?? (section?.type as UiSection['type'])
        ?? '1 column'

    const colCount = readColumnsCount(section)

    // Build columns with blocks from layout if present
    const layoutCols = Array.isArray(section?.layout?.columns) ? section.layout.columns : []
    const uiColumns: UiColumn[] = Array.from({ length: colCount }).map((_, idx) => {
        const sourceCol = layoutCols[idx] || {}
        const sourceBlocks: any[] = Array.isArray(sourceCol?.blocks) ? sourceCol.blocks : []

        const blocks: UiBlock[] = sourceBlocks.map((b, bIdx) => ({
            id: String(b?.id ?? uuidv4()),
            contentId: Number(b?.contentId ?? b?.content_id ?? b?.id ?? 0),
            contentType: (b?.contentType ?? b?.content_type ?? 'block') as UiBlock['contentType'],
            title: String(b?.title ?? b?.label ?? 'Untitled'),
        }))

        return { id: uuidv4(), blocks }
    })

    return {
        id: String(section?.id ?? uuidv4()),
        title: String(section?.title ?? ''),
        type: uiTypeLabel,
        columns: uiColumns,
    }
}

/**
 * Initialize UI sections from props.initialPage (edit mode).
 */
function hydrateUiFromBackend() {
    form.sections = []
    const raw = props.initialPage?.sections ?? []
    if (!Array.isArray(raw) || !raw.length) return
    form.sections = raw.map(toUiSection)
}

/* Run hydration on mount / when initialPage changes */
watch(() => props.initialPage, () => {
    if (props.mode === 'edit') hydrateUiFromBackend()
}, { immediate: true })

/* -------------------------------------------
   SUBMIT: UI -> BACKEND
------------------------------------------- */
function mapUiTypeToDbEnum(type: UiSection['type']): 'one_column' | 'two_columns' | 'hero' | 'gallery' {
    // We only send this as a hint; backend also re-maps safely.
    switch (type) {
        case '1 column': return 'one_column'
        case '2 columns': return 'two_columns'
        case '3 columns': return 'gallery'
        case '4 columns': return 'gallery'
    }
}

/** Flatten the editor model into a compact payload. */
function buildPayload() {
    return {
        title: form.title,
        slug: form.slug || null,
        type: form.type,
        status: form.status,
        parent_id: form.parent_id ?? null,
        sections: form.sections.map((s, sIdx) => ({
            title: s.title,
            ui_type: s.type,
            db_type_hint: mapUiTypeToDbEnum(s.type),
            order: sIdx,
            color: '#ffffff',
            layout: {
                columns_count: s.columns.length,
                columns: s.columns.map((c, cIdx) => ({
                    index: cIdx,
                    blocks: c.blocks.map((b, bIdx) => ({
                        id: b.id,
                        contentId: b.contentId,
                        contentType: b.contentType,
                        title: b.title ?? null,
                        order: bIdx,
                    })),
                })),
            },
        })),
    }
}

async function submitForm() {
    if (!form.title?.trim()) {
        alert('Title is required.')
        return
    }

    console.log('Submitting payload:', buildPayload())
    isSaving.value = true
    const payload = buildPayload()

    const storeName = props.storeRouteName || 'pages.store'
    const updateName = props.updateRouteName || 'pages.update'

    if (props.mode === 'create') {
        router.post(route(storeName), payload, {
            preserveScroll: true,
            onFinish: () => { isSaving.value = false },
            onSuccess: () => emit('saved', { id: undefined, mode: 'create' }),
        })
    } else {
        const id = props.initialPage?.id
        if (!id) {
            isSaving.value = false
            alert('Missing page id for edit.')
            return
        }
        router.put(route(updateName, id), payload, {
            preserveScroll: true,
            onFinish: () => { isSaving.value = false },
            onSuccess: () => emit('saved', { id, mode: 'edit' }),
            onError: (e: unknown) => { console.error('Failed to update the page.', e) },
        })
    }
}
</script>

<template>

    <Head :title="mode === 'edit' ? 'Edit page' : 'Create page'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <form class="p-4" @submit.prevent="submitForm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_1.5fr]">
                <!-- LEFT: Editor -->
                <Card class="rounded-md">
                    <CardHeader>
                        <CardTitle>Form</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div class="space-y-2">
                            <Label for="title">Title</Label>
                            <Input id="title" v-model="form.title" placeholder="e.g. About us" class="w-full" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
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
                            </div>

                            <div class="space-y-2 w-full">
                                <Label>Status</Label>
                                <Select v-model="form.status">
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

                        <Separator />

                        <div class="space-y-3">
                            <SectionManager :sections="form.sections" :availableElements="availableElements"
                                @addSection="addSection" @removeSection="removeSection" />
                        </div>

                        <div class="flex justify-end pt-4">
                            <Button type="submit" :disabled="isSaving">
                                <span v-if="isSaving">{{ mode === 'edit' ? 'Updating…' : 'Saving…' }}</span>
                                <span v-else>{{ mode === 'edit' ? 'Update Page' : 'Save Page' }}</span>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- RIGHT: Live Preview -->
                <Card class="rounded-md">
                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between">
                            <CardTitle>Live preview</CardTitle>
                            <div class="inline-flex gap-2">
                                <Button size="sm" variant="outline"
                                    :class="previewMode === 'desktop' ? 'ring-2 ring-ring' : ''"
                                    @click="previewMode = 'desktop'">Desktop</Button>
                                <Button size="sm" variant="outline"
                                    :class="previewMode === 'tablet' ? 'ring-2 ring-ring' : ''"
                                    @click="previewMode = 'tablet'">Tablet</Button>
                                <Button size="sm" variant="outline"
                                    :class="previewMode === 'mobile' ? 'ring-2 ring-ring' : ''"
                                    @click="previewMode = 'mobile'">Mobile</Button>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <div class="w-full flex justify-center">
                            <div class="border rounded-lg overflow-hidden bg-background shadow-sm"
                                :class="previewWidthClass">
                                <div class="px-5 py-4 border-b bg-muted/40">
                                    <div class="flex items-center justify-between">
                                        <div class="font-semibold truncate">{{ previewPageTitle }}</div>
                                        <div class="text-xs text-muted-foreground">Preview</div>
                                    </div>
                                </div>

                                <div class="p-5 space-y-8">
                                    <div v-if="!form.sections.length"
                                        class="text-sm text-muted-foreground text-center py-10">
                                        No sections yet. Use the editor to add a section and you will see it live here.
                                    </div>

                                    <section v-for="section in form.sections" :key="section.id" class="space-y-4">
                                        <h2 class="text-lg font-semibold tracking-tight">
                                            {{ section.title || 'Untitled section' }}
                                        </h2>

                                        <!-- Equal-height rows + stretched items -->
                                        <div class="grid gap-4 items-stretch auto-rows-fr"
                                            :class="sectionGridCols(section.type)">
                                            <div v-for="col in section.columns" :key="col.id" class="space-y-4">
                                                <article v-for="blk in col.blocks" :key="blk.id"
                                                    class="rounded-lg border bg-card text-card-foreground overflow-hidden h-full flex flex-col">
                                                    <PreviewBlock :block="blk" :resolveBlock="resolveBlockPayload"
                                                        :allowHtml="true" :uniform="section.type !== '1 column'"
                                                        uniformImageHeightClass="h-48 md:h-56" />
                                                </article>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <div class="px-5 py-4 border-t bg-muted/30 text-xs text-muted-foreground">
                                    © Preview
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </form>
    </AppLayout>
</template>
