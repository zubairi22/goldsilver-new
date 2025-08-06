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
import SearchInput from '@/components/SearchInput.vue';
import { LoaderCircle } from 'lucide-vue-next';

const { customer } = defineProps(['customer', 'deposits']);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pelanggan', href: '/outlet/customers' },
    { title: 'Detail Deposit', href: '/#' },
];

const { formatDate, formatRupiah } = useFormat();

const form = useForm({
    amount: '',
    description: '',
});

const refundForm = useForm({
    amount: '',
    description: '',
});

const addModal = ref(false);
const refundModal = ref(false);

const submitDeposit = () => {
    form.post(route('outlet.customer.deposit.store', customer.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            addModal.value = false;
        },
    });
};

const submitRefund = () => {
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
            <Heading class="mx-4" title="Detail Deposit" :description="'Daftar Riwayat Deposit dari ' + customer.name"  />

            <div class="mx-auto max-w-8xl">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="flex flex-col justify-between md:flex-row mb-2">
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
                                        <TableHead>Jenis</TableHead>
                                        <TableHead class="text-center">Nominal</TableHead>
                                        <TableHead>Deskripsi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="(deposit, index) in deposits.data" :key="deposit.id">
                                        <TableCell class="text-center">{{ index + 1 }}</TableCell>
                                        <TableCell>{{ formatDate(deposit.created_at) }}</TableCell>
                                        <TableCell>
                                            <Badge :variant="deposit.type === 'top_up' ? 'success' : deposit.type === 'used' ? 'warning' : 'destructive'">
                                                {{ deposit.type === 'top_up' ? 'Top Up' : deposit.type === 'used' ? 'Digunakan' : 'Refund'  }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-center font-bold">
                                            {{ formatRupiah(deposit.amount) }}
                                        </TableCell>
                                        <TableCell>{{ deposit.description || '-' }}</TableCell>
                                    </TableRow>
                                    <TableRow v-if="!deposits.data.length">
                                        <TableCell colspan="5" class="text-center text-gray-500">
                                            Belum ada riwayat deposit.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="addModal" @update:open="val => addModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tambah Deposit</DialogTitle>
            </DialogHeader>

            <div class="space-y-4">
                <div>
                    <Label for="amount">Jumlah Deposit</Label>
                    <Input v-model="form.amount" id="amount" type="number" min="0" class="w-full" />
                    <InputError :message="form.errors.amount" />
                </div>

                <div>
                    <Label for="description">Deskripsi</Label>
                    <Input v-model="form.description" id="description" class="w-full" />
                    <InputError :message="form.errors.description" />
                </div>
            </div>

            <DialogFooter class="pt-4">
                <Button variant="secondary" @click="addModal = false">Batal</Button>
                <Button :disabled="form.processing" @click="submitDeposit">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="refundModal" @update:open="val => refundModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Refund</DialogTitle>
            </DialogHeader>

            <div class="space-y-4">
                <div>
                    <Label for="amount">Jumlah Refund</Label>
                    <Input v-model="refundForm.amount" id="amount" type="number" min="1000" class="w-full" />
                    <InputError :message="refundForm.errors.amount" />
                </div>

                <div>
                    <Label for="description">Deskripsi</Label>
                    <Input v-model="refundForm.description" id="description" class="w-full" />
                    <InputError :message="refundForm.errors.description" />
                </div>
            </div>

            <DialogFooter class="pt-4">
                <Button variant="secondary" @click="refundModal = false">Batal</Button>
                <Button :disabled="refundForm.processing" @click="submitRefund">
                    <LoaderCircle v-if="refundForm.processing" class="h-4 w-4 animate-spin" />
                    Refund
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

</template>
