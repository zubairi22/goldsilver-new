<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import PageNav from '@/components/PageNav.vue';
import Heading from '@/components/Heading.vue';
import SearchInput from '@/components/SearchInput.vue';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { useSearch } from '@/composables/useSearch';
import { useCurrency } from '@/composables/useCurrency';
import type { BreadcrumbItem } from '@/types';
import { Badge } from '@/components/ui/badge';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Penjualan', href: '#' },
];

defineProps(['sales']);

const { formatRupiah } = useCurrency();
const { search } = useSearch('sales.index', '', ['sales']);
</script>

<template>
    <Head title="Penjualan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Penjualan" description="Riwayat transaksi berhasil yang sudah terjadi" />
            <div class="mx-auto max-w-8xl">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="flex flex-col justify-between md:flex-row mb-2">
                            <div />
                            <div class="mb-3 md:text-right">
                                <SearchInput v-model="search" />
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-44">Tanggal</TableHead>
                                        <TableHead>Kasir</TableHead>
                                        <TableHead>Produk</TableHead>
                                        <TableHead class="text-right">Total</TableHead>
                                        <TableHead class="text-right">Bayar</TableHead>
                                        <TableHead class="text-right">Kembali</TableHead>
                                        <TableHead>Metode</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="trx in sales.data" :key="trx.id">
                                        <TableCell class="align-top">
                                            {{ format(new Date(trx.created_at), 'dd MMM yyyy HH:mm', { locale: id }) }}
                                        </TableCell>
                                        <TableCell class="align-top">
                                            {{ trx.user?.name || '-' }}
                                        </TableCell>
                                        <TableCell class="align-top p-2">
                                            <ul class="space-y-1">
                                                <li
                                                    v-for="item in trx.items"
                                                    :key="item.id"
                                                    class="flex justify-between gap-2 text-sm text-gray-700"
                                                >
                                                    <span>
                                                        {{ item.product?.name || '-' }} ({{ item.unit?.name }})
                                                    </span>
                                                    <span>
                                                        x{{ item.quantity }}
                                                    </span>
                                                </li>
                                            </ul>
                                        </TableCell>
                                        <TableCell class="text-right align-top">
                                            {{ formatRupiah(trx.total_price) }}
                                        </TableCell>
                                        <TableCell class="text-right align-top">
                                            {{ formatRupiah(trx.paid_amount) }}
                                        </TableCell>
                                        <TableCell class="text-right align-top">
                                            {{ formatRupiah(trx.change_amount) }}
                                        </TableCell>
                                        <TableCell class="align-top">
                                            <Badge>{{ trx.payment_method || '-' }}</Badge>
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="!sales.total">
                                        <TableCell colspan="7">Belum ada penjualan.</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>

                            <PageNav :data="sales" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
