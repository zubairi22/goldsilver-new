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

const { formatRupiah } = useFormat();
const { connectPrinter, printText, isConnected } = useBluetoothPrinter()
const { search } = useSearch('transaction.refunds.index', '', ['refunds']);

const detailModal = ref(false);
const selectedTransaction = ref<any>(null);

function openTransactionModal(trx: any) {
    selectedTransaction.value = trx;
    detailModal.value = true;
}

async function printReceipt(trx: any) {
    try {
        const response = await axios.get(`/api/receipt/${trx.transaction_number}`)
        const receipt = response.data.receipt;

        const connected = isConnected.value || await connectPrinter()
        if (!connected) return alert('Gagal konek ke printer')

        await printText(receipt)
    } catch (err) {
        console.error('Error saat cetak struk:', err)
        alert('Gagal mengambil atau mencetak struk')
    }
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
                                        <TableHead class="w-44">Tanggal</TableHead>
                                        <TableHead>Kasir</TableHead>
                                        <TableHead class="text-right">Total</TableHead>
                                        <TableHead class="text-right">Bayar</TableHead>
                                        <TableHead class="text-right">Kembali</TableHead>
                                        <TableHead>Metode</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="trx in refunds.data" :key="trx.id" @click="openTransactionModal(trx)">
                                        <TableCell class="align-top">
                                            {{ format(new Date(trx.created_at), 'dd MMM yyyy HH:mm', { locale: id }) }}
                                        </TableCell>
                                        <TableCell class="align-top">
                                            {{ trx.user?.name || '-' }}
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
        <DialogContent @interactOutside.prevent>
            <DialogHeader>
                <DialogTitle>Detail Transaksi</DialogTitle>
                <DialogDescription>
                    No: {{ selectedTransaction?.transaction_number }}<br>
                    Tanggal: {{ format(new Date(selectedTransaction?.created_at), 'dd MMM yyyy HH:mm', { locale: id }) }}
                </DialogDescription>

                <Button variant="secondary" @click="printReceipt(selectedTransaction)">Cetak Struk</Button>
            </DialogHeader>

            <div class="space-y-4">
                <div class="text-sm">
                    <p><strong>Kasir : </strong> {{ selectedTransaction?.user?.name }}</p>
                    <p><strong>Metode Pembayaran : </strong> {{ selectedTransaction?.payment_method }}</p>
                </div>

                <div>
                    <p class="font-semibold mb-1">Produk:</p>
                    <ul class="text-sm space-y-1">
                        <li v-for="item in selectedTransaction?.items" :key="item.id" class="flex justify-between">
                            <span>{{ item.product?.name }} ({{ item.unit?.name }})</span>
                            <span>x{{ item.quantity }}</span>
                        </li>
                    </ul>
                </div>

                <div class="border-t pt-4 space-y-1 text-sm">
                    <div class="flex justify-between"><span>Total:</span><span>{{ formatRupiah(selectedTransaction?.total_price) }}</span></div>
                    <div class="flex justify-between"><span>Dibayar:</span><span>{{ formatRupiah(selectedTransaction?.paid_amount) }}</span></div>
                    <div class="flex justify-between"><span>Kembali:</span><span>{{ formatRupiah(selectedTransaction?.change_amount) }}</span></div>
                </div>
            </div>

            <DialogFooter class="mt-6 gap-2">
                <Button variant="secondary" @click="detailModal = false">Tutup</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
