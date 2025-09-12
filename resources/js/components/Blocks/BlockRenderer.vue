<script setup lang="ts">
type AnyRec = Record<string, any>

const props = defineProps<{
    block: AnyRec
    display: AnyRec  // résultat de resolveBlockDisplay
}>()
</script>

<template>
    <!-- IMAGE -->
    <div v-if="display?.kind === 'image'" class="w-full">
        <img :src="display.url" :alt="display.alt ?? ''" class="w-full h-auto object-cover" v-if="display.url" />
        <div v-if="display.caption" class="text-xs text-muted-foreground p-2">
            {{ display.caption }}
        </div>
        <div v-else-if="!display.url" class="p-4 text-sm text-muted-foreground">Missing image URL</div>
    </div>

    <!-- POST TEASER (résolu) -->
    <div v-else-if="display?.kind === 'post'" class="p-4 space-y-2">
        <div class="font-medium">{{ display.title || 'Untitled post' }}</div>
        <p class="text-sm text-muted-foreground" v-if="display.excerpt">{{ display.excerpt }}</p>
        <img v-if="display.cover" :src="display.cover" class="w-full h-44 object-cover rounded" />
        <div class="pt-2">
            <a v-if="display.href" :href="display.href" class="text-sm underline">Read more</a>
        </div>
    </div>

    <!-- RICH TEXT / HTML -->
    <div v-else-if="display?.kind === 'html'" class="prose dark:prose-invert max-w-none p-4" v-html="display.html">
    </div>

    <!-- MEDIA REFERENCE (DTO “éditeur”) -->
    <div v-else-if="display?.kind === 'media'" class="p-4 text-sm">
        Media #{{ display.id }} — {{ display.title || 'Untitled media' }}
        <!-- Ici, si tu veux, tu peux résoudre l’URL réelle via un store global ou props.availableElements -->
    </div>

    <!-- POST REFERENCE (DTO “éditeur”) -->
    <div v-else-if="display?.kind === 'post_ref'" class="p-4 text-sm">
        Post #{{ display.id }} — {{ display.title || 'Untitled post' }}
    </div>

    <!-- FALLBACK -->
    <div v-else class="p-4 text-sm text-muted-foreground">
        Unknown block type
    </div>
</template>
