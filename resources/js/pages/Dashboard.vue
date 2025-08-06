<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { BreadcrumbItem } from '@/types';
import HorizontalBarChart from '@/components/HorizontalBarChart.vue';
import { useCurrency } from '@/composables/useCurrency';
import { ref, watch } from 'vue';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const { summary, salesByCategory, topProducts, salesByCashier, lowestStocks } = defineProps([
    'summary',
    'salesByCategory',
    'topProducts',
    'salesByCashier',
    'lowestStocks',
]);

const { formatRupiah } = useCurrency();

const categoryLabels = salesByCategory.map((item: any) => item.category);
const categoryValues = salesByCategory.map((item: any) => item.total);

const topProductLabels = topProducts.map((item: any) => item.name);
const topProductValues = topProducts.map((item: any) => item.total_sold);

const cashierLabels = salesByCashier.map((item: any) => item.cashier);
const cashierValues = salesByCashier.map((item: any) => item.total);

const stockLabels = lowestStocks.map((item: any) => item.name);
const stockValues = lowestStocks.map((item: any) => item.stock);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const mode = ref<'daily' | 'weekly' | 'monthly'>('daily')

watch(mode, () => {
    router.get('/dashboard', { mode: mode.value }, { preserveScroll: true, preserveState: true })
})
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="pt-8 px-8 w-60">
            <Select v-model="mode">
                <SelectTrigger id="satuan">
                    <SelectValue placeholder="Pilih Satuan" />
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
        <div class="grid gap-4 p-8">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle>Total Penjualan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold text-green-600">{{ formatRupiah(summary.totalSales) }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Jumlah Transaksi</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ summary.transactionCount }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Total Produk Terjual</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ summary.itemsSold }}</p>
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
