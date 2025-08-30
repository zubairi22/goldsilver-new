<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { BreadcrumbItem } from '@/types';
import HorizontalBarChart from '@/components/HorizontalBarChart.vue';
import { useFormat } from '@/composables/useFormat';
import { ref, watch } from 'vue';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'

const { summary, salesByCategory, topProducts, salesByCashier, lowestStocks } = defineProps([
    'summary',
    'salesByCategory',
    'topProducts',
    'salesByCashier',
    'lowestStocks',
]);

const { formatRupiah } = useFormat();

const categoryLabels = salesByCategory.map((item: any) => item.category);
const categoryValues = salesByCategory.map((item: any) => item.total);

const topProductLabels = topProducts.map((item: any) => item.name);
const topProductValues = topProducts.map((item: any) => item.total_sold);

const cashierLabels = salesByCashier.map((item: any) => item.cashier);
const cashierValues = salesByCashier.map((item: any) => item.total);

const stockLabels = lowestStocks.map((item: any) => item.name);
const stockValues = lowestStocks.map((item: any) => item.stock);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const payment_method = ref<'all' | 'cash' | 'qris' | 'debit' | 'deposit'>('all')

const mode = ref<'daily' | 'weekly' | 'monthly'>('daily')

const date = ref();

const applyFilters = () => {
    const params: Record<string, string> = {}

    if (payment_method.value !== 'all') {
        params.payment_method = payment_method.value
    }

    if (date.value && date.value[0] && date.value[1]) {
        console.log(date.value);
        params.mode = 'custom'
        params.start = date.value[0]
        params.end   = date.value[1]
    } else {
        params.mode = mode.value
    }

    router.get(route('dashboard'), params, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    })
}

watch([payment_method, mode, date], applyFilters)

</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-wrap justify-between items-center gap-4 px-8 pt-8 mb-4">
            <div class="w-32">
                <Select v-model="payment_method">
                    <SelectTrigger id="payment_method">
                        <SelectValue placeholder="Pilih Metode Pembayaran" />
                    </SelectTrigger>
                    <SelectContent class="w-32">
                        <SelectGroup>
                            <SelectItem value="all">Semua</SelectItem>
                            <SelectItem value="cash">Cash</SelectItem>
                            <SelectItem value="qris">QRIS</SelectItem>
                            <SelectItem value="debit">Debit</SelectItem>
                            <SelectItem value="deposit">Deposit</SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
            </div>
            <div class="flex items-center gap-x-4">
                <div class="w-32">
                    <Select v-model="mode">
                        <SelectTrigger id="mode">
                            <SelectValue placeholder="Pilih Mode" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="daily">Hari Ini</SelectItem>
                                <SelectItem value="weekly">Minggu Ini</SelectItem>
                                <SelectItem value="monthly">Bulan Ini</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>
                <div>
                    <VueDatePicker
                        v-model="date"
                        range
                        :enable-time-picker="false"
                        model-type="yyyy-MM-dd"
                        locale="id"
                    />
                </div>
            </div>
        </div>
        <div class="grid gap-4 py-2 px-8">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Card class="gap-0">
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">Total Penjualan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ formatRupiah(summary.totalSales) }}</p>
                    </CardContent>
                </Card>
                <Card class="gap-0">
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">Penjualan Terbayar</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ formatRupiah(summary.paidSales) }}</p>
                    </CardContent>
                </Card>
                <Card class="gap-0">
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">Penjualan Piutang</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ formatRupiah(summary.outstandingPayment) }}</p>
                    </CardContent>
                </Card>
                <Card class="gap-0">
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">Total Refund</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ formatRupiah(summary.totalRefund) }}</p>
                    </CardContent>
                </Card>
                <Card class="gap-0">
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">Total Produk Terjual</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ summary.itemsSold }}</p>
                    </CardContent>
                </Card>
                <Card class="gap-0">
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">Jumlah Transaksi</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ summary.transactionCount }}</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 md:grid-cols-4">
                <Card>
                    <CardHeader><CardTitle>Penjualan per Kategori</CardTitle></CardHeader>
                    <CardContent class="h-64">
                        <HorizontalBarChart :labels="categoryLabels" :data="categoryValues" color="#10b981" prefix="Rp " />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Produk Terlaris</CardTitle></CardHeader>
                    <CardContent class="h-64">
                        <HorizontalBarChart :labels="topProductLabels" :data="topProductValues" suffix=" pcs" color="#0ea5e9" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Penjualan per Kasir</CardTitle></CardHeader>
                    <CardContent class="h-64">
                        <HorizontalBarChart :labels="cashierLabels" :data="cashierValues" prefix="Rp " color="#6366f1" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Stok Terendah</CardTitle></CardHeader>
                    <CardContent class="h-64">
                        <HorizontalBarChart :labels="stockLabels" :data="stockValues" suffix=" pcs" color="#ef4444" />
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
