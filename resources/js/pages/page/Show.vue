<script setup lang="ts">
import SiteLayout from '@/Layouts/SiteLayout.vue'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import BlockRenderer from '@/components/blocks/BlockRenderer.vue'

type AnyRec = Record<string, any>

const props = defineProps<{
    page?: AnyRec
    sections?: AnyRec[]
}>()

const view = computed(() => {
    if (props.page && Array.isArray(props.sections)) {
        return { title: String(props.page?.title ?? ''), sections: props.sections, shape: 'inertia' as const }
    }
    if (props.page && Array.isArray(props.page.sections)) {
        return { title: String(props.page?.title ?? ''), sections: props.page.sections, shape: 'dto' as const }
    }
    return { title: 'Untitled', sections: [], shape: 'unknown' as const }
})

function readColumnsCount(section: AnyRec): number {
    const fromLayout = Number(section?.layout?.columns_count)
    if (Number.isInteger(fromLayout) && fromLayout > 0) return Math.min(fromLayout, 4)
    const sCnt = Number(section?.settings?.columns_count)
    if (Number.isInteger(sCnt) && sCnt > 0) return Math.min(sCnt, 4)
    return 1
}

function getColumns(section: AnyRec): Array<{ blocks: AnyRec[] }> {
    if (Array.isArray(section?.layout?.columns)) {
        return section.layout.columns.map((c: AnyRec) => ({
            blocks: Array.isArray(c?.blocks) ? c.blocks : []
        }))
    }
    const blocks = Array.isArray(section?.blocks) ? section.blocks : []
    const cols = readColumnsCount(section)
    if (cols <= 1) return [{ blocks }]
    const buckets = Array.from({ length: cols }, () => ({ blocks: [] as AnyRec[] }))
    blocks.forEach((b: AnyRec, i: number) => { buckets[i % cols].blocks.push(b) })
    return buckets
}

function gridClass(section: AnyRec): string {
    const c = readColumnsCount(section)
    if (c === 4) return 'grid grid-cols-1 md:grid-cols-4 gap-6'
    if (c === 3) return 'grid grid-cols-1 md:grid-cols-3 gap-6'
    if (c === 2) return 'grid grid-cols-1 md:grid-cols-2 gap-6'
    return 'grid grid-cols-1 gap-6'
}

function resolveType(block: AnyRec): string {
    if (typeof block?.type === 'string') return block.type
    if (typeof block?.contentType === 'string') return block.contentType
    return 'block'
}

function resolveBlockDisplay(block: AnyRec) {
    const type = resolveType(block)
    if (block?.settings || block?.data) {
        if (type === 'image') {
            return { kind: 'image', url: block?.data?.url ?? block?.settings?.url, alt: block?.data?.alt ?? block?.settings?.alt ?? '' }
        }
        if (type === 'post_teaser') {
            return { kind: 'post', title: block?.data?.title ?? '', excerpt: block?.data?.excerpt ?? '', cover: block?.data?.cover ?? null, href: block?.data?.href ?? '#' }
        }
        if (type === 'rich_text') {
            return { kind: 'html', html: block?.settings?.html ?? block?.data?.html ?? '' }
        }
        return { kind: 'generic', payload: { type, ...block } }
    }
    if (block?.contentType === 'media') return { kind: 'media', id: block?.contentId, title: block?.title ?? '' }
    if (block?.contentType === 'post') return { kind: 'post_ref', id: block?.contentId, title: block?.title ?? '' }
    return { kind: 'generic', payload: block }
}
</script>

<template>
    <SiteLayout>

        <Head :title="view.title" />
        <div class="mx-auto max-w-6xl px-4 py-10">
            <h1 class="text-2xl font-semibold mb-6">{{ view.title }}</h1>
            <section v-for="(s, sIdx) in view.sections" :key="s.id ?? sIdx" class="mb-10">
                <div v-if="s.title" class="mb-4">
                    <h2 class="text-lg font-semibold">{{ s.title }}</h2>
                </div>
                <div :class="gridClass(s)">
                    <div v-for="(col, cIdx) in getColumns(s)" :key="cIdx" class="space-y-4">
                        <article v-for="(b, bIdx) in col.blocks" :key="b.id ?? bIdx"
                            class="rounded-lg border bg-card text-card-foreground overflow-hidden h-full">
                            <BlockRenderer :block="b" :display="resolveBlockDisplay(b)" />
                        </article>
                    </div>
                </div>
            </section>
        </div>
    </SiteLayout>
</template>
