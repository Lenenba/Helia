<script setup lang="ts" generic="TData extends Record<string, any>">
// Production-ready reusable DataTable for shadcn-vue + @tanstack/vue-table v8
// Features: sorting, filtering, column visibility, row selection, pagination
// Works in client or server mode (manualSorting/manualPagination/manualFiltering)
// Props are intentionally small; the rest is driven by TanStack column defs.

import { computed, ref, watch, toRef } from 'vue'
import type { ColumnDef, ColumnFiltersState, SortingState, VisibilityState, RowSelectionState } from '@tanstack/vue-table'
import { FlexRender, getCoreRowModel, getSortedRowModel, getPaginationRowModel, getFilteredRowModel, useVueTable } from '@tanstack/vue-table'

// shadcn-vue
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, Settings2 } from 'lucide-vue-next'

const props = withDefaults(defineProps<{
    /** Your TanStack columns */
    columns: ColumnDef<TData, any>[]
    /** Raw items (client mode) */
    data?: TData[]
    /** Bindable/controlled states (optional) */
    sorting?: SortingState
    columnFilters?: ColumnFiltersState
    columnVisibility?: VisibilityState
    rowSelection?: RowSelectionState
    /** Server mode toggles */
    manualSorting?: boolean
    manualPagination?: boolean
    manualFiltering?: boolean
    /** Pagination (server or client) */
    pageSize?: number
    pageIndex?: number
    /** Global filter field key to bind the search input (defaults to first string accessor) */
    globalFilterKey?: string
}>(), {
    data: () => [],
    manualSorting: false,
    manualPagination: false,
    manualFiltering: false,
    pageSize: 10,
    pageIndex: 0,
})

const emit = defineEmits<{
    /** Server mode hooks */
    (e: 'update:sorting', v: SortingState): void
    (e: 'update:columnFilters', v: ColumnFiltersState): void
    (e: 'update:columnVisibility', v: VisibilityState): void
    (e: 'update:rowSelection', v: RowSelectionState): void
    (e: 'update:pageIndex', v: number): void
    (e: 'update:pageSize', v: number): void
}>()

// Local reactive state with controlled fallbacks
const _sorting = ref<SortingState>(props.sorting ?? [])
const _filters = ref<ColumnFiltersState>(props.columnFilters ?? [])
const _visibility = ref<VisibilityState>(props.columnVisibility ?? {})
const _rowSelection = ref<RowSelectionState>(props.rowSelection ?? {})
const _pageSize = ref<number>(props.pageSize)
const _pageIndex = ref<number>(props.pageIndex)

watch(() => props.sorting, (v) => v && (_sorting.value = v))
watch(() => props.columnFilters, (v) => v && (_filters.value = v))
watch(() => props.columnVisibility, (v) => v && (_visibility.value = v))
watch(() => props.rowSelection, (v) => v && (_rowSelection.value = v))
watch(() => props.pageSize, (v) => v != null && (_pageSize.value = v))
watch(() => props.pageIndex, (v) => v != null && (_pageIndex.value = v))

const table = useVueTable<TData>({
    get data() {
        return props.data ?? []
    },
    get columns() {
        return props.columns
    },
    manualSorting: props.manualSorting,
    manualPagination: props.manualPagination,
    manualFiltering: props.manualFiltering,
    state: {
        get sorting() { return _sorting.value },
        get columnFilters() { return _filters.value },
        get columnVisibility() { return _visibility.value },
        get rowSelection() { return _rowSelection.value },
        get pagination() { return { pageIndex: _pageIndex.value, pageSize: _pageSize.value } },
    },
    onSortingChange: (updater) => {
        const next = typeof updater === 'function' ? updater(_sorting.value) : updater
        _sorting.value = next
        emit('update:sorting', next)
    },
    onColumnFiltersChange: (updater) => {
        const next = typeof updater === 'function' ? updater(_filters.value) : updater
        _filters.value = next
        emit('update:columnFilters', next)
    },
    onColumnVisibilityChange: (updater) => {
        const next = typeof updater === 'function' ? updater(_visibility.value) : updater
        _visibility.value = next
        emit('update:columnVisibility', next)
    },
    onRowSelectionChange: (updater) => {
        const next = typeof updater === 'function' ? updater(_rowSelection.value) : updater
        _rowSelection.value = next
        emit('update:rowSelection', next)
    },
    onPaginationChange: (updater) => {
        const p = { pageIndex: _pageIndex.value, pageSize: _pageSize.value }
        const next = typeof updater === 'function' ? updater(p) : updater
        _pageIndex.value = next.pageIndex
        _pageSize.value = next.pageSize
        emit('update:pageIndex', next.pageIndex)
        emit('update:pageSize', next.pageSize)
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
})

// Toolbar helpers
const globalKey = computed(() => {
    if (props.globalFilterKey) return props.globalFilterKey
    // try to find a primitive accessor as default
    const firstAccessor = props.columns.find(c => typeof (c as any).accessorKey === 'string') as any
    return firstAccessor?.accessorKey as string | undefined
})

const globalFilterValue = computed({
    get() {
        return globalKey.value ? (table.getColumn(globalKey.value)?.getFilterValue() as string) : ''
    },
    set(v: string) {
        globalKey.value && table.getColumn(globalKey.value)?.setFilterValue(v)
    }
})

</script>

<template>
    <div class="w-full space-y-4">
        <!-- Toolbar -->
        <div class="flex flex-wrap items-center gap-3">
            <Input v-if="globalKey" v-model="globalFilterValue" class="max-w-xs"
                :placeholder="`Filtrer par ${globalKey}`" />

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" size="sm" class="ml-auto">
                        <Settings2 class="mr-2 h-4 w-4" /> Colonnes
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuCheckboxItem v-for="column in table.getAllLeafColumns()" :key="column.id"
                        :checked="column.getIsVisible()" :disabled="column.getCanHide() === false"
                        @update:checked="(v: boolean) => column.toggleVisibility(!!v)">
                        {{ column.columnDef.header && typeof column.columnDef.header === 'string' ?
                            column.columnDef.header : column.id }}
                    </DropdownMenuCheckboxItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>

        <!-- Table -->
        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow v-for="hg in table.getHeaderGroups()" :key="hg.id">
                        <TableHead v-for="header in hg.headers" :key="header.id">
                            <div v-if="!header.isPlaceholder">
                                <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
                            </div>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="table.getRowModel().rows?.length">
                        <TableRow v-for="row in table.getRowModel().rows" :key="row.id"
                            :data-state="row.getIsSelected() ? 'selected' : undefined">
                            <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                            </TableCell>
                        </TableRow>
                    </template>
                    <template v-else>
                        <TableRow>
                            <TableCell :colspan="table.getAllLeafColumns().length" class="h-24 text-center">
                                Aucun résultat.
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between gap-2">
            <div class="text-sm text-muted-foreground">
                Page {{ table.getState().pagination.pageIndex + 1 }} / {{ table.getPageCount() || 1 }} · {{
                    table.getFilteredRowModel().rows.length }} ligne(s)
            </div>
            <div class="flex items-center gap-1">
                <Button variant="outline" size="icon" :disabled="!table.getCanPreviousPage()"
                    @click="table.setPageIndex(0)">
                    <ChevronsLeft class="h-4 w-4" />
                </Button>
                <Button variant="outline" size="icon" :disabled="!table.getCanPreviousPage()"
                    @click="table.previousPage()">
                    <ChevronLeft class="h-4 w-4" />
                </Button>
                <Button variant="outline" size="icon" :disabled="!table.getCanNextPage()" @click="table.nextPage()">
                    <ChevronRight class="h-4 w-4" />
                </Button>
                <Button variant="outline" size="icon" :disabled="!table.getCanNextPage()"
                    @click="table.setPageIndex(table.getPageCount() - 1)">
                    <ChevronsRight class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
