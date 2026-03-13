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
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

const {
    sales,
    filters,
    employees,
    totalWeight,
    totalAmount,
    totalInvoice,
} = defineProps<{
    sales: any
    filters: any
    employees: any[]
    totalWeight: number
    totalAmount: number
    totalInvoice: number
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Laporan Penjualan', href: '#' },
]

const { search } = useSearch(
    'reports.sales.employee',
    filters.search,
    ['sales']
)

const { formatRupiah } = useFormat()

const user_id = ref(filters.user_id ?? 'all')
const category = ref(filters.category ?? 'all')

const date = ref(
    filters.start && filters.end
        ? [filters.start, filters.end]
        : []
)

const applyFilters = () => {
    const params: Record<string, any> = {}

    if (user_id.value !== 'all') params.user_id = user_id.value
    if (category.value !== 'all') params.category = category.value

    if (date.value?.[0] && date.value?.[1]) {
        params.start = date.value[0]
        params.end = date.value[1]
    }

    if (search.value) params.search = search.value

    router.get(route('reports.sales.employee'), params, {
        preserveScroll: true,
        preserveState: true,
    })
}

watch([user_id, category, date], applyFilters)

const exportExcel = () => {
    const rows = sales.map((row: any, index: number) => ({
        No: index + 1,
        'Nomor Nota': row.invoice,
        'Tanggal Penjualan': row.date,
        'Nama Karyawan': row.employee,
        Kategori: row.category,
        'Total Berat': row.total_weight,
        Nominal: row.total_price,
    }))

    const worksheet = XLSX.utils.json_to_sheet(rows)
    XLSX.utils.sheet_add_aoa(worksheet, [
        ['TOTAL', '', '', '', '', totalWeight, totalAmount]
    ], { origin: -1 })

    const workbook = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Laporan Karyawan')

    const filename = `laporan-penjualan-karyawan-${filters.start}-sd-${filters.end}.xlsx`
    XLSX.writeFile(workbook, filename)
}

const exportPdf = (action: 'download' | 'stream') => {
    const doc = new jsPDF()
    
    doc.text('Laporan Penjualan Per Karyawan', 14, 15)
    
    const tableData = sales.map((row: any, index: number) => [
        index + 1,
        row.invoice,
        row.date,
        row.employee,
        row.category,
        row.total_weight,
        formatRupiah(row.total_price)
    ])

    autoTable(doc, {
        startY: 20,
        head: [['No', 'Nomor Nota', 'Tanggal', 'Karyawan', 'Kategori', 'Berat', 'Nominal']],
        body: tableData,
        foot: [['', 'TOTAL', '', '', '', totalWeight.toFixed(2), formatRupiah(totalAmount)]],
        showFoot: 'lastPage',
        theme: 'striped',
        headStyles: { fillColor: [30, 41, 59] },
        footStyles: { fillColor: [241, 245, 249], textColor: [0, 0, 0], fontStyle: 'bold' },
        styles: { fontSize: 8 }
    })

    if (action === 'download') {
        doc.save(`laporan-karyawan-${filters.start}-sd-${filters.end}.pdf`)
    } else {
        window.open(doc.output('bloburl'), '_blank')
    }
}
</script>

<template>
    <Head title="Laporan Penjualan Karyawan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">

            <div class="mx-4 space-y-4 md:flex items-center md:justify-between md:space-y-0">
                <Heading
                    class="md:mr-6 flex-1"
                    title="Laporan Penjualan Karyawan"
                    description="Rekap penjualan per nota berdasarkan karyawan"
                />

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full md:w-[36rem]">
                    <Card class="py-4 gap-1 border-blue-200 bg-blue-50">
                        <CardHeader>
                            <CardTitle class="text-sm font-medium text-blue-700">
                                Total Nota
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold text-blue-700">
                                {{ totalInvoice }}
                            </p>
                        </CardContent>
                    </Card>

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

            <div class="max-w-8xl mx-auto mt-6">
                <Card class="py-4 md:mx-4">
                    <CardContent>

                        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                            <div class="flex flex-wrap items-center gap-4">
                                <div class="w-48">
                                    <Select v-model="user_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Karyawan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua</SelectItem>
                                            <SelectItem
                                                v-for="emp in employees"
                                                :key="emp.id"
                                                :value="emp.id"
                                            >
                                                {{ emp.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="w-40">
                                    <Select v-model="category">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Kategori" />
                                        </SelectTrigger>
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

                        <div class="flex flex-wrap items-center justify-between mb-3 gap-3">
                            <div class="w-full lg:w-80">
                                <SearchInput v-model:search="search" />
                            </div>

                            <div class="flex gap-2">
                                <Button variant="secondary" @click="exportExcel">
                                    Excel
                                </Button>
                                <Button variant="secondary" @click="exportPdf('stream')">
                                    Print
                                </Button>
                                <Button variant="secondary" @click="exportPdf('download')">
                                    PDF
                                </Button>
                            </div>
                        </div>

                        <div class="overflow-x-auto max-h-[70vh] overflow-y-auto">
                            <Table>
                                <TableHeader class="sticky top-0 bg-background z-10">
                                    <TableRow>
                                        <TableHead>Nota</TableHead>
                                        <TableHead>Tanggal</TableHead>
                                        <TableHead>Karyawan</TableHead>
                                        <TableHead>Jenis</TableHead>
                                        <TableHead>Kategori</TableHead>
                                        <TableHead class="text-right">Total Berat (gr)</TableHead>
                                        <TableHead class="text-right">Nominal</TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow
                                        v-for="(row, index) in sales"
                                        :key="row.invoice"
                                    >
                                        <TableCell class="font-medium">
                                            {{ row.invoice }}
                                        </TableCell>
                                        <TableCell>{{ row.date }}</TableCell>
                                        <TableCell>{{ row.employee }}</TableCell>
                                        <TableCell>{{ row.sale_type }}</TableCell>
                                        <TableCell>{{ row.category }}</TableCell>
                                        <TableCell class="text-right">
                                            {{ row.total_weight }}
                                        </TableCell>
                                        <TableCell class="text-right">
                                            {{ formatRupiah(row.total_price) }}
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="sales.length" class="bg-muted/50 font-bold">
                                        <TableCell colspan="5" class="text-right">TOTAL</TableCell>
                                        <TableCell class="text-right">{{ totalWeight.toFixed(2) }}</TableCell>
                                        <TableCell class="text-right">{{ formatRupiah(totalAmount) }}</TableCell>
                                    </TableRow>

                                    <TableRow v-if="!sales.length">
                                        <TableCell colspan="7" class="text-center py-4">
                                            Tidak ada data penjualan.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
