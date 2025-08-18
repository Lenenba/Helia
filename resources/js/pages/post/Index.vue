<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Button } from '@/components/ui/button';
import { Head, usePage, Link } from '@inertiajs/vue3';
import { DollarSign, User, CreditCard, Activity } from 'lucide-vue-next';
import { computed } from 'vue';
import DataTable from '@/components/DataTable/data-table.vue';
import { columnsPost } from '@/components/DataTable/columnsPost';
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Post list',
        href: '/post',
    },
];
// Shared page props
const page = usePage<SharedData>();

// Define stats cards data
const stats = computed<any>(() => {
    return page.props.stats;
});

const posts = computed<any>(() => {
    return page.props.posts;
});



</script>

<template>

    <Head title="page" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Stats cards container -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div v-for="(stat, index) in stats" :key="index"
                    class="bg-white border border-gray-200 rounded-sm p-4 shadow-xs">
                    <div class="flex justify-between items-center">
                        <h2 class="text-sm font-medium text-gray-500">{{ stat.title }}</h2>
                        <component :is="stat.icon" class="w-5 h-5 text-gray-400" />
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl font-semibold text-gray-900">{{ stat.value }}</p>
                        <p class="mt-1 text-sm">
                            <span class="text-green-500">{{ stat.change }}</span>
                            <span class="text-gray-400"> {{ stat.changeText }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="border border-gray-200 rounded-sm shadow-xs">
                <div class="mx-5 my-10">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col gap-2">
                            <h1 class="text-2xl font-bold text-gray-900">Mes posts</h1>

                        </div>
                        <div class="flex items-center justify-end">
                            <Link href="/posts/create">
                            <Button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                                Create new post
                            </Button>
                            </Link>
                        </div>
                    </div>

                    <DataTable :columns="columnsPost" :data="posts" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
