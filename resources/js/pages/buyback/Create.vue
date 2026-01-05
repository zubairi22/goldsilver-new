<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import Heading from '@/components/Heading.vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { computed } from 'vue'
import { useFormat } from '@/composables/useFormat'
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow
} from '@/components/ui/table'
import type { BreadcrumbItem } from '@/types'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { LoaderCircle } from 'lucide-vue-next'
import { useTime } from '@/composables/useTime'
import CameraUploader from '@/components/CameraUploader.vue'
import InputError from '@/components/InputError.vue'
import ImageModal from '@/components/ImageModal.vue';

const props = defineProps<{
    category: 'gold' | 'silver'
    sale: any
    customer: any
    items: any[]
}>()

const categoryLabel = computed(() =>
    props.category === 'gold' ? 'Emas' : 'Perak'
)

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: `Buyback ${categoryLabel.value}`, href: route('buyback.index', { category: props.category }) },
    { title: 'Tambah', href: '#' },
]

const { formatRupiah, formatDate } = useFormat()
const { today } = useTime()

const form = useForm({
    sale_id: props.sale.id,
    customer_id: props.customer?.id ?? null,
    items: props.items.map((it: any) => {
        const bb = it.buyback_item

        return {
            sale_item_id: it.id,
            item_id: it.item?.id ?? null,

            name: it.manual_name ?? it.item?.name,

            weight: it.weight,
            price: it.price,
            subtotal: it.subtotal,

            buyback_weight: bb ? bb.weight : it.weight,
            buyback_price: bb ? bb.price : 0,
            buyback_subtotal: bb ? bb.subtotal : 0,

            selected: false,
            image: undefined,

            initial_image: it.item?.image ?? null,

            is_buyback: !!bb,
        }
    }),
})

const totalBuyback = computed(() =>
    form.items.reduce(
        (sum, it) =>
            it.selected && !it.is_buyback
                ? sum + Number(it.buyback_weight) * Number(it.buyback_price)
                : sum,
        0
    )
)

const totalBuybackWeight = computed(() =>
    form.items.reduce(
        (sum, it) =>
            it.selected && !it.is_buyback
                ? sum + Number(it.buyback_weight || 0)
                : sum,
        0
    )
)

const totalBuybackItems = computed(() =>
    form.items.filter(it => it.selected && !it.is_buyback).length
)

const submit = () => {
    const filtered = form.items.filter(
        it => it.selected && !it.is_buyback
    )

    if (!filtered.length) {
        alert('Pilih minimal satu item')
        return
    }

    form.transform(data => ({
        ...data,
        items: filtered,
    }))

    form.post(route('buyback.store', { category: props.category }), {
        preserveScroll: true,
        onSuccess: () => {
            setTimeout(() => {
                router.get(route('buyback.index', { category: props.category }))
            }, 700)
        },
    })
}

const goBack = () => {
    router.get(route('sales.index', { category: props.category }))
}
</script>

<template>
    <Head :title="`Buyback ${categoryLabel} - ${sale.invoice_no}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <div class="flex justify-between items-center mx-4">
                <Heading
                    :title="`Buyback ${categoryLabel}`"
                    :description="`Invoice ${sale.invoice_no}`"
                />

                <Button variant="outline" @click="goBack">
                    Kembali
                </Button>
            </div>

            <div class="max-w-8xl mx-auto space-y-8">

                <Card class="md:mx-4 mb-4">
                    <CardHeader>
                        <p class="mb-4 font-semibold">Informasi Penjualan</p>
                        <hr />
                    </CardHeader>

                    <CardContent>
                        <div class="grid gap-6 text-sm md:grid-cols-2">
                            <div class="space-y-6">
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

                                <div>
                                    <p class="mb-1 font-semibold">Tanggal Buyback</p>
                                    <p class="rounded border bg-gray-100 px-3 py-2">
                                        {{ formatDate(today()) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-col items-center justify-center p-4">
                                <img
                                    :src="'/storage/' + sale.qr_path"
                                    class="h-24 w-24 rounded shadow"
                                />
                                <p class="mt-4 text-md font-semibold">
                                    {{ sale.invoice_no }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="md:mx-4 mb-4">
                    <CardHeader>
                        <p class="mb-4 font-semibold">Detail Item Buyback</p>
                        <hr />
                    </CardHeader>

                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-10 text-center" />
                                    <TableHead>Produk</TableHead>
                                    <TableHead class="text-right">Harga Jual Net</TableHead>
                                    <TableHead class="text-right">Berat BB</TableHead>
                                    <TableHead class="text-right">Harga/gr BB</TableHead>
                                    <TableHead class="text-right">Subtotal</TableHead>
                                    <TableHead class="text-center">Gambar Pembelian</TableHead>
                                    <TableHead class="text-center">Foto Buyback</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <template v-for="(it, i) in form.items" :key="i">
                                    <TableRow>
                                        <TableCell class="text-center">
                                            <Checkbox
                                                v-model="it.selected"
                                                :disabled="it.is_buyback"
                                            />
                                        </TableCell>

                                        <TableCell>
                                            <div class="flex flex-col gap-1">
                                                <span class="font-medium">{{ it.name }}</span>
                                                <Badge
                                                    v-if="it.is_buyback"
                                                    variant="destructive"
                                                    class="w-28"
                                                >
                                                    Sudah Buyback
                                                </Badge>
                                            </div>
                                        </TableCell>

                                        <TableCell class="text-right">
                                            <div class="flex flex-col">
                                                <span class="font-semibold">
                                                    {{ formatRupiah(it.subtotal) }}
                                                </span>
                                                <span class="text-xs text-gray-500">
                                                    ({{ it.weight }} gr × {{ formatRupiah(it.price) }})
                                                </span>
                                            </div>
                                        </TableCell>

                                        <TableCell class="text-right">
                                            <Input
                                                type="number"
                                                step="0.01"
                                                class="w-24 text-right"
                                                v-model.number="it.buyback_weight"
                                                :disabled="!it.selected || it.is_buyback"
                                            />
                                        </TableCell>

                                        <TableCell class="text-right">
                                            <Input
                                                type="number"
                                                step="100"
                                                class="w-28 text-right"
                                                v-model.number="it.buyback_price"
                                                :disabled="!it.selected || it.is_buyback"
                                            />
                                        </TableCell>

                                        <TableCell class="text-right font-semibold">
                                            <span v-if="it.is_buyback">
                                                {{ formatRupiah(it.buyback_subtotal) }}
                                            </span>
                                            <span v-else-if="it.selected">
                                                {{ formatRupiah(it.buyback_weight * it.buyback_price) }}
                                            </span>
                                            <span v-else>-</span>
                                        </TableCell>

                                        <TableCell class="text-center">
                                            <ImageModal
                                                v-if="it.initial_image"
                                                :src="it.initial_image"
                                                :filename="`item-${it.name.replace(/\s+/g, '_')}.png`"
                                                trigger
                                            />
                                            <span v-else class="text-sm text-gray-400">Tidak ada</span>
                                        </TableCell>

                                        <TableCell class="text-center">
                                            <CameraUploader
                                                v-model="form.items[i].image"
                                                :disabled="!it.selected || it.is_buyback"
                                            />
                                        </TableCell>
                                    </TableRow>

                                    <TableRow
                                        v-if="
                                            (form.errors as any)[`items.${i}.buyback_weight`] ||
                                            (form.errors as any)[`items.${i}.buyback_price`] ||
                                            (form.errors as any)[`items.${i}.image`]
                                        "
                                        class="bg-red-50"
                                    >
                                        <TableCell colspan="8" class="text-sm text-red-600">
                                            <InputError :message="(form.errors as any)[`items.${i}.buyback_weight`]" />
                                            <InputError :message="(form.errors as any)[`items.${i}.buyback_price`]" />
                                            <InputError :message="(form.errors as any)[`items.${i}.image`]" />
                                        </TableCell>
                                    </TableRow>
                                </template>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card class="md:mx-4">
                    <CardHeader>
                        <p class="mb-2 font-semibold">Ringkasan Buyback</p>
                        <hr />
                    </CardHeader>

                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="rounded-lg border p-4">
                                <p class="text-sm text-gray-500">Total Item Dipilih</p>
                                <p class="text-2xl font-bold">{{ totalBuybackItems }}</p>
                            </div>

                            <div class="rounded-lg border p-4">
                                <p class="text-sm text-gray-500">Total Berat Buyback</p>
                                <p class="text-2xl font-bold">{{ totalBuybackWeight.toFixed(2) }} gr</p>
                            </div>

                            <div class="rounded-lg border p-4">
                                <p class="text-sm text-gray-500">Total Harga Buyback</p>
                                <p class="text-2xl font-bold">{{ formatRupiah(totalBuyback) }}</p>
                            </div>
                        </div>

                        <div class="flex justify-end mt-6">
                            <Button
                                :disabled="form.processing || !totalBuybackItems"
                                @click="submit"
                                class="px-6"
                            >
                                <LoaderCircle
                                    v-if="form.processing"
                                    class="h-4 w-4 mr-2 animate-spin"
                                />
                                Proses Transaksi Buyback
                            </Button>
                        </div>
                    </CardContent>
                </Card>

            </div>
        </div>
    </AppLayout>
</template>
