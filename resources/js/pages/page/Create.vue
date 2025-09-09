<script setup lang="ts">
/**
 * Create.vue
 * Thin wrapper that reuses PageEditor in "create" mode.
 */

import { usePage } from '@inertiajs/vue3'
import PageEditor from './Partials/PageEditor.vue'
import type { SharedData } from '@/types'

const page = usePage<SharedData>()

// Available elements are assumed to be in shared props.
const availableElements = page.props.availableElements
</script>

<template>
    <PageEditor mode="create" :available-elements="availableElements" store-route-name="pages.store"
        @saved="() => { }" />
</template>
