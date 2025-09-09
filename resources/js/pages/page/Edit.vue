<script setup lang="ts">
/**
 * Edit.vue
 * Thin wrapper that reuses PageEditor in "edit" mode.
 * Expects the backend to pass `page` (the model) + `availableElements` in props.
 */

import { usePage } from '@inertiajs/vue3'
import PageEditor from './Partials/PageEditor.vue'
import type { SharedData } from '@/types'

type EditProps = SharedData & {
    page: {
        id: number
        title: string
        slug: string | null
        type: string
        status: string
        parent_id: number | null
        /** sections: the backend layout you already have (any shape; hydration is defensive) */
        sections: unknown[]
    }
}

const page = usePage<EditProps>()
const availableElements = page.props.availableElements
const initialPage = page.props.page
</script>

<template>
    <PageEditor mode="edit" :initial-page="initialPage" :available-elements="availableElements"
        update-route-name="pages.update" @saved="() => { }" />
</template>
