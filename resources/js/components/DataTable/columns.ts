import { h } from 'vue'
import DropdownAction from '@/components/DataTable/data-table-dropdown.vue'
import { ColumnDef } from '@tanstack/vue-table'
import { Pages } from '@/types'
import DataTableColumnHeader from '@/components/DataTable/columnHeader.vue'

export const columns: ColumnDef<Pages>[] = [
    {
        accessorKey: 'id',
        header: ({ column }) => (
            h(DataTableColumnHeader, {
                column: column,
                title: '#'
            })
        ),
    },
    {
        accessorKey: 'type',
        header: ({ column }) => (
            h(DataTableColumnHeader, {
                column: column,
                title: 'Type'
            })
        ),
    },
    {
        accessorKey: 'status',
        header: ({ column }) => (
            h(DataTableColumnHeader, {
                column: column,
                title: 'Status'
            })
        ),
    },
    {
        accessorKey: 'slug',
        header: ({ column }) => (
            h(DataTableColumnHeader, {
                column: column,
                title: 'Slug'
            })
        ),
    },
    {
        accessorKey: 'created_at_human',
        header: ({ column }) => h(DataTableColumnHeader, { column, title: 'Creation Date' }),
    },
    {
        accessorKey: 'updated_at_human',
        header: ({ column }) => h(DataTableColumnHeader, { column, title: 'Updated Date' }),
    },
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }) => {
            const page = row.original

            return h('div', { class: 'relative' }, h(DropdownAction, {
                item: page,
                type: 'page',
            }))
        },
    },
]
