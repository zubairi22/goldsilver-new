<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import Heading from '@/components/Heading.vue'
import SearchInput from '@/components/SearchInput.vue'
import PageNav from '@/components/PageNav.vue'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import VueDatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import { ref, watch } from 'vue'
import { useSearch } from '@/composables/useSearch'
import { useFormat } from '@/composables/useFormat'
import type { BreadcrumbItem } from '@/types'
import { Button } from '@/components/ui/button'
import * as XLSX from 'xlsx'

const {
    sales,
    filters,
    totalWeight,
    totalAmount,
} = defineProps<{
    sales: any
    filters: any
    totalWeight: number
    totalAmount: number
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Laporan Penjualan', href: '#' },
]

const { search } = useSearch(
    'reports.sales.note',
    filters.search,
    ['sales']
)

const { formatRupiah } = useFormat()

const sale_type = ref(filters.sale_type ?? 'all')
const category = ref(filters.category ?? 'all')

const date = ref(
    filters.start && filters.end
        ? [filters.start, filters.end]
        : []
)

const applyFilters = () => {
    const params: Record<string, any> = {}

    if (sale_type.value !== 'all') params.sale_type = sale_type.value
    if (category.value !== 'all') params.category = category.value

    if (date.value?.[0] && date.value?.[1]) {
        params.start = date.value[0]
        params.end = date.value[1]
    }

    if (search.value) params.search = search.value

    router.get(route('reports.sales.note'), params, {
        preserveScroll: true,
        preserveState: true,
    })
}

watch([sale_type, category, date], applyFilters)

/* ===============================
   EXPORT EXCEL (XLSX)
   =============================== */
const exportExcel = () => {
    const rows = sales.data.map((row: any) => ({
        Nota: row.invoice,
        Tanggal: row.date,
        Jenis: row.sale_type,
        Kategori: row.category,
        'Total Berat (gr)': row.total_weight,
        Nominal: row.total_price,
    }))

    rows.push({})
    rows.push({
        Nota: 'TOTAL',
        'Total Berat (gr)': totalWeight,
        Nominal: totalAmount,
    })

    const worksheet = XLSX.utils.json_to_sheet(rows)
    const workbook = XLSX.utils.book_new()

    XLSX.utils.book_append_sheet(workbook, worksheet, 'Laporan Nota')

    const filename = `laporan-penjualan-nota-${filters.start}-sd-${filters.end}.xlsx`
    XLSX.writeFile(workbook, filename)
}
</script>

<template>
    <Head title="Laporan Penjualan Per Nota" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">

            <!-- HEADER + TOTAL -->
            <div class="mx-4 space-y-4 md:flex items-center md:justify-between md:space-y-0">
                <Heading
                    class="md:mr-6 flex-1"
                    title="Laporan Penjualan Per Nota"
                    description="Rekap penjualan berdasarkan nota"
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full md:w-96">
                    <Card class="py-4 gap-1 border-yellow-200 bg-yellow-50">
                        <CardHeader>
                            <CardTitle class="text-sm font-medium text-yellow-700">
                                Total Berat
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold text-yellow-800">
                                {{ totalWeight.toFixed(2) }} gr
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="py-4 gap-1 border-emerald-200 bg-emerald-50">
                        <CardHeader>
                            <CardTitle class="text-sm font-medium text-emerald-700">
                                Total Penjualan
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold text-emerald-800">
                                {{ formatRupiah(totalAmount) }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- TABLE -->
            <div class="max-w-8xl mx-auto mt-6">
                <Card class="py-4 md:mx-4">
                    <CardContent>

                        <!-- FILTER -->
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                            <div class="flex flex-wrap items-center gap-4">
                                <div class="w-40">
                                    <Select v-model="sale_type">
                                        <SelectTrigger><SelectValue placeholder="Jenis" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua</SelectItem>
                                            <SelectItem value="retail">Eceran</SelectItem>
                                            <SelectItem value="wholesale">Grosir</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="w-40">
                                    <Select v-model="category">
                                        <SelectTrigger><SelectValue placeholder="Kategori" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua</SelectItem>
                                            <SelectItem value="gold">Emas</SelectItem>
                                            <SelectItem value="silver">Perak</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div class="w-full sm:w-auto lg:w-80">
                                <VueDatePicker
                                    v-model="date"
                                    range
                                    :enable-time-picker="false"
                                    model-type="yyyy-MM-dd"
                                    locale="id"
                                />
                            </div>
                        </div>

                        <!-- SEARCH + EXPORT -->
                        <div class="flex flex-wrap items-center justify-between mb-3 gap-3">
                            <div class="w-full lg:w-80">
                                <SearchInput v-model:search="search" />
                            </div>

                            <Button variant="secondary" @click="exportExcel">
                                Export Excel
                            </Button>
                        </div>

                        <!-- TABLE -->
                        <div class="overflow-x-auto max-h-[70vh] overflow-y-auto">
                            <Table>
                                <TableHeader class="sticky top-0 bg-background z-10">
                                    <TableRow>
                                        <TableHead>Nota</TableHead>
                                        <TableHead>Tanggal</TableHead>
                                        <TableHead>Penjualan</TableHead>
                                        <TableHead>Kategori</TableHead>
                                        <TableHead class="text-right">Total Berat (gr)</TableHead>
                                        <TableHead class="text-right">Nominal</TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow
                                        v-for="row in sales.data"
                                        :key="row.invoice"
                                    >
                                        <TableCell class="font-medium">{{ row.invoice }}</TableCell>
                                        <TableCell>{{ row.date }}</TableCell>
                                        <TableCell>{{ row.sale_type }}</TableCell>
                                        <TableCell>{{ row.category }}</TableCell>
                                        <TableCell class="text-right">{{ row.total_weight }}</TableCell>
                                        <TableCell class="text-right">
                                            {{ formatRupiah(row.total_price) }}
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="!sales.total">
                                        <TableCell colspan="6" class="text-center py-4">
                                            Tidak ada data penjualan.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <PageNav :data="sales" />
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
