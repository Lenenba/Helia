import { h } from 'vue'
import DropdownAction from '@/components/DataTable/data-table-dropdown.vue'
import { ColumnDef } from '@tanstack/vue-table'
import { Pages } from '@/types'
import DataTableColumnHeader from '@/components/DataTable/columnHeader.vue'

export const columns: ColumnDef<Pages>[] = [
    {
        accessorKey: 'ref',
        header: ({ column }) => (
            h(DataTableColumnHeader, {
                column: column,
                title: 'Ref'
            })
        ),
    },
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }) => {
            const page = row.original

            return h('div', { class: 'relative' }, h(DropdownAction, {
                page,
            }))
        },
    },
]
