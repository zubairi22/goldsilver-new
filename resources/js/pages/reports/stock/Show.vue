<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import PageNav from '@/components/PageNav.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { ref, watch } from 'vue';
import { format } from 'date-fns';
import { useFormat } from '@/composables/useFormat';

const { formatDate } = useFormat()
const { product } = defineProps(['product', 'mutations', 'summary']);

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Laporan Stok', href: '/reports/stock' },
    { title: product.name, href: '#' },
]

const today = format(new Date(), 'yyyy-MM-dd');
const date = ref<[string, string] | null>([
    route().params.start ?? today,
    route().params.end ?? today,
]);

const applyFilters = () => {
    const params: Record<string, string> = {};
    if (date.value && date.value[0] && date.value[1]) {
        params.start = date.value[0];
        params.end = date.value[1];
    }
    router.get(route('reports.stock.show', product.id), params, {
        preserveScroll: true,
        preserveState: true,
    });
};

watch(date, applyFilters);
</script>

<template>
    <Head :title="`Mutasi - ${product.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <!-- Product Info -->
            <div class="mb-4 max-8-xl mx-auto">
                <Card class="border border-border/50 shadow-sm bg-muted/20 mx-4">
                    <CardContent class="space-y-4 py-5 px-6">
                        <!-- Header Produk -->
                        <div class="flex flex-wrap justify-between items-center gap-4">
                            <div class="space-y-1">
                                <h2 class="text-lg font-semibold text-foreground">
                                    {{ product.name }}
                                </h2>
                                <p class="text-sm text-muted-foreground">
                                    Kategori: <span class="font-medium text-foreground">{{ product.category || '-' }}</span>
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="text-sm text-muted-foreground">Stok Saat Ini</p>
                                <p
                                    class="text-2xl font-bold"
                                    :class="product.stock <= 0 ? 'text-destructive' : 'text-foreground'"
                                >
                                    {{ product.stock }}
                                </p>
                            </div>
                        </div>

                        <!-- Ringkasan Mutasi -->
                        <div class="border-t pt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                            <div class="flex justify-between sm:block">
                                <p class="text-muted-foreground">Total Masuk</p>
                                <p class="font-semibold sm:mt-1">{{ summary.in }}</p>
                            </div>
                            <div class="flex justify-between sm:block">
                                <p class="text-muted-foreground">Total Keluar</p>
                                <p class="font-semibold sm:mt-1">{{ summary.out }}</p>
                            </div>
                            <div class="flex justify-between sm:block">
                                <p class="text-muted-foreground">Pergerakan Stok</p>
                                <p
                                    class="font-semibold sm:mt-1"
                                    :class="summary.net < 0 ? 'text-destructive' : 'text-foreground'"
                                >
                                    {{ summary.net }}
                                </p>
                            </div>
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="flex justify-end pt-4 border-t">
                            <Button variant="secondary" @click="router.get(route('reports.stock.index'))">
                                Kembali
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>


            <div class="max-w-8xl mx-auto">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="flex flex-wrap justify-end items-center gap-4 mb-4">
                            <div class="w-full sm:w-auto">
                                <VueDatePicker
                                    v-model="date"
                                    range
                                    :enable-time-picker="false"
                                    model-type="yyyy-MM-dd"
                                    format="yyyy-MM-dd"
                                    locale="id"
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-52">Tanggal</TableHead>
                                        <TableHead>Jenis</TableHead>
                                        <TableHead class="text-right">Kuantitas</TableHead>
                                        <TableHead>Sumber</TableHead>
                                        <TableHead>Keterangan</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow
                                        v-for="m in mutations.data"
                                        :key="m.id"
                                        class="hover:bg-muted/30"
                                    >
                                        <TableCell>{{ formatDate(m.created_at) }}</TableCell>
                                        <TableCell>
                                            <Badge :variant="m.type === 'in' ? 'outline' : 'destructive'">
                                                {{ m.type === 'in' ? 'Masuk' : 'Keluar' }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-right">{{ m.quantity }}</TableCell>
                                        <TableCell>{{ m.source_type || '-' }}</TableCell>
                                        <TableCell>{{ m.note || '-' }}</TableCell>
                                    </TableRow>

                                    <TableRow v-if="!mutations.total">
                                        <TableCell colspan="5" class="text-center text-muted-foreground py-4">
                                            Tidak ada mutasi pada periode ini.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <PageNav :data="mutations" />
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
