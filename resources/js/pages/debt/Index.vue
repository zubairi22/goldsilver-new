<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import Heading from '@/components/Heading.vue';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { useFormat } from '@/composables/useFormat';
import type { BreadcrumbItem } from '@/types';
import ChevronButton from '@/components/ChevronButton.vue';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Piutang', href: '#' },
];

defineProps(['customers']);

const { formatRupiah } = useFormat();

const openValue = ref<number[]>([]);

const toggleValue = (valueId: number) => {
    if (openValue.value.includes(valueId)) {
        openValue.value = openValue.value.filter((id: number) => id !== valueId);
    } else {
        openValue.value.push(valueId);
    }
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
                                        </TableRow>
                                        <TableRow v-if="openValue.includes(customer.id)">
                                            <TableCell/>
                                            <TableCell colSpan="6">
                                                <Table class="w-full">
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead class="w-44">Kode</TableHead>
                                                            <TableHead class="w-44">Tanggal</TableHead>
                                                            <TableHead>Kasir</TableHead>
                                                            <TableHead>Produk</TableHead>
                                                            <TableHead class="text-right">Total</TableHead>
                                                            <TableHead class="text-right">Bayar</TableHead>
                                                            <TableHead class="text-right">Piutang</TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        <TableRow v-for="trx in customer.transactions" :key="trx.id">
                                                            <TableCell class="align-top">
                                                                {{ trx.transaction_number }}
                                                            </TableCell>
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
                                                            <TableCell class="text-right align-top text-red-600">
                                                                {{ formatRupiah(trx.total_price - trx.paid_amount) }}
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
</template>
