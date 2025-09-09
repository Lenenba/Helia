<script setup lang="ts">
/**
 * PreviewBlock.vue
 * Uniform card/image sizing for multi-column sections.
 * - `uniform`: when true, use fixed image height (h-48 md:h-56) + object-cover for strict alignment.
 * - Supports "post" (image positions: top | left | right | background | none), "media" (image/video), and "generic".
 * IMPORTANT: Comments are in English as requested.
 */

import { computed } from 'vue'

/** Block type received from parent */
interface Block {
    id: string
    contentId: number
    contentType: 'post' | 'media' | 'block'
    title: string
}

/** Normalized payloads returned by resolveBlock */
interface PostPayload {
    kind: 'post'
    title: string
    excerpt?: string
    bodyHtml?: string
    imageUrl?: string
    imagePosition?: 'top' | 'left' | 'right' | 'background' | 'none'
    raw?: unknown
}
interface MediaPayload {
    kind: 'media'
    url: string
    mime?: string
    title?: string
    raw?: unknown
}
interface GenericPayload {
    kind: 'generic'
    title: string
    excerpt?: string
    image?: string
    html?: string
    raw?: unknown
}
type ResolvedPayload = PostPayload | MediaPayload | GenericPayload

const props = defineProps<{
    block: Block
    resolveBlock: (b: Block) => ResolvedPayload
    /** If true, will render post.bodyHtml using v-html (backend must sanitize HTML). */
    allowHtml?: boolean
    /** When true (multi-column sections), enforce strict visual alignment. */
    uniform?: boolean
    /** Optional override for the media box height class when uniform is true. */
    uniformImageHeightClass?: string // e.g. 'h-48 md:h-56'
}>()

const payload = computed<ResolvedPayload>(() => {
    try { return props.resolveBlock(props.block) }
    catch { return { kind: 'generic', title: props.block.title ?? 'Untitled' } }
})

/** Class helpers */
const imageBoxClass = computed(() => {
    // When uniform, use a fixed height for perfect alignment
    const fixed = props.uniformImageHeightClass || 'h-48 md:h-56'
    return props.uniform ? `${fixed} w-full` : 'aspect-[16/9]'
})
const sideImageBoxClass = computed(() => {
    // For left/right layouts, keep a consistent look; fixed height improves alignment row-wise
    const fixed = props.uniformImageHeightClass || 'h-48 md:h-56'
    return props.uniform ? `${fixed} w-full` : 'aspect-[16/10]'
})

/** Media detectors */
function isImageUrl(url?: string) {
    if (!url) return false
    return /\.(jpe?g|png|gif|webp|bmp|svg)$/i.test(url)
}
function isVideoUrl(url?: string) {
    if (!url) return false
    return /\.(mp4|webm|ogg|m4v)$/i.test(url)
}

/** Utilities */
function truncate(text = '', max = 160): string {
    if (text.length <= max) return text
    return text.slice(0, max - 1) + '…'
}
</script>

<template>
    <!-- Root: make the card fill available height -->
    <div class="flex flex-col h-full">
        <!-- MEDIA RENDER -->
        <template v-if="payload.kind === 'media'">
            <div class="bg-muted/40 border-b overflow-hidden flex items-center justify-center" :class="imageBoxClass">
                <template v-if="isImageUrl(payload.url)">
                    <img :src="payload.url" :alt="payload.title || 'Media'" class="w-full h-full"
                        :class="uniform ? 'object-cover' : 'object-contain'" draggable="false" />
                </template>
                <template v-else-if="isVideoUrl(payload.url)">
                    <video :src="payload.url" controls autoplay muted loop class="w-full h-full"
                        :class="uniform ? 'object-cover' : 'object-contain'" />
                </template>
                <template v-else>
                    <div class="w-full h-full flex items-center justify-center text-xs text-muted-foreground">
                        Unsupported media type
                    </div>
                </template>
            </div>
            <div v-if="payload.title" class="px-4 py-2 text-sm text-muted-foreground truncate">
                {{ payload.title }}
            </div>
        </template>

        <!-- POST RENDER -->
        <template v-else-if="payload.kind === 'post'">
            <!-- BACKGROUND (hero-like) -->
            <div v-if="payload.imagePosition === 'background'" class="relative overflow-hidden">
                <div class="w-full bg-center bg-cover" :class="imageBoxClass"
                    :style="payload.imageUrl ? { backgroundImage: `url('${payload.imageUrl}')` } : {}" />
                <div class="p-4 md:p-6">
                    <h3 class="text-lg font-semibold leading-tight">{{ payload.title }}</h3>
                    <p v-if="payload.excerpt" class="text-sm text-muted-foreground mt-1">
                        {{ truncate(payload.excerpt, 200) }}
                    </p>
                    <div v-if="props.allowHtml && payload.bodyHtml" class="prose prose-sm max-w-none mt-3"
                        v-html="payload.bodyHtml" />
                </div>
            </div>

            <!-- TOP -->
            <div v-else-if="payload.imagePosition === 'top'">
                <div class="bg-muted/40 border-b overflow-hidden" :class="imageBoxClass">
                    <img v-if="payload.imageUrl" :src="payload.imageUrl" alt="" class="w-full h-full"
                        :class="uniform ? 'object-cover' : 'object-cover'" draggable="false" />
                    <div v-else class="w-full h-full flex items-center justify-center text-xs text-muted-foreground">
                        No image
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="text-lg font-semibold leading-tight">{{ payload.title }}</h3>
                    <p v-if="payload.excerpt" class="text-sm text-muted-foreground mt-1">
                        {{ truncate(payload.excerpt, 200) }}
                    </p>
                    <div v-if="props.allowHtml && payload.bodyHtml" class="prose prose-sm max-w-none mt-3"
                        v-html="payload.bodyHtml" />
                </div>
            </div>

            <!-- LEFT / RIGHT -->
            <div v-else-if="payload.imagePosition === 'left' || payload.imagePosition === 'right'" class="p-4 flex-1">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-start h-full">
                    <!-- Image left -->
                    <div v-if="payload.imagePosition === 'left'" class="md:col-span-2">
                        <div class="bg-muted/40 border overflow-hidden" :class="sideImageBoxClass">
                            <img v-if="payload.imageUrl" :src="payload.imageUrl" alt=""
                                class="w-full h-full object-cover" />
                            <div v-else
                                class="w-full h-full flex items-center justify-center text-xs text-muted-foreground">No
                                image</div>
                        </div>
                    </div>

                    <!-- Text -->
                    <div :class="payload.imagePosition === 'left' ? 'md:col-span-3' : 'md:col-span-3 md:order-1'">
                        <h3 class="text-lg font-semibold leading-tight">{{ payload.title }}</h3>
                        <p v-if="payload.excerpt" class="text-sm text-muted-foreground mt-1">
                            {{ truncate(payload.excerpt, 220) }}
                        </p>
                        <div v-if="props.allowHtml && payload.bodyHtml" class="prose prose-sm max-w-none mt-3"
                            v-html="payload.bodyHtml" />
                    </div>

                    <!-- Image right -->
                    <div v-if="payload.imagePosition === 'right'" class="md:col-span-2 md:order-2">
                        <div class="bg-muted/40 border overflow-hidden" :class="sideImageBoxClass">
                            <img v-if="payload.imageUrl" :src="payload.imageUrl" alt=""
                                class="w-full h-full object-cover" />
                            <div v-else
                                class="w-full h-full flex items-center justify-center text-xs text-muted-foreground">No
                                image</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- NONE (text only) -->
            <div v-else class="p-4">
                <h3 class="text-lg font-semibold leading-tight">{{ payload.title }}</h3>
                <p v-if="payload.excerpt" class="text-sm text-muted-foreground mt-1">
                    {{ truncate(payload.excerpt, 240) }}
                </p>
                <div v-if="props.allowHtml && (payload as any).html" class="prose prose-sm max-w-none mt-3"
                    v-html="(payload as any).html" />
            </div>
        </template>

        <!-- GENERIC FALLBACK -->
        <!-- GENERIC FALLBACK -->
        <template v-else>
            <div class="bg-muted/40 border-b flex items-center justify-center text-xs text-muted-foreground"
                :class="imageBoxClass">
                No image
            </div>
            <div class="p-4">
                <h3 class="text-base font-semibold leading-tight">{{ (payload as any).title || 'Untitled' }}</h3>
                <p v-if="(payload as any).excerpt" class="text-sm text-muted-foreground mt-1">
                    {{ (payload as any).excerpt }}
                </p>
                <!-- ✅ nouveau : rendu du HTML des blocs génériques -->
                <div v-if="props.allowHtml && (payload as any).html" class="prose prose-sm max-w-none mt-3"
                    v-html="(payload as any).html" />
            </div>
        </template>
    </div>
</template>
