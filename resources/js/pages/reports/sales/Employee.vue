<script setup lang="ts">
import Heading from '@/components/Heading.vue';
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
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import { ref, watch } from 'vue';
import * as XLSX from 'xlsx';

const { sales, filters, employees, totalWeightWholesale, totalWeightRetail, totalInvoice } = defineProps<{
    sales: any;
    filters: any;
    employees: any[];
    totalWeightWholesale: number;
    totalWeightRetail: number;
    totalInvoice: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Laporan Penjualan', href: '#' },
];

const { search } = useSearch('reports.sales.employee', filters.search, ['sales']);

const { formatRupiah } = useFormat();

const user_id = ref(filters.user_id ?? 'all');
const category = ref(filters.category ?? 'all');

const date = ref(filters.start && filters.end ? [filters.start, filters.end] : []);

const applyFilters = () => {
    const params: Record<string, any> = {};

    if (user_id.value !== 'all') params.user_id = user_id.value;
    if (category.value !== 'all') params.category = category.value;

    if (date.value?.[0] && date.value?.[1]) {
        params.start = date.value[0];
        params.end = date.value[1];
    }

    if (search.value) params.search = search.value;

    router.get(route('reports.sales.employee'), params, {
        preserveScroll: true,
        preserveState: true,
    });
};

watch([user_id, category, date], applyFilters);

const exportExcel = () => {
    const rows = sales.map((row: any, index: number) => ({
        No: index + 1,
        'Nama Karyawan': row.employee,
        'Jumlah Transaksi': row.total_count,
        'Partai (gr)': row.weight_wholesale,
        'Eceran (gr)': row.weight_retail,
    }));

    const worksheet = XLSX.utils.json_to_sheet(rows);
    XLSX.utils.sheet_add_aoa(worksheet, [['TOTAL', '', totalInvoice, totalWeightWholesale, totalWeightRetail]], { origin: -1 });

    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Laporan Karyawan');

    const filename = `laporan-penjualan-karyawan-${filters.start}-sd-${filters.end}.xlsx`;
    XLSX.writeFile(workbook, filename);
};

const exportPdf = (action: 'download' | 'stream') => {
    const doc = new jsPDF();

    doc.text('Laporan Penjualan Per Karyawan', 14, 15);

    const tableData = sales.map((row: any, index: number) => [index + 1, row.employee, row.total_count, row.weight_wholesale, row.weight_retail]);

    autoTable(doc, {
        startY: 20,
        head: [['No', 'Karyawan', 'Jumlah Transaksi', 'Partai (gr)', 'Eceran (gr)']],
        body: tableData,
        foot: [['', 'TOTAL', totalInvoice, totalWeightWholesale.toFixed(2), totalWeightRetail.toFixed(2)]],
        showFoot: 'lastPage',
        theme: 'striped',
        headStyles: { fillColor: [30, 41, 59] },
        footStyles: { fillColor: [241, 245, 249], textColor: [0, 0, 0], fontStyle: 'bold' },
        styles: { fontSize: 8 },
    });

    if (action === 'download') {
        doc.save(`laporan-karyawan-${filters.start}-sd-${filters.end}.pdf`);
    } else {
        window.open(doc.output('bloburl'), '_blank');
    }
};
</script>

<template>
    <Head title="Laporan Penjualan Karyawan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <div class="mx-4 items-center space-y-4 md:flex md:justify-between md:space-y-0">
                <Heading class="flex-1 md:mr-6" title="Laporan Penjualan Karyawan" description="Rekap penjualan per nota berdasarkan karyawan" />

                <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-3 md:w-[36rem]">
                    <Card class="gap-1 border-blue-200 bg-blue-50 py-4">
                        <CardHeader>
                            <CardTitle class="text-sm font-medium text-blue-700"> Total Nota </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold text-blue-700">
                                {{ totalInvoice }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="gap-1 border-yellow-200 bg-yellow-50 py-4">
                        <CardHeader>
                            <CardTitle class="text-sm font-medium text-yellow-700"> Total Partai </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold text-yellow-800">{{ totalWeightWholesale.toFixed(2) }} gr</p>
                        </CardContent>
                    </Card>

                    <Card class="gap-1 border-rose-200 bg-rose-50 py-4">
                        <CardHeader>
                            <CardTitle class="text-sm font-medium text-rose-700"> Total Eceran </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold text-rose-800">{{ totalWeightRetail.toFixed(2) }} gr</p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div class="max-w-8xl mx-auto mt-6">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                            <div class="flex flex-wrap items-center gap-4">
                                <div class="w-48">
                                    <Select v-model="user_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Karyawan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua</SelectItem>
                                            <SelectItem v-for="emp in employees" :key="emp.id" :value="emp.id">
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
                                            <SelectItem value="wholesale">Partai</SelectItem>
                                            <SelectItem value="retail">Eceran</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div class="w-full sm:w-auto lg:w-80">
                                <VueDatePicker v-model="date" range :enable-time-picker="false" model-type="yyyy-MM-dd" locale="id" />
                            </div>
                        </div>

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

                        <div class="max-h-[70vh] overflow-x-auto overflow-y-auto">
                            <Table>
                                <TableHeader class="sticky top-0 z-10 bg-background">
                                    <TableRow>
                                        <TableHead class="w-12">No</TableHead>
                                        <TableHead>Karyawan</TableHead>
                                        <TableHead class="text-center">Jumlah Transaksi</TableHead>
                                        <TableHead class="text-right">Partai (gr)</TableHead>
                                        <TableHead class="text-right">Eceran (gr)</TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow v-for="(row, index) in sales" :key="index">
                                        <TableCell>{{ Number(index) + 1 }}</TableCell>
                                        <TableCell class="font-medium">{{ row.employee }}</TableCell>
                                        <TableCell class="text-center">{{ row.total_count }}</TableCell>
                                        <TableCell class="text-right">
                                            {{ row.weight_wholesale }}
                                        </TableCell>
                                        <TableCell class="text-right">
                                            {{ row.weight_retail }}
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="sales.length" class="bg-muted/50 font-bold">
                                        <TableCell colspan="2" class="text-right">TOTAL</TableCell>
                                        <TableCell class="text-center">{{ totalInvoice }}</TableCell>
                                        <TableCell class="text-right">{{ totalWeightWholesale.toFixed(2) }}</TableCell>
                                        <TableCell class="text-right">{{ totalWeightRetail.toFixed(2) }}</TableCell>
                                    </TableRow>

                                    <TableRow v-if="!sales.length">
                                        <TableCell colspan="5" class="py-4 text-center"> Tidak ada data penjualan. </TableCell>
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
