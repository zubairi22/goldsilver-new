<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import PageNav from '@/components/PageNav.vue';
import Heading from '@/components/Heading.vue';
import SearchInput from '@/components/SearchInput.vue';
import { useSearch } from '@/composables/useSearch';
import { useFormat } from '@/composables/useFormat';
import type { BreadcrumbItem } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { computed, ref, watch } from 'vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import axios from 'axios';
import { useBluetoothPrinter } from '@/composables/useBluetoothPrinter';
import { LoaderCircle } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Penjualan', href: '#' },
];

defineProps(['sales', 'paymentMethods', 'financialAccounts']);

const { formatRupiah, formatDate } = useFormat();
const { connectPrinter, printText, isConnected } = useBluetoothPrinter();
const { search } = useSearch('transaction.sales.index', route().params.search, ['sales']);

const detailModal = ref(false);
const refundModal = ref(false);
const selectedTransaction = ref<any>(null);

const payment_method_id = ref<any>('all');
const mode = ref<any>('all');
const date = ref();

const applyFilters = () => {
    const params: Record<string, string> = {};

    if (payment_method_id.value !== 'all') {
        params.payment_method_id = payment_method_id.value;
    }

    if (date.value && date.value[0] && date.value[1]) {
        params.mode = 'custom';
        params.start = date.value[0];
        params.end = date.value[1];
    } else {
        params.mode = mode.value;
    }

    if (search.value) {
        params.search = search.value;
    }

    router.get(route('transaction.sales.index'), params, {
        preserveScroll: true,
        preserveState: true,
    });
};

watch([payment_method_id, mode, date], applyFilters);

function openTransactionModal(trx: any) {
    selectedTransaction.value = trx;
    detailModal.value = true;
}

const net = (trx: any) => Math.max(0, (trx.total_price || 0) - (trx.refunded_total || 0));

const refundForm = useForm({
    reason: '',
    financial_account_id: 1,
    external_reference: '',
    items: <any>[],
});

function openRefundFromDetail(trx: any) {
    selectedTransaction.value = trx;

    refundForm.reset();
    refundForm.clearErrors();

    refundForm.reason = '';
    refundForm.financial_account_id = 1;
    refundForm.external_reference = '';
    refundForm.items = (trx.items || []).map((it: any) => ({
        transaction_item_id: it.id,
        product_name: it.product?.name,
        unit_name: it.unit?.name,
        quantity_sold: it.quantity,
        refunded_qty: it.refunded_qty || 0,
        subtotal: it.subtotal,
        refund_qty: 0,
    }));

    detailModal.value = false;
    refundModal.value = true;
}

const estimateTotalRefund = computed(() => {
    return refundForm.items.reduce((sum: any, it: any) => {
        const rest = Math.max(0, it.quantity_sold - (it.refunded_qty || 0));
        const qty = Math.max(0, Math.min(it.refund_qty || 0, rest));
        const unitNet = Math.round((it.subtotal || 0) / Math.max(1, it.quantity_sold));
        return sum + unitNet * qty;
    }, 0);
});

function submitRefund() {
    if (!selectedTransaction.value) return;

    refundForm
        .transform((data) => {
            const items = (data.items || [])
                .filter((it: any) => (it.refund_qty || 0) > 0)
                .map((it: any) => ({
                    transaction_item_id: it.transaction_item_id,
                    quantity: it.refund_qty,
                }));
            return {
                reason: data.reason,
                financial_account_id: data.financial_account_id,
                external_reference: data.external_reference || undefined,
                items,
            };
        })
        .post(route('transaction.refunds.store', selectedTransaction.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                refundModal.value = false;
                selectedTransaction.value = null;
            },
        });
}

async function printReceipt(trx: any) {
    try {
        const response = await axios.get(`/api/receipt/${trx.transaction_number}`);
        const receipt = response.data.receipt;

        const connected = isConnected.value || (await connectPrinter());
        if (!connected) return alert('Gagal konek ke printer');

        await printText(receipt);
    } catch (err) {
        console.error('Error saat cetak struk:', err);
        alert('Gagal mengambil atau mencetak struk');
    }
}
</script>

<template>
    <Head title="Penjualan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Penjualan" description="Riwayat transaksi berhasil yang sudah terjadi" />
            <div class="max-w-8xl mx-auto">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                            <div class="w-52">
                                <Select v-model="payment_method_id">
                                    <SelectTrigger id="payment_method_id">
                                        <SelectValue placeholder="Pilih Metode Pembayaran" />
                                    </SelectTrigger>
                                    <SelectContent class="w-52">
                                        <SelectGroup>
                                            <SelectItem value="all">Semua</SelectItem>
                                            <SelectItem
                                                v-for="pm in paymentMethods"
                                                :key="pm.id"
                                                :value="pm.id"
                                            >
                                                {{ pm.name }}
                                            </SelectItem>
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
                                                <SelectItem value="all">Semua</SelectItem>
                                                <SelectItem value="daily">Hari Ini</SelectItem>
                                                <SelectItem value="weekly">Minggu Ini</SelectItem>
                                                <SelectItem value="monthly">Bulan Ini</SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <VueDatePicker v-model="date" range :enable-time-picker="false" model-type="yyyy-MM-dd" locale="id" />
                                </div>
                            </div>
                        </div>
                        <div class="mb-2 flex flex-col justify-between md:flex-row">
                            <div />
                            <div class="mb-3 md:text-right">
                                <SearchInput v-model:search="search" />
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-44">Tanggal</TableHead>
                                        <TableHead>Kasir</TableHead>
                                        <TableHead class="text-right">Total</TableHead>
                                        <TableHead class="text-right">Refund</TableHead>
                                        <TableHead class="text-right">Net</TableHead>
                                        <TableHead class="text-right">Bayar</TableHead>
                                        <TableHead class="text-right">Diskon</TableHead>
                                        <TableHead class="text-right">Kembali</TableHead>
                                        <TableHead class="text-center">Metode</TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow v-for="trx in sales.data" :key="trx.id" @click="openTransactionModal(trx)">
                                        <TableCell class="align-top">
                                            {{ formatDate(trx.created_at, 'dd MMM yyyy HH:mm')}}
                                        </TableCell>

                                        <TableCell class="align-top">
                                            {{ trx.user?.name || '-' }}
                                        </TableCell>

                                        <TableCell class="text-right align-top">
                                            {{ formatRupiah(trx.total_price) }}
                                        </TableCell>

                                        <TableCell
                                            class="text-right align-top"
                                            :class="(trx.refunded_total || 0) > 0 ? 'font-medium text-destructive' : ''"
                                        >
                                            {{ formatRupiah(trx.refunded_total || 0) }}
                                        </TableCell>

                                        <TableCell class="text-right align-top font-semibold">
                                            {{ formatRupiah(net(trx)) }}
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

                                        <TableCell class="text-center align-top">
                                            <Badge>{{ trx.payment_method?.name }}</Badge>
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="!sales.total">
                                        <TableCell colspan="10">Belum ada penjualan.</TableCell>
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

    <Dialog :open="detailModal" @update:open="(val) => (detailModal = val)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Detail Transaksi</DialogTitle>
                <DialogDescription>
                    No: {{ selectedTransaction?.transaction_number }}<br />
                    Tanggal: {{ formatDate(selectedTransaction?.created_at, 'dd MMM yyyy HH:mm') }}
                </DialogDescription>

                <Button variant="secondary" @click="printReceipt(selectedTransaction)">Cetak Struk</Button>
            </DialogHeader>

            <div class="space-y-4">
                <div class="text-sm capitalize">
                    <p><strong>Kasir : </strong> {{ selectedTransaction?.user?.name }}</p>
                    <p><strong>Metode Pembayaran : </strong> {{ selectedTransaction?.payment_method?.name }}</p>
                    <p class="mt-1">
                        <strong>Status Refund : </strong>
                        <Badge
                            :variant="
                                selectedTransaction?.refund_status === 'full'
                                    ? 'destructive'
                                    : selectedTransaction?.refund_status === 'partial'
                                      ? 'secondary'
                                      : 'outline'
                            "
                        >
                            {{ selectedTransaction?.refund_status || 'none' }}
                        </Badge>
                    </p>
                </div>
                <div>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Produk</TableHead>
                                <TableHead class="text-right">Qty</TableHead>
                                <TableHead class="text-right">Subtotal</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in selectedTransaction?.items" :key="item.id">
                                <TableCell>
                                    {{ item.product?.name }} ({{ item.unit?.name }})
                                    <span v-if="item.refunded_qty && Number(item.refunded_qty) > 0" class="ml-2 text-red-500 text-xs">
        - {{ item.refunded_qty }} retur
      </span>
                                </TableCell>
                                <TableCell class="text-right">
      <span v-if="Number(item.refunded_qty) >= Number(item.quantity)" class="line-through text-gray-400">
        x{{ item.quantity }}
      </span>
                                    <span v-else>
        x{{ item.quantity }}
      </span>
                                </TableCell>
                                <TableCell class="text-right">
      <span v-if="Number(item.refunded_qty) >= Number(item.quantity)" class="line-through text-gray-400">
        {{ formatRupiah(item.subtotal) }}
      </span>
                                    <span v-else>
        {{ formatRupiah(item.subtotal) }}
      </span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div class="space-y-1 border-t pt-4 text-sm">
                    <div class="flex justify-between">
                        <span>Total :</span><span>{{ formatRupiah(selectedTransaction?.total_price) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Dibayar :</span><span>{{ formatRupiah(selectedTransaction?.paid_amount) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Diskon :</span><span>{{ formatRupiah(selectedTransaction?.discount_amount) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Kembali :</span><span>{{ formatRupiah(selectedTransaction?.change_amount) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Refund :</span><span>{{ formatRupiah(selectedTransaction?.refunded_total) }}</span>
                    </div>
                </div>
            </div>

            <DialogFooter class="mt-6 gap-2">
                <Button variant="secondary" @click="detailModal = false">Tutup</Button>
                <Button
                    v-if="selectedTransaction && selectedTransaction.refund_status !== 'full'"
                    variant="destructive"
                    @click="openRefundFromDetail(selectedTransaction)"
                >
                    Refund
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="refundModal" @update:open="(v) => (refundModal = v)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Refund Item</DialogTitle>
                <DialogDescription>Pilih item dan jumlah yang ingin direfund.</DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div class="overflow-x-auto">
                    <Table class="w-full">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Produk</TableHead>
                                <TableHead class="text-center w-20">Qty</TableHead>
                                <TableHead class="text-right w-48">Qty Refund</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(it, idx) in refundForm.items" :key="it.transaction_item_id">
                                <TableCell>
                                    <div class="font-medium">{{ it.product_name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ it.unit_name }}</div>
                                </TableCell>
                                <TableCell class="text-center">
                                    {{ Math.max(0, it.quantity_sold - (it.refunded_qty || 0)) }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="inline-flex items-center justify-center gap-2" @click.stop>
                                        <Button
                                            size="icon"
                                            class="h-7 w-7"
                                            @click.stop="refundForm.items[idx].refund_qty = Math.max(0, (refundForm.items[idx].refund_qty || 0) - 1)"
                                        >
                                            -
                                        </Button>

                                        <Input
                                            type="number"
                                            min="0"
                                            class="text-center"
                                            :max="Math.max(0, it.quantity_sold - (it.refunded_qty || 0))"
                                            v-model.number="refundForm.items[idx].refund_qty"
                                        />

                                        <Button
                                            size="icon"
                                            class="h-7 w-7"
                                            @click.stop="refundForm.items[idx].refund_qty = Math.min((refundForm.items[idx].refund_qty || 0) + 1,Math.max(0, it.quantity_sold - (it.refunded_qty || 0)))"
                                        >
                                            +
                                        </Button>
                                    </div>
                                    <InputError :message="(refundForm.errors as any)[`items.${idx}.quantity`]" class="mt-1" />
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="!refundForm.items.length">
                                <TableCell colspan="6">Tidak ada item.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div class="grid gap-3 md:grid-cols-3">
                    <div class="md:col-span-1">
                        <Label>Akun Keuangan</Label>
                        <Select class="w-42" v-model="refundForm.financial_account_id">
                            <SelectTrigger id="refund">
                                <SelectValue placeholder="Pilih Akun Keuangan" />
                            </SelectTrigger>
                            <SelectContent class="w-42">
                                <SelectGroup>
                                    <SelectItem
                                        v-for="fa in financialAccounts"
                                        :key="fa.id"
                                        :value="fa.id"
                                    >
                                        {{ fa.name }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <InputError :message="refundForm.errors.financial_account_id" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <Label>Referensi Eksternal (opsional)</Label>
                        <Input class="h-10" v-model="refundForm.external_reference" placeholder="No. transfer / ref e-wallet" />
                        <InputError :message="refundForm.errors.external_reference" class="mt-1" />
                    </div>
                </div>

                <div>
                    <Label>Alasan Refund (umum)</Label>
                    <Textarea rows="3" v-model="refundForm.reason" />
                    <InputError :message="refundForm.errors.reason" class="mt-1" />
                </div>

                <div class="flex items-center justify-between border-t pt-3">
                    <span class="font-semibold">Total Refund</span>
                    <span class="text-right font-bold">{{ formatRupiah(estimateTotalRefund) }}</span>
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="refundModal = false">Batal</Button>
                <Button :disabled="refundForm.processing || estimateTotalRefund <= 0" @click="submitRefund">
                    <LoaderCircle v-if="refundForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Konfirmasi Refund
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
