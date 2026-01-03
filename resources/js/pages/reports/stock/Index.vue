<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Button } from '@/components/ui/button'
import Heading from '@/components/Heading.vue'
import PageNav from '@/components/PageNav.vue'
import Icon from '@/components/Icon.vue'
import { Badge } from '@/components/ui/badge'
import { useFormat } from '@/composables/useFormat'
import type { BreadcrumbItem } from '@/types'
import * as XLSX from 'xlsx'

const {
    items,
    itemTypes,
    summary,
    filters,
} = defineProps<{
    items: any
    itemTypes: Record<number, string>
    summary: {
        totalItems: number
        totalWeight: number
        totalBuy: number
        totalSell: number
        margin: number
    }
    filters: any
}>()

const { formatRupiah } = useFormat()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Laporan Stok', href: '#' },
]

const category = ref(filters.category ?? 'all')
const item_type_id = ref(filters.item_type_id ?? 'all')
const status = ref(filters.status ?? 'all')

const applyFilters = () => {
    const params: Record<string, any> = {}

    if (category.value !== 'all') params.category = category.value
    if (item_type_id.value !== 'all') params.item_type_id = item_type_id.value
    if (status.value !== 'all') params.status = status.value

    router.get(route('reports.stock.index'), params, {
        preserveScroll: true,
        preserveState: true,
    })
}

watch([category, item_type_id, status], applyFilters)

/* ===============================
   EXPORT EXCEL (XLSX)
   =============================== */
const exportExcel = () => {
    const rows = items.data.map((row: any) => ({
        Kode: row.code,
        Nama: row.name,
        Kategori: row.category,
        Tipe: row.type,
        Status: row.status_label,
        'Berat (gr)': row.weight,
        'Harga Beli': row.price_buy,
        'Harga Jual': row.price_sell,
    }))

    rows.push({})

    rows.push({
        Kode: 'TOTAL',
        'Berat (gr)': summary.totalWeight,
        'Harga Beli': summary.totalBuy,
        'Harga Jual': summary.totalSell,
    })

    const worksheet = XLSX.utils.json_to_sheet(rows)
    const workbook = XLSX.utils.book_new()

    XLSX.utils.book_append_sheet(workbook, worksheet, 'Laporan Stok')

    const filename = `laporan-stok-${category.value}-${status.value}.xlsx`
    XLSX.writeFile(workbook, filename)
}
</script>

<template>
    <Head title="Laporan Stok" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">

            <!-- HEADING + SUMMARY -->
            <div class="mx-4 space-y-4">
                <Heading
                    title="Laporan Stok"
                    description="Ringkasan kondisi stok emas dan perak"
                />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card class="gap-2 py-4 bg-emerald-50 border-emerald-200">
                        <CardHeader class="flex justify-between flex-row">
                            <CardTitle class="text-sm text-emerald-700">Total Barang</CardTitle>
                            <Icon name="Package" class="w-5 h-5 text-emerald-600" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold text-emerald-800">
                                {{ summary.totalItems }} pcs
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="gap-2 py-4 bg-yellow-50 border-yellow-200">
                        <CardHeader class="flex justify-between flex-row">
                            <CardTitle class="text-sm text-yellow-700">Total Berat</CardTitle>
                            <Icon name="Scale" class="w-5 h-5 text-yellow-600" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold text-yellow-800">
                                {{ summary.totalWeight.toFixed(2) }} gr
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="gap-2 py-4 bg-sky-50 border-sky-200">
                        <CardHeader class="flex justify-between flex-row">
                            <CardTitle class="text-sm text-sky-700">Total Modal</CardTitle>
                            <Icon name="Wallet" class="w-5 h-5 text-sky-600" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold text-sky-800">
                                {{ formatRupiah(summary.totalBuy) }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="gap-2 py-4 bg-indigo-50 border-indigo-200">
                        <CardHeader class="flex justify-between flex-row">
                            <CardTitle class="text-sm text-indigo-700">
                                Nilai Jual & Margin
                            </CardTitle>
                            <Icon name="TrendingUp" class="w-5 h-5 text-indigo-600" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold text-indigo-800">
                                {{ formatRupiah(summary.totalSell) }}
                            </p>
                            <p class="text-xs text-indigo-700">
                                Margin {{ formatRupiah(summary.margin) }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- TABLE -->
            <div class="max-w-8xl mx-auto mt-6">
                <Card class="py-4 md:mx-4">
                    <CardContent>

                        <!-- FILTER + EXPORT -->
                        <div class="flex flex-wrap gap-4 mb-4 justify-between">
                            <div class="flex flex-wrap gap-4">
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

                                <div class="w-48">
                                    <Select v-model="item_type_id">
                                        <SelectTrigger><SelectValue placeholder="Tipe Barang" /></SelectTrigger>
                                        <SelectContent class="max-h-72 overflow-y-auto">
                                            <SelectItem value="all">Semua</SelectItem>
                                            <SelectItem
                                                v-for="(name, id) in itemTypes"
                                                :key="id"
                                                :value="id"
                                            >
                                                {{ name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="w-40">
                                    <Select v-model="status">
                                        <SelectTrigger><SelectValue placeholder="Status" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua</SelectItem>
                                            <SelectItem value="ready">Siap Jual</SelectItem>
                                            <SelectItem value="sold">Terjual</SelectItem>
                                            <SelectItem value="damaged">Rusak</SelectItem>
                                            <SelectItem value="buyback">Buyback</SelectItem>
                                            <SelectItem value="not_ready">Belum Siap</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <Button variant="secondary" @click="exportExcel">
                                Export Excel
                            </Button>
                        </div>

                        <div class="overflow-x-auto max-h-[70vh] overflow-y-auto">
                            <Table>
                                <TableHeader class="sticky top-0 bg-background z-10">
                                    <TableRow>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Kategori</TableHead>
                                        <TableHead>Tipe</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead class="text-right">Berat</TableHead>
                                        <TableHead class="text-right">Modal</TableHead>
                                        <TableHead class="text-right">Harga Jual</TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow v-for="row in items.data" :key="row.code">
                                        <TableCell>{{ row.code }}</TableCell>
                                        <TableCell>{{ row.name }}</TableCell>
                                        <TableCell>{{ row.category }}</TableCell>
                                        <TableCell>{{ row.type }}</TableCell>
                                        <TableCell class="text-center">
                                            <Badge>{{ row.status_label }}</Badge>
                                        </TableCell>
                                        <TableCell class="text-right">{{ row.weight }}</TableCell>
                                        <TableCell class="text-right">{{ formatRupiah(row.price_buy) }}</TableCell>
                                        <TableCell class="text-right">{{ formatRupiah(row.price_sell) }}</TableCell>
                                    </TableRow>

                                    <TableRow v-if="!items.total">
                                        <TableCell colspan="8" class="text-center py-4">
                                            Tidak ada data stok.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <PageNav :data="items" />
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
