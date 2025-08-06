<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
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
import { Textarea } from '@/components/ui/textarea';
import { ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import axios from 'axios';
import { useBluetoothPrinter } from '@/composables/useBluetoothPrinter';
import { LoaderCircle } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Penjualan', href: '#' },
];

defineProps(['sales']);

const { formatRupiah } = useFormat();
const { connectPrinter, printText, isConnected } = useBluetoothPrinter()
const { search } = useSearch('transaction.sales.index', '', ['sales']);

const detailModal = ref(false);
const refundModal = ref(false);
const selectedTransaction = ref<any>(null);

function openTransactionModal(trx: any) {
    selectedTransaction.value = trx;
    detailModal.value = true;
}

const refundForm = useForm({
    refund_amount: '',
    refund_reason: ''
});

function openRefundFromDetail(trx: any) {
    selectedTransaction.value = trx;
    refundForm.refund_amount = trx.total_price;
    refundForm.refund_reason = '';
    detailModal.value = false;
    refundModal.value = true;
}

function submitRefund() {
    if (!selectedTransaction.value) return;

    refundForm.post(route('sales.refund', selectedTransaction.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            refundModal.value = false;
            selectedTransaction.value = null;
        },
    });
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
                                        <TableHead class="text-right">Total</TableHead>
                                        <TableHead class="text-right">Bayar</TableHead>
                                        <TableHead class="text-right">Diskon</TableHead>
                                        <TableHead class="text-right">Kembali</TableHead>
                                        <TableHead>Metode</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="trx in sales.data" :key="trx.id" @click="openTransactionModal(trx)">
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
                                            {{ formatRupiah(trx.discount_amount) }}
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

    <Dialog :open="detailModal" @update:open="(val) => detailModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Detail Transaksi</DialogTitle>
                <DialogDescription>
                    No: {{ selectedTransaction?.transaction_number }}<br>
                    Tanggal: {{ format(new Date(selectedTransaction?.created_at), 'dd MMM yyyy HH:mm', { locale: id }) }}
                </DialogDescription>

                <Button variant="secondary" @click="printReceipt(selectedTransaction)">Cetak Struk</Button>
            </DialogHeader>

            <div class="space-y-4">
                <div class="text-sm capitalize">
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
                    <div class="flex justify-between"><span>Total :</span><span>{{ formatRupiah(selectedTransaction?.total_price) }}</span></div>
                    <div class="flex justify-between"><span>Dibayar :</span><span>{{ formatRupiah(selectedTransaction?.paid_amount) }}</span></div>
                    <div class="flex justify-between"><span>Diskon :</span><span>{{ formatRupiah(selectedTransaction?.discount_amount) }}</span></div>
                    <div class="flex justify-between"><span>Kembali :</span><span>{{ formatRupiah(selectedTransaction?.change_amount) }}</span></div>
                </div>
            </div>

            <DialogFooter class="mt-6 gap-2">
                <Button variant="secondary" @click="detailModal = false">Tutup</Button>
                <Button
                    v-if="!selectedTransaction?.is_refunded"
                    variant="destructive"
                    @click="openRefundFromDetail(selectedTransaction)"
                >
                    Refund
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>


    <Dialog :open="refundModal" @update:open="(val) => refundModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Refund Transaksi</DialogTitle>
                <DialogDescription>Masukkan alasan dan jumlah refund.</DialogDescription>
            </DialogHeader>
            <div class="space-y-4">
                <div>
                    <Label>Jumlah Refund</Label>
                    <Input type="number" min="0" v-model="refundForm.refund_amount" />
                </div>
                <div>
                    <Label>Alasan Refund</Label>
                    <Textarea rows="3" v-model="refundForm.refund_reason" />
                </div>
            </div>
            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="refundModal = false">Batal</Button>
                <Button
                    :disabled="refundForm.processing"
                    @click="submitRefund"
                >
                    <LoaderCircle v-if="refundForm.processing" class="h-4 w-4 animate-spin mr-2" />
                    Konfirmasi Refund
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

</template>
