<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import Heading from '@/components/Heading.vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { computed } from 'vue';
import { useFormat } from '@/composables/useFormat';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { BreadcrumbItem } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { LoaderCircle } from 'lucide-vue-next';
import { useTime } from '@/composables/useTime';

const { sale, customer, items } = defineProps(['sale', 'customer', 'items']);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Buyback Emas', href: '#' },
    { title: 'Tambah', href: '#' },
]

const { formatRupiah, formatDate } = useFormat();
const { today } = useTime();

// FORM
const form = useForm({
    sale_id: sale.id,
    items: items.map((it: any) => ({
        item_id: it.item?.id,
        sale_item_id: it.id,
        name: it.manual_name ?? it.item?.name,
        original_weight: it.weight,
        buyback_weight: it.weight,
        buyback_price: 0,
        selected: false,
        image: null,
        initial_image: it.item?.image,
        is_buyback: it.item?.status === 'buyback',
    })),
});

const handleFiles = (e: Event, index: number) => {
    const input = e.target as HTMLInputElement;

    if (input.files && input.files.length > 0) {
        form.items[index].image = input.files[0];
    }
};

const totalBuyback = computed(() =>
    form.items.reduce(
        (sum: any, it: any) => (it.selected && !it.is_buyback ? sum + Number(it.buyback_weight) * Number(it.buyback_price) : sum),
        0 as any,
    ),
);

const totalBuybackWeight = computed(() =>
    form.items.reduce(
        (sum: any, it: any) =>
            it.selected && !it.is_buyback ? sum + Number(it.buyback_weight || 0) : sum,
        0 as any
    )
);

const totalBuybackItems = computed(() =>
    form.items.filter((i: any) => i.selected && !i.is_buyback).length
);


const submit = () => {
    const filtered = form.items.filter((i: any) => i.selected && !i.is_buyback);
    if (filtered.length === 0) return alert('Pilih minimal satu item');

    form.transform((data) => ({ ...data, items: filtered }));
    form.post(route('buyback.gold.store'), {
        preserveScroll: true,
        onSuccess: () => {
            setTimeout(() => {
                router.get('buyback.gold.index')
            }, 1000)
        },
    });
};

const goBack = () => router.get(route('transactions.sales.gold.index', { category: 'gold' }));
</script>

<template>
    <Head :title="`Buyback Emas - ${sale.invoice_no}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <div class="flex justify-between items-center mx-4">
                <Heading title="Buyback Emas" :description="`Invoice ${sale.invoice_no}`" />

                <div class="flex gap-3">
                    <Button variant="outline" @click="goBack">Kembali</Button>
                </div>
            </div>

            <div class="max-w-8xl mx-auto space-y-8">
                <!-- INFO PENJUALAN & PELANGGAN -->
                <Card class="md:mx-4 mb-4">
                    <CardHeader>
                        <p class="mb-4 font-semibold">Informasi Penjualan</p>
                        <hr>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-6 text-sm md:grid-cols-2">
                            <!-- Kiri: Info Penjualan + Pelanggan -->
                            <div class="space-y-6">
                                <!-- Info Penjualan -->
                                <div>
                                    <div class="space-y-1">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Invoice</span>
                                            <span>{{ sale.invoice_no }}</span>
                                        </div>

                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Tanggal Penjualan</span>
                                            <span>{{ formatDate(sale.created_at) }}</span>
                                        </div>

                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Kasir</span>
                                            <span>{{ sale.user?.name }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Info Pelanggan -->
                                <div v-if="customer">
                                    <p class="mb-2 font-semibold">Informasi Pelanggan</p>

                                    <div class="space-y-1">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Nama</span>
                                            <span>{{ customer.name }}</span>
                                        </div>

                                        <div class="flex justify-between">
                                            <span class="text-gray-500">No HP</span>
                                            <span>{{ customer.phone ?? '-' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tanggal Buyback - tidak bisa diubah -->
                                <div>
                                    <p class="mb-1 font-semibold">Tanggal Buyback</p>
                                    <p class="rounded border bg-gray-100 px-3 py-2">
                                        {{ formatDate(today()) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Kanan: QR Code + Invoice -->
                            <div class="flex flex-col items-center justify-center p-4 text-center">
                                <img
                                    :src="'/storage/' + sale.qrcode"
                                    alt="QR"
                                    class="h-24 w-24 rounded shadow"
                                />

                                <p class="mt-4 text-md font-semibold">{{ sale.invoice_no }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- TABLE ITEM BUYBACK -->
                <Card class="md:mx-4 mb-4">
                    <CardHeader>
                        <p class="mb-4 font-semibold">Detail Item Buyback</p>
                        <hr>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-10 text-center"/>
                                    <TableHead>Produk</TableHead>
                                    <TableHead class="text-right">Berat Asli (gr)</TableHead>
                                    <TableHead class="text-right">Berat BB</TableHead>
                                    <TableHead class="text-right">Harga/gr</TableHead>
                                    <TableHead class="text-right">Subtotal</TableHead>
                                    <TableHead class="text-center">Gambar Pembelian</TableHead>
                                    <TableHead class="text-center">Foto Buyback</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow v-for="(it, i) in form.items" :key="i">
                                    <!-- Checkbox -->
                                    <TableCell class="text-center">
                                        <Checkbox
                                            class="w-6 h-6"
                                            v-model="it.selected"
                                            :disabled="it.is_buyback"
                                        />
                                    </TableCell>

                                    <!-- Produk -->
                                    <TableCell>
                                        <div class="flex flex-col gap-1">
                                            <span class="font-medium">{{ it.name }}</span>

                                            <Badge v-if="it.is_buyback" variant="destructive" class="w-28">
                                                Sudah Buyback
                                            </Badge>
                                        </div>
                                    </TableCell>

                                    <TableCell class="text-right">{{ it.original_weight }}</TableCell>

                                    <!-- Berat BB -->
                                    <TableCell class="text-right">
                                        <Input
                                            type="number"
                                            step="0.01"
                                            class="w-24 text-right"
                                            v-model.number="it.buyback_weight"
                                            :disabled="!it.selected || it.is_buyback"
                                        />
                                    </TableCell>

                                    <!-- Harga/gr -->
                                    <TableCell class="text-right">
                                        <Input
                                            type="number"
                                            step="100"
                                            class="w-28 text-right"
                                            v-model.number="it.buyback_price"
                                            :disabled="!it.selected || it.is_buyback"
                                        />
                                    </TableCell>

                                    <!-- Subtotal -->
                                    <TableCell class="text-right font-semibold">
                                        <span v-if="it.selected && !it.is_buyback">
                                            {{ formatRupiah(it.buyback_weight * it.buyback_price) }}
                                        </span>
                                        <span v-else>-</span>
                                    </TableCell>

                                    <!-- Gambar Awal -->
                                    <TableCell class="text-center">
                                        <img v-if="it.initial_image" :src="it.initial_image" class="mx-auto h-16 w-16 rounded object-cover" />
                                        <span v-else class="text-gray-400">Tidak ada</span>
                                    </TableCell>

                                    <!-- Foto Buyback -->
                                    <TableCell class="text-center">
                                        <Input
                                            type="file"
                                            accept="image/jpg, image/png"
                                            capture="environment"
                                            :disabled="!it.selected || it.is_buyback"
                                            @change="(e: any) => handleFiles(e, i)"
                                        />

                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                    </CardContent>
                </Card>

                <!-- SUMMARY CARD -->
                <Card class="md:mx-4">
                    <CardHeader>
                        <p class="mb-2 font-semibold">Ringkasan Buyback</p>
                        <hr>
                    </CardHeader>

                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                            <!-- Total Item -->
                            <div class="rounded-lg border bg-gray-50/50 p-4">
                                <p class="text-sm text-gray-500">Total Item Dipilih</p>
                                <p class="text-2xl font-bold mt-1 text-gray-800">
                                    {{ totalBuybackItems }}
                                </p>
                            </div>

                            <!-- Total Berat -->
                            <div class="rounded-lg border bg-gray-50/50 p-4">
                                <p class="text-sm text-gray-500">Total Berat Buyback</p>
                                <p class="text-2xl font-bold mt-1 text-gray-800">
                                    {{ totalBuybackWeight.toFixed(2) }} gr
                                </p>
                            </div>

                            <!-- Total Harga -->
                            <div class="rounded-lg border bg-gray-50/50 p-4">
                                <p class="text-sm text-gray-500">Total Harga Buyback</p>
                                <p class="text-2xl font-bold mt-1 ">
                                    {{ formatRupiah(totalBuyback) }}
                                </p>
                            </div>

                        </div>

                        <!-- Tombol Submit -->
                        <div class="flex justify-end mt-6">
                            <Button :disabled="form.processing || !totalBuybackItems" @click="submit" class="px-6">
                                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                                Proses Transaksi Buyback
                            </Button>
                        </div>
                    </CardContent>
                </Card>

            </div>
        </div>
    </AppLayout>
</template>
