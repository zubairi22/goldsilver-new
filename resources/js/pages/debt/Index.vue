<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import Heading from '@/components/Heading.vue';
import { useFormat } from '@/composables/useFormat';
import type { BreadcrumbItem } from '@/types';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import ChevronButton from '@/components/ChevronButton.vue';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import CurrencyInput from '@/components/CurrencyInput.vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Piutang', href: '#' },
];

defineProps(['customers', 'invoices', 'paymentMethods']);

const { formatRupiah, formatDate } = useFormat();

const form = useForm({
    customer_id: '',
    settlement_amount: 0,
    payment_method_id: '',
});

const invoiceForm = useForm({
    transaction_id: '',
    due_date_days: 1,
});

const settlementModal = ref(false);
const detailModal = ref(false);
const invoiceModal = ref(false);
const invoiceListModal = ref(false);

const selectedPayment = ref();

const settleDebt = (customer: any) => {
    form.customer_id = customer.id;
    settlementModal.value = true;
};

const detailPayment = (transaction: any) => {
    selectedPayment.value = transaction;
    detailModal.value = true;
};

const generateInvoice = (transaction: any) => {
    invoiceForm.transaction_id = transaction.id;
    invoiceModal.value = true;
};

const openValue = ref<number[]>([]);

const toggleValue = (valueId: number) => {
    if (openValue.value.includes(valueId)) {
        openValue.value = openValue.value.filter((id: number) => id !== valueId);
    } else {
        openValue.value.push(valueId);
    }
};

const handleSettlement = () => {
    form.post(route('transaction.debt.settle', form.customer_id), {
        preserveScroll: true,
        onSuccess: () => {
            settlementModal.value = false;
            form.reset();
        },
    });
};

const handleGenerateInvoice = () => {
    invoiceForm.post(route('transaction.debt.invoice.generate', invoiceForm.transaction_id), {
        preserveScroll: true,
        onSuccess: () => {
            invoiceModal.value = false;
            invoiceForm.reset();
        },
    });
};

const cancelDebtModal = ref(false)

const cancelForm = useForm({
    transaction_id: '',
    reason: '',
    items: [] as {
        transaction_item_id: number
        product_name: string
        unit_name: string
        quantity: number
        cancel_qty: number
    }[],
})

const openCancelDebtModal = (trx: any) => {
    cancelForm.transaction_id = trx.id
    cancelForm.items = trx.items.map((it: any) => ({
        transaction_item_id: it.id,
        product_name: it.product.name,
        unit_name: it.unit.name,
        quantity: it.quantity,
        cancel_qty: 0,
    }))
    cancelDebtModal.value = true
}

const submitCancelDebt = () => {
    cancelForm.post(route('transaction.debt.cancel.item', cancelForm.transaction_id), {
        preserveScroll: true,
        onSuccess: () => {
            cancelDebtModal.value = false
            cancelForm.reset()
        },
    })
}

</script>

<template>
    <Head title="Piutang" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <div class="flex items-center justify-between">
                <Heading class="mx-4" title="Piutang" description="Riwayat transaksi yang masih berstatus belum lunas" />
                <Button class="mx-4" variant="outline" size="lg" @click="invoiceListModal = true"> Daftar Invoice </Button>
            </div>
            <div class="max-w-8xl mx-auto">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-8"></TableHead>
                                        <TableHead>Pelanggan</TableHead>
                                        <TableHead class="text-right">Total Piutang</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <template v-for="customer in customers.data" :key="customer.id">
                                        <TableRow>
                                            <TableCell>
                                                <ChevronButton
                                                    :isOpen="openValue.includes(customer.id)"
                                                    title="Transaksi"
                                                    @click="toggleValue(customer.id)"
                                                />
                                            </TableCell>
                                            <TableCell @click="toggleValue(customer.id)">
                                                {{ customer.name }}
                                            </TableCell>
                                            <TableCell class="text-right">
                                                {{ formatRupiah(customer.total_debt) }}
                                            </TableCell>
                                            <TableCell class="w-8 text-right">
                                                <Button @click="settleDebt(customer)">Bayar</Button>
                                            </TableCell>
                                        </TableRow>
                                        <TableRow v-if="openValue.includes(customer.id)">
                                            <TableCell />
                                            <TableCell colSpan="6">
                                                <Table class="w-full">
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead class="w-44">Kode</TableHead>
                                                            <TableHead>Tanggal</TableHead>
                                                            <TableHead class="text-right">Total</TableHead>
                                                            <TableHead class="text-right">Bayar</TableHead>
                                                            <TableHead class="text-right">Piutang</TableHead>
                                                            <TableHead class="w-8" />
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        <TableRow v-for="trx in customer.transactions" :key="trx.id">
                                                            <TableCell>
                                                                {{ trx.transaction_number }}
                                                            </TableCell>
                                                            <TableCell>
                                                                {{ formatDate(trx.created_at) }}
                                                            </TableCell>
                                                            <TableCell class="text-right">
                                                                {{ formatRupiah(trx.total_price) }}
                                                            </TableCell>
                                                            <TableCell class="text-right">
                                                                {{ formatRupiah(trx.paid_amount) }}
                                                            </TableCell>
                                                            <TableCell class="text-right text-red-600">
                                                                {{ formatRupiah(trx.total_price - trx.paid_amount) }}
                                                            </TableCell>
                                                            <TableCell class="px-2">
                                                                <DropdownMenu>
                                                                    <DropdownMenuTrigger as-child>
                                                                        <Button variant="secondary">
                                                                            <icon name="EllipsisVertical" />
                                                                        </Button>
                                                                    </DropdownMenuTrigger>
                                                                    <DropdownMenuContent class="w-40">
                                                                        <DropdownMenuItem @click="detailPayment(trx)">
                                                                            Detail Pembayaran
                                                                        </DropdownMenuItem>
                                                                        <DropdownMenuItem @click="openCancelDebtModal(trx)">
                                                                            Cancel Produk
                                                                        </DropdownMenuItem>
                                                                        <DropdownMenuItem @click="generateInvoice(trx)">
                                                                            Buat Invoice
                                                                        </DropdownMenuItem>
                                                                    </DropdownMenuContent>
                                                                </DropdownMenu>
                                                            </TableCell>
                                                        </TableRow>
                                                    </TableBody>
                                                </Table>
                                            </TableCell>
                                        </TableRow>
                                    </template>
                                    <TableRow v-if="!customers.total">
                                        <TableCell />
                                        <TableCell colspan="6">Belum ada piutang.</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="settlementModal" @update:open="(val) => (settlementModal = val)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Pembayaran Utang</DialogTitle>
                <DialogDescription>Masukkan jumlah pembayaran untuk piutang</DialogDescription>
            </DialogHeader>

            <div>
                <Label for="settlement_amount">Jumlah Pembayaran</Label>
                <CurrencyInput v-model="form.settlement_amount" required />
                <InputError class="mt-2" :message="form.errors.settlement_amount" />
            </div>

            <div>
                <Label for="method">Metode Pembayaran</Label>
                <Select v-model="form.payment_method_id">
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Pilih Metode Pembayaran" />
                    </SelectTrigger>
                    <SelectContent class="w-60">
                        <SelectGroup>
                            <SelectItem v-for="pm in paymentMethods" :key="pm.id" :value="pm.id">
                                {{ pm.name }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.payment_method_id" class="mt-1" />
            </div>

            <DialogFooter>
                <Button variant="secondary" @click="settlementModal = false">Batal</Button>
                <Button :disabled="form.processing" @click="handleSettlement">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Proses Pembayaran
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="detailModal" @update:open="(val) => (detailModal = val)">
        <DialogContent class="sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>Detail Pembayaran</DialogTitle>
            </DialogHeader>
            <DialogDescription>
                <div>
                    <p><strong>Kode Transaksi:</strong> {{ selectedPayment?.transaction_number }}</p>
                    <p><strong>Tanggal:</strong> {{ formatDate(selectedPayment?.created_at) }}</p>
                    <p><strong>Total Piutang:</strong> {{ formatRupiah(selectedPayment?.total_price) }}</p>
                    <p><strong>Jumlah Bayar:</strong> {{ formatRupiah(selectedPayment?.paid_amount) }}</p>
                    <p><strong>Sisa Piutang:</strong> {{ formatRupiah(selectedPayment?.total_price - selectedPayment?.paid_amount) }}</p>

                    <h3>Daftar Pembayaran:</h3>
                    <Table class="mt-2 border">
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-44">Jumlah Bayar</TableHead>
                                <TableHead>Tanggal Pembayaran</TableHead>
                                <TableHead>Catatan</TableHead>
                                <TableHead>Metode</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="payment in selectedPayment?.payments" :key="payment.id">
                                <TableCell>{{ formatRupiah(payment.amount) }}</TableCell>
                                <TableCell>{{ formatDate(payment.paid_at) }}</TableCell>
                                <TableCell>{{ payment.notes }}</TableCell>
                                <TableCell>{{ payment.payment_method?.name }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </DialogDescription>
            <DialogFooter>
                <Button variant="secondary" @click="detailModal = false">Tutup</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="invoiceModal" @update:open="(val) => (invoiceModal = val)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Buat Invoice</DialogTitle>
                <DialogDescription>Masukkan berapa hari invoice harus dilunasi</DialogDescription>
            </DialogHeader>

            <div>
                <Label for="settlement_amount">Tenor Invoice</Label>
                <Input v-model="invoiceForm.due_date_days" type="number" min="1" required />
                <InputError class="mt-2" :message="invoiceForm.errors.due_date_days" />
            </div>

            <DialogFooter>
                <Button variant="secondary" @click="invoiceModal = false">Batal</Button>
                <Button :disabled="invoiceForm.processing" @click="handleGenerateInvoice">
                    <LoaderCircle v-if="invoiceForm.processing" class="h-4 w-4 animate-spin" />
                    Buat Invoice
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="invoiceListModal" @update:open="(val) => (invoiceListModal = val)">
        <DialogContent class="sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>Daftar Invoice</DialogTitle>
            </DialogHeader>

            <div>
                <Table class="mt-2">
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-44">Nomor Invoice</TableHead>
                            <TableHead>Tanggal Jatuh Tempo</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="w-8" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="invoice in invoices" :key="invoice.id">
                            <TableCell>{{ invoice.invoice_number }}</TableCell>
                            <TableCell>{{ formatDate(invoice.due_date) }}</TableCell>
                            <TableCell>
                                <Badge :variant="invoice.status === 'paid' ? 'success' : invoice.status === 'unpaid' ? 'destructive' : 'warning'">
                                    {{ invoice.status }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Button>
                                    <a :href="route('transaction.debt.invoice.view', invoice)" target="_blank"> Unduh Invoice </a>
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <DialogFooter>
                <Button variant="secondary" @click="invoiceListModal = false">Tutup</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="cancelDebtModal" @update:open="(v) => (cancelDebtModal = v)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Cancel Item Piutang</DialogTitle>
                <DialogDescription>Pilih item dan jumlah yang ingin dibatalkan.</DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div class="overflow-x-auto">
                    <Table class="w-full">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Produk</TableHead>
                                <TableHead class="text-center w-20">Qty</TableHead>
                                <TableHead class="text-right w-48">Qty Cancel</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(it, idx) in cancelForm.items" :key="it.transaction_item_id">
                                <TableCell>
                                    <div class="font-medium">{{ it.product_name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ it.unit_name }}</div>
                                </TableCell>
                                <TableCell class="text-center">
                                    {{ it.quantity }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="inline-flex items-center justify-center gap-2" @click.stop>
                                        <Button
                                            size="icon"
                                            class="h-7 w-7"
                                            @click.stop="cancelForm.items[idx].cancel_qty = Math.max(0, (cancelForm.items[idx].cancel_qty || 0) - 1)"
                                        >
                                            -
                                        </Button>

                                        <Input
                                            type="number"
                                            min="0"
                                            class="text-center"
                                            :max="it.quantity"
                                            v-model.number="cancelForm.items[idx].cancel_qty"
                                        />

                                        <Button
                                            size="icon"
                                            class="h-7 w-7"
                                            @click.stop="cancelForm.items[idx].cancel_qty = Math.min((cancelForm.items[idx].cancel_qty || 0) + 1, it.quantity)"
                                        >
                                            +
                                        </Button>
                                    </div>
                                    <InputError :message="(cancelForm.errors as any)[`items.${idx}.quantity`]" class="mt-1" />
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="!cancelForm.items.length">
                                <TableCell colspan="6">Tidak ada item.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div>
                    <Label>Alasan Cancel (opsional)</Label>
                    <Textarea rows="3" v-model="cancelForm.reason" />
                    <InputError :message="cancelForm.errors.reason" class="mt-1" />
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="cancelDebtModal = false">Batal</Button>
                <Button :disabled="cancelForm.processing || !cancelForm.items.some(i => i.cancel_qty > 0)" @click="submitCancelDebt">
                    <LoaderCircle v-if="cancelForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Konfirmasi Cancel
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
