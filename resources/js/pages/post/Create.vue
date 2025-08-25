<script setup lang="ts">
import PostForm from './Form.vue';
import type { MediaItem, TagItem } from '@/types';

defineProps<{
    media?: MediaItem[];
    tags?: TagItem[];
}>();
</script>

<template>
    <PostForm :media="media" :tags="tags" />
</template>
