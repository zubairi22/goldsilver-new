<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Card, CardContent } from '@/components/ui/card';
import Heading from '@/components/Heading.vue';
import type { BreadcrumbItem } from '@/types';
import { useFormat } from '@/composables/useFormat';
import { Badge } from '@/components/ui/badge';
import PageNav from '@/components/PageNav.vue';
import { ref } from 'vue';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const { customer } = defineProps(['customer', 'point_logs']);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pelanggan', href: '/outlet/customers' },
    { title: 'Detail Poin', href: '/#' },
];

const { formatDate } = useFormat();

const redeemModal = ref(false);

const form = useForm({
    points: 0,
});

const openRedeemModal = () => {
    form.reset();
    redeemModal.value = true;
}

const handleRedeem = () => {
    form.post(route('outlet.customer.point.redeem', customer.id), {
        onSuccess: () => redeemModal.value = false,
    });
};

</script>

<template>
    <Head :title="'Detail Poin ' + customer.name " />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <div class="flex justify-between items-center mx-4 mb-4">
                <Heading title="Detail Poin" description="Daftar Transaksi Poin" />
                <Button size="lg" variant="secondary" @click="openRedeemModal">Redeem Poin</Button>
            </div>

            <div class="mx-auto max-w-8xl">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-20 text-center">#</TableHead>
                                        <TableHead>Tanggal</TableHead>
                                        <TableHead>Jenis</TableHead>
                                        <TableHead class="text-center">Poin</TableHead>
                                        <TableHead>Deskripsi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="(log, index) in point_logs.data" :key="log.id">
                                        <TableCell class="text-center">{{ index + 1 }}</TableCell>
                                        <TableCell>{{ formatDate(log.created_at) }}</TableCell>
                                        <TableCell>
                                            <Badge :variant="log.type === 'earn' ? 'success' : 'destructive'">
                                                {{ log.type === 'earn' ? 'Dapat' : 'Tebus' }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-center font-bold">
                                            {{ log.points }}
                                        </TableCell>
                                        <TableCell>{{ log.description || '-' }}</TableCell>
                                    </TableRow>
                                    <TableRow v-if="!point_logs.total">
                                        <TableCell colspan="5" class="text-center text-gray-500">
                                            Belum ada aktivitas poin.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <PageNav :data="point_logs" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="redeemModal" @update:open="(val) => redeemModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Redeem Poin</DialogTitle>
                <DialogDescription>
                    Masukkan jumlah poin yang ingin diredeem.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div>
                    <Label for="points" class="block text-sm font-medium text-gray-700">Jumlah Poin</Label>
                    <Input
                        v-model="form.points"
                        id="points"
                        type="number"
                        min="1"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                    <div v-if="form.errors.points" class="text-red-500 text-sm mt-1">{{ form.errors.points }}</div>
                </div>
                <p class="text-sm text-gray-500">Sisa poin tersedia: {{ customer.current_year_point.points }}</p>
            </div>

            <DialogFooter class="mt-6 gap-2">
                <Button variant="secondary" @click="redeemModal=false">Batal</Button>
                <Button :disabled="form.processing" @click="handleRedeem">Redeem</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

</template>
