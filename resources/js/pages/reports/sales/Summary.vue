<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFormat } from '@/composables/useFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { computed, ref, watch } from 'vue';

const { formatRupiah } = useFormat();

const props = defineProps<{
    category: string;
    filters: { start: string; end: string };
    soldWeights: { type: string; total: number }[];
    retailPayments: { method: string; total: number }[];
    wholesalePayments: { method: string; total: number }[];
    buybacks: { weight: number; nominal: number };
    grandTotalCash: number;
}>();

const categoryLabel = computed(() => {
    return props.category === 'gold' ? 'Emas' : 'Perak';
});

const dateDescription = computed(() => {
    const startDate = format(new Date(props.filters.start), 'dd MMMM yyyy', { locale: id });
    const endDate = format(new Date(props.filters.end), 'dd MMMM yyyy', { locale: id });
    return startDate === endDate ? startDate : `${startDate} - ${endDate}`;
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Laporan', href: '#' },
    { title: `Hasil Penjualan ${categoryLabel.value}`, href: '#' },
];

const selectedDate = ref(props.filters.start && props.filters.end ? [props.filters.start, props.filters.end] : []);

const applyFilters = () => {
    const params: Record<string, any> = {};
    if (selectedDate.value?.[0] && selectedDate.value?.[1]) {
        params.start = selectedDate.value[0];
        params.end = selectedDate.value[1];
    }
    router.get(route('reports.sales.summary', { category: props.category }), params, { preserveState: true });
};

watch(selectedDate, applyFilters);

const totalBeratLaku = () => {
    return props.soldWeights.reduce((acc, curr) => acc + curr.total, 0);
};

const totalPenerimaan = () => {
    return props.retailPayments.reduce((acc, curr) => acc + curr.total, 0);
};

const totalPenerimaanGrosir = () => {
    return props.wholesalePayments.reduce((acc, curr) => acc + curr.total, 0);
};

const formatWeight = (value: number) => {
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
};
</script>

<template>
    <Head :title="`Hasil Penjualan ${categoryLabel}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading
                class="mx-4"
                :title="`Hasil Penjualan ${categoryLabel}`"
                :description="`Laporan transaksi dari tanggal ${dateDescription}`"
            />

            <div class="max-w-8xl mx-auto">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <!-- FILTER -->
                        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center gap-2">
                                <!-- Place for other filters if needed -->
                            </div>

                            <div class="w-full sm:w-auto lg:w-80">
                                <VueDatePicker
                                    v-model="selectedDate"
                                    model-type="yyyy-MM-dd"
                                    :enable-time-picker="false"
                                    range
                                    auto-apply
                                    placeholder="Pilih rentang tanggal"
                                    locale="id"
                                />
                            </div>
                        </div>

                        <div class="space-y-8">
                            <!-- Rekap Berat Laku -->
                            <div>
                                <h3 class="mb-4 text-lg font-semibold text-slate-800">Rekap Berat Laku</h3>
                                <div class="overflow-x-auto rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow class="bg-gray-600 hover:bg-gray-600">
                                                <TableHead class="w-[80px] text-center text-white">No.</TableHead>
                                                <TableHead class="text-white">Jenis Penjualan</TableHead>
                                                <TableHead class="text-right text-white">Total</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="(item, index) in soldWeights" :key="index" class="hover:bg-transparent">
                                                <TableCell class="text-center">{{ index + 1 }}</TableCell>
                                                <TableCell>{{ item.type }}</TableCell>
                                                <TableCell class="text-right">{{ formatWeight(item.total) }}</TableCell>
                                            </TableRow>
                                            <!-- Grand Total -->
                                            <TableRow class="bg-gray-600 font-bold hover:bg-gray-600">
                                                <TableCell colspan="2" class="text-center text-white">Grand Total</TableCell>
                                                <TableCell class="text-right text-white">{{ formatWeight(totalBeratLaku()) }}</TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>

                            <!-- Rekap Penerimaan Pembayaran (Eceran) -->
                            <div>
                                <h3 class="mb-4 text-lg font-semibold text-slate-800">Rekap Penerimaan Pembayaran</h3>
                                <div class="overflow-x-auto rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow class="bg-gray-600 hover:bg-gray-600">
                                                <TableHead class="w-[80px] text-center text-white">No.</TableHead>
                                                <TableHead class="text-white">Jenis Bayar</TableHead>
                                                <TableHead class="text-right text-white">Total</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="(item, index) in retailPayments" :key="index" class="hover:bg-transparent">
                                                <TableCell class="text-center">{{ index + 1 }}</TableCell>
                                                <TableCell>{{ item.method }}</TableCell>
                                                <TableCell class="text-right">{{ formatRupiah(item.total) }}</TableCell>
                                            </TableRow>
                                            <!-- Grand Total -->
                                            <TableRow class="bg-gray-600 font-bold hover:bg-gray-600">
                                                <TableCell colspan="2" class="text-center text-white">Grand Total</TableCell>
                                                <TableCell class="text-right text-white">{{ formatRupiah(totalPenerimaan()) }}</TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>

                            <!-- Rekap Penerimaan Grosir -->
                            <div>
                                <h3 class="mb-4 text-lg font-semibold text-slate-800">Rekap Penerimaan Grosir</h3>
                                <div class="overflow-x-auto rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow class="bg-gray-600 hover:bg-gray-600">
                                                <TableHead class="w-[80px] text-center text-white">No.</TableHead>
                                                <TableHead class="text-white">Jenis Bayar</TableHead>
                                                <TableHead class="text-right text-white">Total</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="(item, index) in wholesalePayments" :key="index" class="hover:bg-transparent">
                                                <TableCell class="text-center">{{ index + 1 }}</TableCell>
                                                <TableCell>{{ item.method }}</TableCell>
                                                <TableCell class="text-right">{{ formatRupiah(item.total) }}</TableCell>
                                            </TableRow>
                                            <!-- Grand Total -->
                                            <TableRow class="bg-gray-600 font-bold hover:bg-gray-600">
                                                <TableCell colspan="2" class="text-center text-white">Grand Total</TableCell>
                                                <TableCell class="text-right text-white">{{ formatRupiah(totalPenerimaanGrosir()) }}</TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>

                            <!-- Rekap Buyback -->
                            <div>
                                <h3 class="mb-4 text-lg font-semibold text-slate-800">Rekap Buyback</h3>
                                <div class="overflow-x-auto rounded-md border">
                                    <Table>
                                        <TableBody>
                                            <TableRow class="bg-gray-600 font-bold hover:bg-gray-600">
                                                <TableCell class="text-white">Grand Total Berat Gram</TableCell>
                                                <TableCell class="w-[250px] text-right text-white">{{ formatWeight(buybacks.weight) }}</TableCell>
                                            </TableRow>
                                            <TableRow class="bg-gray-600 font-bold hover:bg-gray-600">
                                                <TableCell class="text-white">Grand Total Nominal</TableCell>
                                                <TableCell class="w-[250px] text-right text-white">{{ formatRupiah(buybacks.nominal) }}</TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>

                            <!-- Total Hasil Akhir Kas Tunai -->
                            <div class="mt-8 border-t pt-4">
                                <h3 class="mb-4 text-lg font-medium text-gray-800">Total Hasil Akhir Kas Tunai (Tunai - Buyback)</h3>
                                <div class="overflow-hidden rounded-md border bg-gray-600">
                                    <Table>
                                        <TableBody>
                                            <TableRow class="border-b-0 hover:bg-transparent">
                                                <TableCell class="border-r border-gray-700 py-4 text-lg font-bold text-white"
                                                    >Grand Total Nominal</TableCell
                                                >
                                                <TableCell class="w-[250px] py-4 text-right text-lg font-bold text-white">{{
                                                    formatRupiah(grandTotalCash)
                                                }}</TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
