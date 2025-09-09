<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { MoreHorizontal } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'

// Les props sont maintenant génériques
defineProps<{
    item: {
        id: string
        // ...autres propriétés possibles
    },
    type: string // ex: 'post', 'page', 'product'
}>()

/**
 * La fonction 'action' est déjà générique, donc aucune modification n'est nécessaire ici.
 * Elle utilise déjà les variables 'id' et 'type' passées en paramètres.
 */
function action(id: string, type: string, event: string) {
    console.log(`Action triggered: ${event} on ${type} with ID ${id}`);
    const eventType = event.toLowerCase();

    if (eventType === 'publish') {
        router.patch(`/admin/publish/${type}/${id}`, {}, {
            preserveScroll: true
        });
    }
    else if (eventType === 'unpublish') {
        router.patch(`/admin/unpublish/${type}/${id}`, {}, {
            preserveScroll: true
        });
    }
    else if (eventType === 'archive') {
        router.delete(`/admin/archive/${type}/${id}`, {
            preserveScroll: true
        });
    } else if (eventType === 'edit') {
        if (type === 'post') {
            router.get(`/posts/${id}/edit`, {
                preserveScroll: true
            });
        } else if (type === 'page') {
            router.get(`/pages/${id}/edit`, {
                preserveScroll: true
            });
        }
    }
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" class="w-8 h-8 p-0">
                <span class="sr-only">Open menu</span>
                <MoreHorizontal class="w-4 h-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuLabel>Actions</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem @click="action(item.id, type, 'edit')">
                Edit
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem @click="action(item.id, type, 'publish')">
                Publish
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem @click="action(item.id, type, 'unpublish')">
                Unpublish
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem @click="action(item.id, type, 'archive')">
                Archive
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
