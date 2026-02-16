<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFormat } from '@/composables/useFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { ref, watch } from 'vue';

const props = defineProps<{
    summary: {
        totalSales: number;
        totalBuyback: number;
        totalReceivables: number;
        totalReadyStock: number;
    };
    latestSales: Array<{
        id: number;
        invoice_no: string;
        customer_name: string;
        total_price: number;
        status: string;
        status_label: string;
        date: string;
    }>;
    latestBuybacks: Array<{
        id: number;
        buyback_no: string;
        customer_name: string;
        total_price: number;
        date: string;
    }>;
    filters: {
        mode: 'daily' | 'weekly' | 'monthly';
        start: string;
        end: string;
    };
}>();

const { formatRupiah } = useFormat();

const mode = ref<'daily' | 'weekly' | 'monthly'>(route().params.mode ?? props.filters.mode ?? 'daily');

const date = ref<[string, string] | null>(route().params.start && route().params.end ? [route().params.start, route().params.end] : null);

const applyFilters = () => {
    const params: Record<string, string> = {};

    if (date.value && date.value[0] && date.value[1]) {
        params.mode = '';
        params.start = date.value[0];
        params.end = date.value[1];
    } else {
        date.value = null;
        params.mode = mode.value;
    }

    router.get(route('dashboard'), params, {
        preserveScroll: true,
        preserveState: true,
    });
};

watch(mode, (val) => {
    if (val) date.value = null;
    applyFilters();
});

watch(date, () => {
    applyFilters();
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <div class="mb-4 flex flex-col justify-end gap-3 px-4 pt-4 md:flex-row md:px-8">
            <div class="w-full md:w-32">
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

            <div class="w-full md:w-64">
                <VueDatePicker v-model="date" range :enable-time-picker="false" model-type="yyyy-MM-dd" locale="id" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 px-4 pb-6 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Penjualan -->
            <Card class="gap-0 border-emerald-200 bg-emerald-50">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-emerald-700">Total Penjualan</CardTitle>
                    <Icon name="DollarSign" class="h-5 w-5 text-emerald-600" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold text-emerald-800">
                        {{ formatRupiah(summary.totalSales) }}
                    </p>
                </CardContent>
            </Card>

            <!-- Total Buyback -->
            <Card class="gap-0 border-rose-200 bg-rose-50">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-rose-700">Total Buyback</CardTitle>
                    <Icon name="RefreshCcw" class="h-5 w-5 text-rose-600" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold text-rose-800">
                        {{ formatRupiah(summary.totalBuyback) }}
                    </p>
                </CardContent>
            </Card>

            <!-- Total Piutang -->
            <Card class="gap-0 border-amber-200 bg-amber-50">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-amber-700">Total Piutang</CardTitle>
                    <Icon name="CreditCard" class="h-5 w-5 text-amber-600" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold text-amber-800">
                        {{ formatRupiah(summary.totalReceivables) }}
                    </p>
                </CardContent>
            </Card>

            <!-- Total Stok Ready -->
            <Card class="gap-0 border-blue-200 bg-blue-50">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-blue-700">Stok Ready</CardTitle>
                    <Icon name="Package" class="h-5 w-5 text-blue-600" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold text-blue-800">{{ summary.totalReadyStock }} Item</p>
                </CardContent>
            </Card>
        </div>

        <div class="grid grid-cols-1 gap-6 px-4 pb-8 lg:grid-cols-2">
            <!-- Latest Sales -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-md">5 Penjualan Terakhir</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Invoice</TableHead>
                                <TableHead>Pelanggan</TableHead>
                                <TableHead class="text-right">Total</TableHead>
                                <TableHead class="text-center">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="sale in latestSales" :key="sale.id">
                                <TableCell class="font-medium">
                                    <div class="flex flex-col">
                                        <span>{{ sale.invoice_no }}</span>
                                        <span class="text-xs text-muted-foreground">{{ sale.date }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>{{ sale.customer_name }}</TableCell>
                                <TableCell class="text-right">{{ formatRupiah(sale.total_price) }}</TableCell>
                                <TableCell class="text-center">
                                    <Badge :variant="sale.status === 'paid' ? 'default' : sale.status === 'partial' ? 'secondary' : 'destructive'">
                                        {{ sale.status_label }}
                                    </Badge>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="latestSales.length === 0">
                                <TableCell colspan="4" class="py-4 text-center text-muted-foreground"> Belum ada data penjualan </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Latest Buybacks -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-md">5 Buyback Terakhir</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>No. Buyback</TableHead>
                                <TableHead>Pelanggan</TableHead>
                                <TableHead class="text-right">Total</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="buyback in latestBuybacks" :key="buyback.id">
                                <TableCell class="font-medium">
                                    <div class="flex flex-col">
                                        <span>{{ buyback.buyback_no }}</span>
                                        <span class="text-xs text-muted-foreground">{{ buyback.date }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>{{ buyback.customer_name }}</TableCell>
                                <TableCell class="text-right">{{ formatRupiah(buyback.total_price) }}</TableCell>
                            </TableRow>
                            <TableRow v-if="latestBuybacks.length === 0">
                                <TableCell colspan="3" class="py-4 text-center text-muted-foreground"> Belum ada data buyback </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
