<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import type { MediaItem } from '@/types';
import { Button } from '@/components/ui/button';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area'

const props = defineProps<{
    items: MediaItem[];
}>();

const handleSetProfile = (media: MediaItem) => {
    router.post('/media/setAsProfile', { media_id: media.id }, {
        preserveScroll: true,
        onSuccess: () => {
            // Inertia rafraîchira automatiquement les props si le backend les renvoie.
            // Vous pouvez ajouter un toast/notification ici.
            console.log(`Profile photo updated: ${media.id}`);
        },
        onError: (errors: Record<string, string[]>) => {
            console.error('Failed to set profile photo:', errors);
            // Afficher les erreurs à l'utilisateur si nécessaire
        },
    });
};

const handleDelete = (media: MediaItem) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer cette image ? (${media.collection_name} #${media.id})`)) {
        router.delete(`/media/${media.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                console.log(`Media deleted: ${media.id}`);
            },
            onError: (errors: Record<string, string[]>) => {
                console.error('Failed to delete media:', errors);
            },
        });
    }
};
</script>

<template>
    <div v-if="!items || items.length === 0">
        <p class="p-4 text-sm text-muted-foreground">No media uploaded yet.</p>
    </div>
    <ScrollArea v-else class="w-full whitespace-nowrap rounded-md border">
        <div class="flex w-max space-x-4 p-4">
            <figure v-for="mediaItem in items" :key="mediaItem.id" class="shrink-0 w-[30vh]">
                <div class="overflow-hidden rounded-md">
                    <img :src="mediaItem.url" :alt="`${mediaItem.collection_name} #${mediaItem.id}`"
                        class="aspect-[3/4] h-auto w-full object-cover" loading="lazy" />
                </div>
                <figcaption class="pt-2 text-xs text-muted-foreground">
                    Collection :
                    <span class="font-semibold text-foreground">
                        {{
                            mediaItem.is_profile
                                ? 'Profile'
                                : mediaItem.collection_name === 'garde' // Exemple de logique de nom d'affichage
                                    ? 'Collection de Garde'
                                    : mediaItem.collection_name
                        }}
                    </span>
                    <span v-if="mediaItem.is_profile"
                        class="ml-1 px-1.5 py-0.5 text-xs bg-green-100 text-green-700 rounded-full">Actif</span>
                </figcaption>
                <figcaption v-if="!mediaItem.is_profile"
                    class="flex justify-between items-center space-x-2 w-full pt-2">
                    <Button @click="() => handleSetProfile(mediaItem)" size="sm" variant="outline">
                        Mettre en profile
                    </Button>
                    <Button variant="destructive" size="sm" @click="() => handleDelete(mediaItem)">
                        Supprimer
                    </Button>
                </figcaption>
            </figure>
        </div>
        <ScrollBar orientation="horizontal" />
    </ScrollArea>
</template>
