<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import Heading from '@/components/Heading.vue';
import type { BreadcrumbItem } from '@/types';
import { useFormat } from '@/composables/useFormat';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTitle, DialogFooter, DialogHeader } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import { LoaderCircle } from 'lucide-vue-next';
import CurrencyInput from '@/components/CurrencyInput.vue';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

const { customer } = defineProps(['customer', 'deposits', 'financialAccounts']);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pelanggan', href: '/outlet/customers' },
    { title: 'Detail Deposit', href: '/#' },
];

const { formatDate, formatRupiah } = useFormat();

const form = useForm({
    amount: 0,
    description: '',
    financial_account_id: 1,
    external_reference: '',
});

const refundForm = useForm({
    amount: 0,
    description: '',
    financial_account_id: 1,
    external_reference: '',
});

const addModal = ref(false);
const refundModal = ref(false);

const handleDeposit = () => {
    form.post(route('outlet.customer.deposit.store', customer.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            addModal.value = false;
        },
    });
};

const handleRefund = () => {
    refundForm.post(route('outlet.customer.deposit.refund', customer.id), {
        preserveScroll: true,
        onSuccess: () => {
            refundForm.reset();
            refundModal.value = false;
        },
    });
};

</script>

<template>
    <Head title="Detail Deposit" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Detail Deposit" :description="'Daftar Riwayat Deposit dari ' + customer.name" />

            <div class="max-w-8xl mx-auto">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="mb-2 flex flex-col justify-between md:flex-row">
                            <div class="mb-3 md:mb-0">
                                <Button @click="addModal = true">Tambah Deposit</Button>
                            </div>
                            <div class="mb-3">
                                <Button variant="secondary" @click="refundModal = true">Refund Deposit</Button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-20 text-center">#</TableHead>
                                        <TableHead>Tanggal</TableHead>
                                        <TableHead class="text-center">Jenis</TableHead>
                                        <TableHead class="text-center">Nominal</TableHead>
                                        <TableHead class="text-center">Akun Keuangan</TableHead>
                                        <TableHead>Deskripsi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="(deposit, index) in deposits.data" :key="deposit.id">
                                        <TableCell class="text-center">{{ index + 1 }}</TableCell>
                                        <TableCell>{{ formatDate(deposit.created_at, 'dd MMM yyyy HH:mm') }}</TableCell>
                                        <TableCell class="text-center">
                                            <Badge
                                                :variant="deposit.type === 'top_up' ? 'success' : deposit.type === 'used' ? 'warning' : 'destructive'"
                                            >
                                                {{ deposit.type === 'top_up' ? 'Top Up' : deposit.type === 'used' ? 'Digunakan' : 'Refund' }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-center font-bold">
                                            {{ formatRupiah(deposit.amount) }}
                                        </TableCell>
                                        <TableCell class="text-center">
                                            {{ deposit.financial_account.name }}
                                            <template v-if="deposit.external_reference">
                                                (Ref: {{ deposit.external_reference }})
                                            </template>
                                        </TableCell>
                                        <TableCell>{{ deposit.description || '-' }}</TableCell>
                                    </TableRow>
                                    <TableRow v-if="!deposits.data.length">
                                        <TableCell colspan="5" class="text-center text-gray-500"> Belum ada riwayat deposit. </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="addModal" @update:open="(val) => (addModal = val)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tambah Deposit</DialogTitle>
            </DialogHeader>

            <div class="space-y-4">
                <div>
                    <Label for="amount">Jumlah Deposit</Label>
                    <CurrencyInput v-model="form.amount" />
                    <InputError :message="form.errors.amount" />
                </div>

                <div class="grid gap-3 md:grid-cols-3">
                    <div class="md:col-span-1">
                        <Label>Akun Keuangan</Label>
                        <Select class="w-42" v-model="form.financial_account_id">
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
                        <InputError :message="form.errors.financial_account_id" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <Label>Referensi Eksternal (opsional)</Label>
                        <Input class="h-10" v-model="form.external_reference" placeholder="No. transfer / ref e-wallet" />
                        <InputError :message="form.errors.external_reference" class="mt-1" />
                    </div>
                </div>

                <div>
                    <Label for="description">Deskripsi</Label>
                    <Textarea rows="3" v-model="form.description" />
                    <InputError :message="form.errors.description" />
                </div>
            </div>

            <DialogFooter class="pt-4">
                <Button variant="secondary" @click="addModal = false">Batal</Button>
                <Button :disabled="form.processing || form.amount === 0 || form.amount === null" @click="handleDeposit">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="refundModal" @update:open="(val) => (refundModal = val)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Refund</DialogTitle>
            </DialogHeader>

            <div class="space-y-4">
                <div>
                    <Label for="amount">Jumlah Refund</Label>
                    <CurrencyInput v-model="refundForm.amount" />
                    <InputError :message="refundForm.errors.amount" />
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
                    <Label for="description">Deskripsi</Label>
                    <Textarea rows="3" v-model="refundForm.description" />
                    <InputError :message="refundForm.errors.description" />
                </div>
            </div>

            <DialogFooter class="pt-4">
                <Button variant="secondary" @click="refundModal = false">Batal</Button>
                <Button :disabled="refundForm.processing || refundForm.amount === 0 || refundForm.amount === null" @click="handleRefund">
                    <LoaderCircle v-if="refundForm.processing" class="h-4 w-4 animate-spin" />
                    Refund
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
