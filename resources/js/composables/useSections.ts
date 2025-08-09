// src/composables/useSections.ts
import { ref, reactive, computed } from 'vue'
import { v4 as uuidv4 } from 'uuid'
import { Section, Block } from '@/types'

// Le formulaire de la section
export const sectionForm = reactive({
    title: '',
    type: '1 column' as Section['type'],
})

// Etat de la section et de la modale
export const isSectionDialogVisible = ref(false)
export const editingSectionId = ref<string | null>(null)

// Formulaire et état des sections
export const form = reactive({
    title: '',
    slug: '',
    type: 'page',
    status: 'draft',
    parent_id: undefined as number | undefined,
    sections: [] as Section[],
})

export function openCreateSectionDialog() {
    editingSectionId.value = null // Pas d'ID, donc c'est une création
    sectionForm.title = ''
    sectionForm.type = '1 column'
    isSectionDialogVisible.value = true
}

export function openEditSectionDialog(section: Section) {
    editingSectionId.value = section.id // On stocke l'ID de la section à éditer
    sectionForm.title = section.title
    sectionForm.type = section.type
    isSectionDialogVisible.value = true
}

export function handleSaveSection() {
    const isEditing = !!editingSectionId.value;

    if (isEditing) {
        const section = form.sections.find(s => s.id === editingSectionId.value)
        if (section) {
            const oldColumnCount = section.columns.length
            const newColumnCount = parseInt(sectionForm.type.split(' ')[0], 10)

            section.title = sectionForm.title
            section.type = sectionForm.type

            // Logique pour ajuster le nombre de colonnes
            if (newColumnCount > oldColumnCount) {
                for (let i = 0; i < newColumnCount - oldColumnCount; i++) {
                    section.columns.push({ id: uuidv4(), blocks: [] })
                }
            } else if (newColumnCount < oldColumnCount) {
                // Pour ne pas perdre de données, on déplace les blocs des colonnes supprimées vers la première
                const columnsToKeep = section.columns.slice(0, newColumnCount)
                const columnsToRemove = section.columns.slice(newColumnCount)
                const blocksToMove = columnsToRemove.flatMap(col => col.blocks)
                columnsToKeep[0].blocks.push(...blocksToMove)
                section.columns = columnsToKeep
            }
        }
    } else {
        // Logique de création (inchangée)
        const columnCount = parseInt(sectionForm.type.split(' ')[0], 10)
        const newSection: Section = {
            id: uuidv4(),
            title: sectionForm.title,
            type: sectionForm.type,
            columns: Array.from({ length: columnCount }, () => ({ id: uuidv4(), blocks: [] })), // Crée les colonnes vides
        }
        form.sections.push(newSection)
    }

    isSectionDialogVisible.value = false;
}

export function removeSection(id: string) {
    form.sections = form.sections.filter(s => s.id !== id)
}

export function openAddBlockDialog(sectionId: string, columnId: string) {
    // Cette logique est nécessaire pour ouvrir le dialogue pour ajouter un bloc à la colonne
    // Ce sera géré dans le composant principal
}
