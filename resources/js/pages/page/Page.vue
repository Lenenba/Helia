<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { defineAsyncComponent } from 'vue';

const props = defineProps<{
    page: {
        id: number;
        title: string;
        sections: Array<{
            id: number;
            db_type: string;
            settings: {
                layout?: {
                    columns: Array<{ blocks: any[] }>;
                };
            };
            blocks: Array<{
                id: number;
                column_index: number;
                blockable_type: string;
                blockable_id: number;
                template_hint: string;
                blockable: any;
            }>;
        }>;
    };
}>();

// Dynamic component loader
function loadBlockComponent(templateHint: string) {
    // Dynamically import the block component based on the template hint.
    // For example, 'hero' would load 'HeroBlock.vue'.
    return defineAsyncComponent(() => import(`../Components/Blocks/${templateHint}.vue`));
}

// A generic "empty" component to use as a fallback if a block component isn't found.
const EmptyComponent = { template: '<div>Component not found for: {{ templateHint }}</div>' };
</script>

<template>

    <Head :title="page.title" />

    <AppLayout>
        <template v-for="section in page.sections" :key="section.id">
            <component :is="section.db_type">
                <template v-if="section.settings?.layout?.columns.length">
                    <div v-for="col in section.settings.layout.columns" :key="col.index" :class="`column-${col.index}`">
                        <template v-for="block in section.blocks.filter(b => b.column_index === col.index)"
                            :key="block.id">
                            <component :is="loadBlockComponent(block.template_hint)" v-if="block.template_hint"
                                :data="block.blockable" />
                            <component v-else :is="EmptyComponent" :template-hint="block.template_hint" />
                        </template>
                    </div>
                </template>
                <div v-else>
                    <template v-for="block in section.blocks" :key="block.id">
                        <component :is="loadBlockComponent(block.template_hint)" v-if="block.template_hint"
                            :data="block.blockable" />
                        <component v-else :is="EmptyComponent" :template-hint="block.template_hint" />
                    </template>
                </div>
            </component>
        </template>
    </AppLayout>
</template>
