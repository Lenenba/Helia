import { h } from 'vue'
import DropdownAction from '@/components/Reservation/data-table-dropdown.vue'
import { ColumnDef } from '@tanstack/vue-table'
import { Reservation } from '@/types'
import { Checkbox } from '@/components/ui/checkbox'
import DataTableColumnHeader from '@/components/columnHeader.vue'
import { formatDistanceToNow } from 'date-fns';
import { fr } from 'date-fns/locale';

export const columns: ColumnDef<Reservation>[] = [
    {
        id: 'select',
        header: ({ table }) => h(Checkbox, {
            'modelValue': table.getIsAllPageRowsSelected(),
            'onUpdate:modelValue': (value: boolean) => table.toggleAllPageRowsSelected(!!value),
            'ariaLabel': 'Select all',
        }),
        cell: ({ row }) => h(Checkbox, {
            'modelValue': row.getIsSelected(),
            'onUpdate:modelValue': (value: boolean) => row.toggleSelected(!!value),
            'ariaLabel': 'Select row',
        }),
        enableSorting: false,
        enableHiding: false,
    },
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
        accessorKey: 'notes',
        header: ({ column }) => (
            h(DataTableColumnHeader, {
                column: column,
                title: 'Notes'
            })
        ),
    },
    {
        accessorKey: 'created_at',
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Cree le' }),
    },
    {
        accessorKey: 'status',
        header: ({ column }) =>
            h(DataTableColumnHeader, {
                column,
                title: 'Status',
            }),
        cell: ({ row }) => {
            // English comment: Map each status to its display text and CSS classes
            const status = row.getValue<string>('status');
            const statusMap: Record<string, { text: string; classes: string }> = {
                pending: {
                    text: 'Pending',
                    classes: 'bg-yellow-100 text-yellow-800',
                },
                confirmed: {
                    text: 'Confirmed',
                    classes: 'bg-green-100 text-green-800',
                },
                cancelled: {
                    text: 'Cancelled',
                    classes: 'bg-red-100 text-red-800',
                },
                // add other statuses as needed
            };

            // English comment: Fallback for unknown statuses
            const { text, classes } =
                statusMap[status] ?? { text: status, classes: 'bg-gray-100 text-gray-800' };

            // English comment: Render a pill-shaped badge
            return h(
                'span',
                {
                    class: `inline-block px-2 py-1 rounded-xs text-sm font-semibold ${classes}`,
                },
                text
            );
        },
    },
    {
        accessorKey: 'total_amount',
        header: () => h('div', { class: 'text-right' }, 'Montant total'),
        cell: ({ row }) => {
            const amount = Number.parseFloat(row.getValue('total_amount'))
            const formatted = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
            }).format(amount)

            return h('div', { class: 'text-right font-medium' }, formatted)
        },
    },
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }) => {
            const reservation = row.original

            return h('div', { class: 'relative' }, h(DropdownAction, {
                reservation,
            }))
        },
      },
]
