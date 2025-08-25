<script setup lang="ts">
import PostForm from './Form.vue';
import type { Post, MediaItem, TagItem } from '@/types'; // Assurez-vous d'avoir un type `Post`

defineProps<{
    post: Post;
    media?: MediaItem[];
    tags?: TagItem[];
}>();
</script>

<template>
    <PostForm :post="post" :media="media" :tags="tags" />
</template>
