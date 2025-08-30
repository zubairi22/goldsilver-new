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
import Icon from '@/components/Icon.vue';

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
        params.mode = 'custom'
        params.start = date.value[0]
        params.end   = date.value[1]
    } else {
        params.mode = mode.value
    }

    router.get(route('dashboard'), params, {
        preserveScroll: true,
        preserveState: true,
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
                <Card class="gap-0 bg-emerald-50 border-emerald-200">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-sm font-medium text-emerald-700">Total Penjualan</CardTitle>
                        <Icon name="DollarSign" class="w-5 h-5 text-emerald-600" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold text-emerald-800">{{ formatRupiah(summary.totalSales) }}</p>
                    </CardContent>
                </Card>

                <Card class="gap-0 bg-green-50 border-green-200">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-sm font-medium text-green-700">Penjualan Terbayar</CardTitle>
                        <Icon name="CheckCircle" class="w-5 h-5 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold text-green-800">{{ formatRupiah(summary.paidSales) }}</p>
                    </CardContent>
                </Card>

                <Card class="gap-0 bg-yellow-50 border-yellow-200">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-sm font-medium text-yellow-700">Penjualan Piutang</CardTitle>
                        <Icon name="FileText" class="w-5 h-5 text-yellow-600" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold text-yellow-800">{{ formatRupiah(summary.outstandingPayment) }}</p>
                    </CardContent>
                </Card>

                <Card class="gap-0 bg-red-50 border-red-200">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-sm font-medium text-red-700">Total Refund</CardTitle>
                        <Icon name="RotateCcw" class="w-5 h-5 text-red-600" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold text-red-800">{{ formatRupiah(summary.totalRefund) }}</p>
                    </CardContent>
                </Card>

                <Card class="gap-0 bg-blue-50 border-blue-200">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-sm font-medium text-blue-700">Total Produk Terjual</CardTitle>
                        <Icon name="Package" class="w-5 h-5 text-blue-600" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold text-blue-800">{{ summary.itemsSold }}</p>
                    </CardContent>
                </Card>

                <Card class="gap-0 bg-indigo-50 border-indigo-200">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-sm font-medium text-indigo-700">Jumlah Transaksi</CardTitle>
                        <Icon name="ShoppingCart" class="w-5 h-5 text-indigo-600" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold text-indigo-800">{{ summary.transactionCount }}</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 md:grid-cols-4">
                <Card class="bg-emerald-50 border-emerald-200">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-emerald-700">Penjualan per Kategori</CardTitle>
                        <Icon name="FolderTree" class="w-5 h-5 text-emerald-600" />
                    </CardHeader>
                    <CardContent class="h-64">
                        <HorizontalBarChart :labels="categoryLabels" :data="categoryValues" color="#10b981" prefix="Rp " />
                    </CardContent>
                </Card>

                <Card class="bg-sky-50 border-sky-200">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-sky-700">Produk Terlaris</CardTitle>
                        <Icon name="Star" class="w-5 h-5 text-sky-600" />
                    </CardHeader>
                    <CardContent class="h-64">
                        <HorizontalBarChart :labels="topProductLabels" :data="topProductValues" suffix=" pcs" color="#0ea5e9" />
                    </CardContent>
                </Card>

                <Card class="bg-indigo-50 border-indigo-200">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-indigo-700">Penjualan per Kasir</CardTitle>
                        <Icon name="UserCheck" class="w-5 h-5 text-indigo-600" />
                    </CardHeader>
                    <CardContent class="h-64">
                        <HorizontalBarChart :labels="cashierLabels" :data="cashierValues" prefix="Rp " color="#6366f1" />
                    </CardContent>
                </Card>

                <Card class="bg-rose-50 border-rose-200">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-rose-700">Stok Terendah</CardTitle>
                        <Icon name="AlertTriangle" class="w-5 h-5 text-rose-600" />
                    </CardHeader>
                    <CardContent class="h-64">
                        <HorizontalBarChart :labels="stockLabels" :data="stockValues" suffix=" pcs" color="#ef4444" />
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
