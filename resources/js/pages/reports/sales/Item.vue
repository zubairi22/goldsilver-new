<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import PageNav from '@/components/PageNav.vue';
import SearchInput from '@/components/SearchInput.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFormat } from '@/composables/useFormat';
import { useSearch } from '@/composables/useSearch';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { ref, watch } from 'vue';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import * as XLSX from 'xlsx';

const { items, filters, totalWeight, totalAmount, isSegmented } = defineProps<{
    items: any;
    filters: any;
    totalWeight: number;
    totalAmount: number;
    isSegmented?: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Laporan Penjualan Item', href: '#' },
];

const { search } = useSearch('reports.sales.item', filters.search, ['items']);

const { formatRupiah } = useFormat();

const category = ref(filters.category ?? 'all');

const date = ref(filters.start && filters.end ? [filters.start, filters.end] : []);

const applyFilters = () => {
    const params: Record<string, any> = {};

    if (!isSegmented && category.value !== 'all') {
        params.category = category.value;
    }

    if (date.value?.[0] && date.value?.[1]) {
        params.start = date.value[0];
        params.end = date.value[1];
    }

    if (search.value) params.search = search.value;

    router.get(route('reports.sales.item'), params, {
        preserveScroll: true,
        preserveState: true,
    });
};

watch([category, date], applyFilters);

const exportExcel = () => {
    const rows = items.map((row: any, index: number) => ({
        No: index + 1,
        Nota: row.invoice,
        Tanggal: row.date,
        Jenis: row.sale_type,
        Kategori: row.category,
        Item: row.item,
        'Berat (gr)': row.weight,
        Subtotal: row.subtotal,
    }));

    const worksheet = XLSX.utils.json_to_sheet(rows);
    XLSX.utils.sheet_add_aoa(worksheet, [
        ['TOTAL', '', '', '', '', '', totalWeight, totalAmount]
    ], { origin: -1 });

    const workbook = XLSX.utils.book_new();

    XLSX.utils.book_append_sheet(workbook, worksheet, 'Laporan Item');

    const filename = `laporan-penjualan-item-${filters.start}-sd-${filters.end}.xlsx`;
    XLSX.writeFile(workbook, filename);
};

const exportPdf = (action: 'download' | 'stream') => {
    const doc = new jsPDF();
    
    doc.text('Laporan Penjualan Per Item', 14, 15);
    
    const tableData = items.map((row: any, index: number) => [
        index + 1,
        row.invoice,
        row.date,
        row.sale_type,
        row.category,
        row.item,
        row.weight,
        formatRupiah(row.subtotal)
    ]);

    autoTable(doc, {
        startY: 20,
        head: [['No', 'Nota', 'Tanggal', 'Jenis', 'Kategori', 'Item', 'Berat', 'Subtotal']],
        body: tableData,
        foot: [['', 'TOTAL', '', '', '', '', totalWeight.toFixed(2), formatRupiah(totalAmount)]],
        showFoot: 'lastPage',
        theme: 'striped',
        headStyles: { fillColor: [30, 41, 59] },
        footStyles: { fillColor: [241, 245, 249], textColor: [0, 0, 0], fontStyle: 'bold' },
        styles: { fontSize: 8 }
    });

    if (action === 'download') {
        doc.save(`laporan-item-${filters.start}-sd-${filters.end}.pdf`);
    } else {
        window.open(doc.output('bloburl'), '_blank');
    }
};
</script>

<template>
    <Head title="Laporan Penjualan Barang" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <div class="mx-4 items-center space-y-4 md:flex md:justify-between md:space-y-0">
                <Heading class="flex-1 md:mr-6" title="Laporan Penjualan Barang" description="Daftar seluruh barang yang terjual" />

                <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 md:w-[500px]">
                    <Card class="gap-1 border-yellow-200 bg-yellow-50 py-4">
                        <CardHeader>
                            <CardTitle class="text-sm text-yellow-700"> Total Berat </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold text-yellow-800">{{ totalWeight.toFixed(2) }} gr</p>
                        </CardContent>
                    </Card>

                    <Card class="gap-1 border-emerald-200 bg-emerald-50 py-4">
                        <CardHeader>
                            <CardTitle class="text-sm text-emerald-700"> Total Penjualan </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold text-emerald-800">
                                {{ formatRupiah(totalAmount) }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- CONTENT -->
            <div class="max-w-8xl mx-auto mt-6">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <!-- FILTER -->
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                            <div class="flex flex-wrap items-center gap-4">
                                <div v-if="!isSegmented" class="w-40">
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
                                <VueDatePicker v-model="date" range :enable-time-picker="false" model-type="yyyy-MM-dd" locale="id" />
                            </div>
                        </div>

                        <!-- SEARCH + EXPORT -->
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                            <div class="w-full lg:w-80">
                                <SearchInput v-model:search="search" />
                            </div>

                            <div class="flex gap-2">
                                <Button variant="secondary" @click="exportExcel"> Excel </Button>
                                <Button variant="secondary" @click="exportPdf('stream')"> Print </Button>
                                <Button variant="secondary" @click="exportPdf('download')"> PDF </Button>
                            </div>
                        </div>

                        <!-- TABLE -->
                        <div class="max-h-[70vh] overflow-x-auto overflow-y-auto">
                            <Table>
                                <TableHeader class="sticky top-0 z-10 bg-background">
                                    <TableRow>
                                        <TableHead>Nota</TableHead>
                                        <TableHead>Tanggal</TableHead>
                                        <TableHead>Jenis</TableHead>
                                        <TableHead>Kategori</TableHead>
                                        <TableHead>Item</TableHead>
                                        <TableHead class="text-right">Berat</TableHead>
                                        <TableHead class="text-right">Subtotal</TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow v-for="row in items" :key="row.invoice + row.item">
                                        <TableCell>{{ row.invoice }}</TableCell>
                                        <TableCell>{{ row.date }}</TableCell>
                                        <TableCell>{{ row.sale_type }}</TableCell>
                                        <TableCell>{{ row.category }}</TableCell>
                                        <TableCell>{{ row.item }}</TableCell>
                                        <TableCell class="text-right">{{ row.weight }} gr</TableCell>
                                        <TableCell class="text-right">
                                            {{ formatRupiah(row.subtotal) }}
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="items.length" class="bg-muted/50 font-bold">
                                        <TableCell colspan="5" class="text-right">TOTAL</TableCell>
                                        <TableCell class="text-right">{{ totalWeight.toFixed(2) }} gr</TableCell>
                                        <TableCell class="text-right">{{ formatRupiah(totalAmount) }}</TableCell>
                                    </TableRow>

                                    <TableRow v-if="!items.length">
                                        <TableCell colspan="7" class="py-4 text-center"> Tidak ada data item terjual. </TableCell>
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
