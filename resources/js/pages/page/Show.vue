<script setup lang="ts">
import SiteLayout from '@layouts/SiteLayout.vue'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

// Props from PageTransformer::toInertia()
const props = defineProps<{
    page: { id: number; title: string; slug: string; excerpt?: string | null; seo?: any; layout?: string };
    sections: Array<{
        id: number; title?: string | null; key?: string | null; settings?: Record<string, any>;
        blocks: Array<{
            id: number; type: string; weight: number; settings?: Record<string, any>; data?: Record<string, any>;
        }>;
    }>;
}>()

// Simple block registry. Register new block types here.
const registry: Record<string, any> = {
    'rich_text': defineAsyncComponent(() => import('@/components/blocks/RichTextBlock.vue')),
    'image': defineAsyncComponent(() => import('@/components/blocks/ImageBlock.vue')),
    'post_teaser': defineAsyncComponent(() => import('@/components/blocks/PostTeaserBlock.vue')),
}

function cmpFor(type: string) {
    return registry[type] ?? defineAsyncComponent(() => import('@/components/blocks/UnknownBlock.vue'))
}

const title = computed(() => props.page?.seo?.title ?? props.page?.title ?? 'Page')
const description = computed(() => props.page?.seo?.description ?? props.page?.excerpt ?? '')
const image = computed(() => props.page?.seo?.image ?? null)
</script>

<template>
    <SiteLayout>

        <Head :title="title">
            <meta v-if="description" name="description" :content="description" />
            <meta v-if="image" property="og:image" :content="image" />
        </Head>

        <!-- Example: layout-aware wrapper -->
        <div class="mx-auto max-w-6xl px-4 py-8">
            <h1 class="mb-6 text-2xl font-semibold">{{ page.title }}</h1>

            <section v-for="section in sections" :key="section.id" class="mb-10">
                <h2 v-if="section.title" class="mb-4 text-lg font-medium">{{ section.title }}</h2>

                <div class="grid gap-6 md:grid-cols-12">
                    <component v-for="blk in section.blocks" :key="blk.id" :is="cmpFor(blk.type)"
                        :settings="blk.settings" :data="blk.data" class="md:col-span-12" />
                </div>
            </section>
        </div>
    </SiteLayout>
</template>
