import { h } from 'vue'
import DropdownAction from '@/components/DataTable/data-table-dropdown.vue'
import { ColumnDef } from '@tanstack/vue-table'
import { Posts } from '@/types'
import DataTableColumnHeader from '@/components/DataTable/columnHeader.vue'

export const columnsPost: ColumnDef<Posts>[] = [
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
        accessorKey: 'title',
        header: ({ column }) => (
            h(DataTableColumnHeader, {
                column: column,
                title: 'Title'
            })
        ),
    }, {
        accessorKey: 'type',
        header: ({ column }) => (
            h(DataTableColumnHeader, {
                column: column,
                title: 'Type'
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
        accessorKey: 'status',
        header: ({ column }) => (
            h(DataTableColumnHeader, {
                column: column,
                title: 'Status'
            })
        ),
    },
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }) => {
            const post = row.original

            return h('div', { class: 'relative' }, h(DropdownAction, {
                post,
            }))
        },
    },
]
