<script setup lang="ts">
import { ref, computed, onUnmounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import type { Preview } from '@/types'; // Import depuis votre fichier de types

// Suppose que vous avez des composants Vue pour l'IU. Sinon, remplacez par des éléments HTML.
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { XIcon, PlusIcon } from 'lucide-vue-next'; // Importez les icônes nécessaires

// Constantes
const MAX_PHOTOS = 5;
const MIN_WIDTH = 500;
const MIN_HEIGHT = 500;
const MAX_FILE_SIZE_MB = 5;
const MAX_FILE_SIZE_BYTES = MAX_FILE_SIZE_MB * 1024 * 1024;


// Références et état réactif
const fileInputRef = ref<HTMLInputElement | null>(null);
const mediaPreviews = ref<Preview[]>([]);
const clientSideErrors = ref<string[]>([]); // Pour les erreurs de validation côté client

const inertiaForm = useForm({
    collection_name: '',
    images: [] as File[],
});

// Validation des dimensions de l'image
const validateImageDimensions = (file: File): Promise<boolean> => {
    return new Promise((resolve) => {
        const img = new Image();
        img.onload = () => {
            const isValid = img.naturalWidth >= MIN_WIDTH && img.naturalHeight >= MIN_HEIGHT;
            URL.revokeObjectURL(img.src);
            resolve(isValid);
        };
        img.onerror = () => {
            URL.revokeObjectURL(img.src);
            resolve(false);
        };
        img.src = URL.createObjectURL(file);
    });
};

// Gestion du changement de fichier
const onFileChange = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const files = target.files;
    if (!files) return;

    const currentClientErrors: string[] = [];
    const newPreviewsToAdd: Preview[] = [];

    for (const file of Array.from(files)) {
        if (mediaPreviews.value.length + newPreviewsToAdd.length >= MAX_PHOTOS) {
            currentClientErrors.push(`You can only upload up to ${MAX_PHOTOS} photos.`);
            break;
        }
        if (!file.type.startsWith('image/')) {
            currentClientErrors.push(`File ${file.name} is not an image.`);
            continue;
        }
        if (file.size > MAX_FILE_SIZE_BYTES) {
            currentClientErrors.push(`File ${file.name} exceeds the maximum size of ${MAX_FILE_SIZE_MB}MB.`);
            continue;
        }

        const validDimensions = await validateImageDimensions(file);
        if (!validDimensions) {
            currentClientErrors.push(`Image ${file.name} dimensions must be at least ${MIN_WIDTH}x${MIN_HEIGHT}px.`);
            continue;
        }
        const url = URL.createObjectURL(file);
        newPreviewsToAdd.push({ file, preview: url });
    }

    clientSideErrors.value = currentClientErrors; // Afficher uniquement les dernières erreurs de validation du lot
    if (newPreviewsToAdd.length > 0) {
        mediaPreviews.value = [...mediaPreviews.value, ...newPreviewsToAdd];
    }

    if (fileInputRef.value) {
        fileInputRef.value.value = ''; // Permet de re-sélectionner le même fichier si besoin
    }
};

// Supprimer une photo de la prévisualisation
const removePhoto = (idx: number) => {
    const removed = mediaPreviews.value.splice(idx, 1);
    if (removed.length > 0) {
        URL.revokeObjectURL(removed[0].preview);
    }
};

// Réinitialiser tout le formulaire
const resetAllClientState = () => {
    mediaPreviews.value.forEach((p) => URL.revokeObjectURL(p.preview));
    mediaPreviews.value = [];
    clientSideErrors.value = [];
    inertiaForm.reset(); // Réinitialise collection_name et images dans inertiaForm
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

// Propriété calculée pour activer/désactiver le bouton d'upload
const canUpload = computed(() => {
    return mediaPreviews.value.length > 0 && inertiaForm.collection_name.trim() !== '' && !inertiaForm.processing;
});

// Logique d'upload
const upload = () => {
    if (!canUpload.value) return;
    clientSideErrors.value = []; // Clear client-side errors before submission

    // Mettre à jour inertiaForm.images avec les fichiers actuels
    inertiaForm.images = mediaPreviews.value.map(p => p.file);

    inertiaForm.post('/media', {
        preserveScroll: true,
        onSuccess: () => {
            resetAllClientState(); // Nettoie les prévisualisations et le formulaire Inertia
        },
        onError: (errors: Record<string, string[]>) => { // `errors` est déjà géré par inertiaForm.errors
            // Vous pouvez ajouter une logique supplémentaire si nécessaire,
            // par exemple, si certaines erreurs ne sont pas liées à des champs spécifiques.
            console.error("Upload failed with errors:", errors);
        },
    });
};

// Nettoyage des Object URLs lors de la destruction du composant
onUnmounted(() => {
    mediaPreviews.value.forEach((p) => URL.revokeObjectURL(p.preview));
});

</script>

<template>
    <form @submit.prevent="upload" enctype="multipart/form-data" class="space-y-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-neutral-200">
            Upload Media
        </h2>
        <div class="grid w-full max-w-sm items-center gap-1.5">
            <Label for="collection_name">Collection name</Label>
            <Input id="collection_name" name="collection_name" type="text" v-model="inertiaForm.collection_name"
                placeholder="Enter collection name" :disabled="inertiaForm.processing" />
            <span v-if="inertiaForm.errors.collection_name" class="text-sm text-red-500 mt-1">{{
                inertiaForm.errors.collection_name }}</span>
        </div>

        <div>
            <Label class="block mb-2 text-sm font-medium text-gray-800 dark:text-neutral-200">
                Add photos (max {{ MAX_PHOTOS }}, {{ MIN_WIDTH }}x{{ MIN_HEIGHT }}px min, {{ MAX_FILE_SIZE_MB }}MB max
                per image)
            </Label>
            <div class="flex flex-wrap gap-2">
                <label for="media-file-input"
                    :class="['flex shrink-0 justify-center items-center w-32 h-32 border-2 border-dotted border-gray-300 rounded-xl text-gray-400 cursor-pointer hover:bg-gray-50 dark:border-neutral-700 dark:text-neutral-600 dark:hover:bg-neutral-700/20',
                        { 'opacity-50 cursor-not-allowed': mediaPreviews.length >= MAX_PHOTOS || inertiaForm.processing }]">
                    <input ref="fileInputRef" id="media-file-input" type="file" accept="image/*" multiple class="hidden"
                        @change="onFileChange"
                        :disabled="mediaPreviews.length >= MAX_PHOTOS || inertiaForm.processing" />
                    <PlusIcon class="w-6 h-6" />
                </label>

                <div v-for="(mp, idx) in mediaPreviews" :key="mp.preview"
                    class="relative w-32 h-32 rounded-xl overflow-hidden border border-gray-300 dark:border-neutral-700">
                    <img :src="mp.preview" class="object-cover w-full h-full" :alt="`Preview ${idx + 1}`" />
                    <button type="button" @click="() => removePhoto(idx)" :disabled="inertiaForm.processing"
                        class="absolute top-1 right-1 p-1 bg-white rounded-full shadow-md hover:bg-gray-100 dark:bg-neutral-800 dark:hover:bg-neutral-700"
                        aria-label="Remove photo">
                        <XIcon class="w-4 h-4 text-gray-500 dark:text-neutral-400" />
                    </button>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-neutral-500">
                Shoppers find images more helpful than text alone.
            </p>
        </div>

        <div v-if="clientSideErrors.length > 0" class="text-red-500 text-sm space-y-1">
            <div v-for="(error, i) in clientSideErrors" :key="`client-err-${i}`">{{ error }}</div>
        </div>

        <div v-if="inertiaForm.hasErrors" class="text-red-500 text-sm space-y-1 mt-2">
            <div v-if="inertiaForm.errors.images && typeof inertiaForm.errors.images === 'string'">{{
                inertiaForm.errors.images }}</div>
            <template v-else-if="inertiaForm.errors.images && typeof inertiaForm.errors.images === 'object'">
                <div v-for="(imgError, imgKey) in (inertiaForm.errors.images as Record<string, string>)"
                    :key="`server-img-err-${imgKey}`">
                    {{ imgError }}
                </div>
            </template>
            <div v-for="(error, key) in inertiaForm.errors" :key="`server-err-${key}`">
                <template v-if="key !== 'collection_name' && key !== 'images'">{{ error }}</template>
            </div>
        </div>


        <div class="flex mt-6 space-x-3">
            <Button type="submit" :disabled="!canUpload" :class="{ 'opacity-50 cursor-not-allowed': !canUpload }">
                <span v-if="inertiaForm.processing">Uploading...</span>
                <span v-else>Upload</span>
            </Button>
            <Button type="button" variant="outline" @click="resetAllClientState" :disabled="inertiaForm.processing">
                Reset
            </Button>
        </div>
    </form>
</template>
