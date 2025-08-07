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
import { useForm } from '@inertiajs/vue3'
import { LoaderCircle } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';
import Icon from '@/components/Icon.vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Piutang', href: '#' },
];

defineProps(['customers']);

const { formatRupiah, formatDate } = useFormat();

const form = useForm({
    customer_id : '',
    settlement_amount: 0,
})

const settlementModal = ref(false);
const detailModal = ref(false);
const selectedPayment = ref()

const settleDebt = (customer: any) => {
    form.customer_id = customer.id;
    settlementModal.value = true
}

const detailPayment = (transaction: any) => {
    selectedPayment.value = transaction;
    detailModal.value = true
}

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

</script>

<template>
    <Head title="Piutang" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Piutang" description="Riwayat transaksi yang masih berstatus belum lunas" />
            <div class="mx-auto max-w-8xl">
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
                                                {{ customer.name}}
                                            </TableCell>
                                            <TableCell class="text-right">
                                                {{ formatRupiah(customer.total_debt) }}
                                            </TableCell>
                                            <TableCell class="text-right w-8">
                                                <Button @click="settleDebt(customer)">Bayar</Button>
                                            </TableCell>
                                        </TableRow>
                                        <TableRow v-if="openValue.includes(customer.id)">
                                            <TableCell/>
                                            <TableCell colSpan="6">
                                                <Table class="w-full">
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead class="w-44">Kode</TableHead>
                                                            <TableHead>Tanggal</TableHead>
                                                            <TableHead class="text-right">Total</TableHead>
                                                            <TableHead class="text-right">Bayar</TableHead>
                                                            <TableHead class="text-right">Piutang</TableHead>
                                                            <TableHead class="w-8"/>
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
                                                                            <icon name="EllipsisVertical"/>
                                                                        </Button>
                                                                    </DropdownMenuTrigger>
                                                                    <DropdownMenuContent class="w-40">
                                                                        <DropdownMenuItem>
                                                                            <span @click="detailPayment(trx)">Detail Pembayaran</span>
                                                                        </DropdownMenuItem>
                                                                        <a target="_blank" :href="route('transaction.debt.invoice', trx)">
                                                                            <DropdownMenuItem>
                                                                                Invoice
                                                                            </DropdownMenuItem>
                                                                        </a>
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
                                        <TableCell/>
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

    <Dialog :open="settlementModal" @update:open="(val) => settlementModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Pembayaran Utang</DialogTitle>
                <DialogDescription>Masukkan jumlah pembayaran untuk piutang</DialogDescription>
            </DialogHeader>

            <div>
                <Label for="settlement_amount">Jumlah Pembayaran</Label>
                <Input v-model="form.settlement_amount" type="number" min="1" required />
                <InputError class="mt-2" :message="form.errors.settlement_amount" />
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

    <Dialog :open="detailModal" @update:open="(val) => detailModal = val">
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
                    <Table class="border mt-2">
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-44">Jumlah Bayar</TableHead>
                                <TableHead>Tanggal Pembayaran</TableHead>
                                <TableHead>Catatan</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="payment in selectedPayment?.payments" :key="payment.id">
                                <TableCell>{{ formatRupiah(payment.amount) }}</TableCell>
                                <TableCell>{{ formatDate(payment.paid_at) }}</TableCell>
                                <TableCell>{{ payment.notes }}</TableCell>
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

</template>
