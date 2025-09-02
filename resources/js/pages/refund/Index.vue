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
import { useFormat } from '@/composables/useFormat';
import type { BreadcrumbItem } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from '@/components/ui/dialog';
import axios from 'axios';
import { useBluetoothPrinter } from '@/composables/useBluetoothPrinter';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Refund', href: '#' },
];

defineProps(['refunds']);

const { formatRupiah, formatDate } = useFormat();
const { search } = useSearch('transaction.refunds.index', '', ['refunds']);

const detailModal = ref(false);
const selectedRefund = ref<any>(null);

function openRefundModal(rf: any) {
    selectedRefund.value = rf;
    detailModal.value = true;
}
</script>

<template>
    <Head title="Refund" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Refund" description="Riwayat transaksi yang di refund" />
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
                                        <TableHead class="w-40">Tgl Refund</TableHead>
                                        <TableHead>No Refund</TableHead>
                                        <TableHead>No Transaksi</TableHead>
                                        <TableHead class="text-center">Diproses Oleh</TableHead>
                                        <TableHead class="text-center">Akun Keuangan</TableHead>
                                        <TableHead class="text-center">Item</TableHead>
                                        <TableHead class="text-center">Total Refund</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="rf in refunds.data" :key="rf.id" @click="openRefundModal(rf)">
                                        <TableCell class="align-top">
                                            {{ formatDate(rf.refunded_at, 'dd MMM yyyy HH:mm') }}
                                        </TableCell>
                                        <TableCell class="align-top">{{ rf.refund_number }}</TableCell>
                                        <TableCell class="align-top">{{ rf.transaction?.transaction_number }}</TableCell>
                                        <TableCell class="text-center">{{ rf.user.name || '-' }}</TableCell>
                                        <TableCell class="text-center"><Badge>{{ rf.financial_account.name }}</Badge></TableCell>
                                        <TableCell class="text-center align-top">{{ rf.items_count }} ({{ rf.total_qty || 0 }})</TableCell>
                                        <TableCell class="text-center">{{ formatRupiah(rf.total_amount) }}</TableCell>
                                    </TableRow>

                                    <TableRow v-if="!refunds.total">
                                        <TableCell colspan="7">Belum ada refund.</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <PageNav :data="refunds" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="detailModal" @update:open="(val) => detailModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ selectedRefund?.refund_number }}</DialogTitle>
                <DialogDescription class="space-y-1">
                    <div>Tgl: {{ formatDate(selectedRefund?.refunded_at, 'dd MMM yyyy HH:mm') }}</div>
                    <div>Transaksi: {{ selectedRefund?.transaction?.transaction_number }}</div>
                    <div>Diproses oleh: {{ selectedRefund?.user?.name || '-' }}</div>
                    <div>Akun Keuangan: {{ selectedRefund?.financial_account.name }} <span v-if="selectedRefund?.external_reference">({{ selectedRefund?.external_reference }})</span></div>
                    <div v-if="selectedRefund?.reason">Alasan: {{ selectedRefund?.reason }}</div>
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div>
                    <Table class="w-full">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Produk</TableHead>
                                <TableHead class="text-right">Qty</TableHead>
                                <TableHead class="text-right">Subtotal</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="ri in selectedRefund?.items" :key="ri.id">
                                <TableCell>{{ ri.transaction_item?.product?.name }} ({{ ri.transaction_item?.unit?.name }})</TableCell>
                                <TableCell class="text-right">x{{ ri.quantity }}</TableCell>
                                <TableCell class="text-right">{{ formatRupiah(ri.amount) }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div class="border-t pt-3 text-sm space-y-1">
                    <div class="flex justify-between"><span>Total Refund:</span><span class="font-semibold">{{ formatRupiah(selectedRefund?.total_amount || 0) }}</span></div>
                    <div class="flex justify-between"><span>Sudah Refund (akumulasi):</span><span>{{ formatRupiah(selectedRefund?.transaction?.refunded_total || 0) }}</span></div>
                    <div class="flex justify-between"><span>Total Transaksi (gross):</span><span>{{ formatRupiah(selectedRefund?.transaction?.total_price || 0) }}</span></div>
                    <div class="flex justify-between font-semibold"><span>Sisa Refundable:</span><span>{{ formatRupiah(Math.max(0, (selectedRefund?.transaction?.total_price || 0) - (selectedRefund?.transaction?.refunded_total || 0))) }}</span></div>
                </div>
            </div>

            <DialogFooter class="mt-6 gap-2">
                <Button variant="secondary" @click="detailModal=false">Tutup</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
