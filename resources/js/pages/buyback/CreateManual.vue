<script lang="ts" setup>
import CameraUploader from '@/components/CameraUploader.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFormat } from '@/composables/useFormat';
import { useTime } from '@/composables/useTime';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    category: 'gold' | 'silver';
}>();

const categoryLabel = computed(() => (props.category === 'gold' ? 'Emas' : 'Perak'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: `Buyback ${categoryLabel.value}`, href: route('buyback.index', { category: props.category }) },
    { title: 'Manual', href: '#' },
];

const { formatRupiah } = useFormat();
const { today } = useTime();

const form = useForm({
    source: 'manual',
    sale_id: null,
    customer_id: null,
    items: [
        {
            sale_item_id: null,
            item_id: null,
            manual_name: '',
            buyback_weight: 0,
            buyback_price: 0,
            buyback_subtotal: 0,
            image: undefined,
        },
    ],
});

const addItem = () => {
    form.items.push({
        sale_item_id: null,
        item_id: null,
        manual_name: '',
        buyback_weight: 0,
        buyback_price: 0,
        buyback_subtotal: 0,
        image: undefined,
    });
};

const removeItem = (index: number) => {
    form.items.splice(index, 1);
};

const totalBuyback = computed(() => form.items.reduce((sum, it) => sum + Number(it.buyback_subtotal || 0), 0));

const totalBuybackWeight = computed(() => form.items.reduce((sum, it) => sum + Number(it.buyback_weight || 0), 0));

const submit = () => {
    form.post(route('buyback.store', { category: props.category }), {
        preserveScroll: true,
        onSuccess: () => {
            setTimeout(() => {
                router.get(route('buyback.index', { category: props.category }));
            }, 700);
        },
    });
};

const updateSubtotal = (item: any) => {
    item.buyback_subtotal = Math.round(Number(item.buyback_weight || 0) * Number(item.buyback_price || 0));
};
</script>

<template>
    <Head :title="`Buyback Manual ${categoryLabel}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <div class="mx-4 flex items-center justify-between">
                <Heading :title="`Buyback Manual ${categoryLabel}`" description="Transaksi buyback tanpa penjualan" />

                <Button variant="outline" @click="router.get(route('buyback.index', { category: props.category }))"> Kembali </Button>
            </div>

            <div class="max-w-8xl mx-auto space-y-8">
                <Card class="md:mx-4">
                    <CardHeader>
                        <p class="mb-4 font-semibold">Detail Item Buyback Manual</p>
                        <hr />
                    </CardHeader>

                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama Item</TableHead>
                                    <TableHead class="text-right">Berat (gr)</TableHead>
                                    <TableHead class="text-right">Harga / gr</TableHead>
                                    <TableHead class="text-right">Subtotal</TableHead>
                                    <TableHead class="text-center">Foto</TableHead>
                                    <TableHead class="w-16" />
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <template v-for="(it, i) in form.items" :key="i">
                                    <TableRow>
                                        <TableCell>
                                            <Input v-model="it.manual_name" placeholder="Contoh: Perak campur" />
                                        </TableCell>

                                        <TableCell class="text-right">
                                            <Input
                                                type="number"
                                                step="0.01"
                                                class="text-right"
                                                v-model.number="it.buyback_weight"
                                                @input="updateSubtotal(it)"
                                            />
                                        </TableCell>

                                        <TableCell class="text-right">
                                            <Input
                                                type="number"
                                                step="100"
                                                class="text-right"
                                                v-model.number="it.buyback_price"
                                                @input="updateSubtotal(it)"
                                            />
                                        </TableCell>

                                        <TableCell class="text-right font-semibold">
                                            <Input type="number" class="text-right" v-model.number="it.buyback_subtotal" />
                                        </TableCell>

                                        <TableCell class="text-center">
                                            <CameraUploader v-model="form.items[i].image" />
                                        </TableCell>

                                        <TableCell class="text-center">
                                            <Button size="icon" variant="ghost" @click="removeItem(i)" v-if="form.items.length > 1">
                                                <Trash2 class="h-4 w-4 text-red-500" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>

                                    <TableRow
                                        v-if="
                                            (form.errors as any)[`items.${i}.manual_name`] ||
                                            (form.errors as any)[`items.${i}.buyback_weight`] ||
                                            (form.errors as any)[`items.${i}.buyback_price`] ||
                                            (form.errors as any)[`items.${i}.image`]
                                        "
                                        class="bg-red-50"
                                    >
                                        <TableCell colspan="6" class="text-sm text-red-600">
                                            <InputError :message="(form.errors as any)[`items.${i}.manual_name`]" />
                                            <InputError :message="(form.errors as any)[`items.${i}.buyback_weight`]" />
                                            <InputError :message="(form.errors as any)[`items.${i}.buyback_price`]" />
                                            <InputError :message="(form.errors as any)[`items.${i}.image`]" />
                                        </TableCell>
                                    </TableRow>
                                </template>
                            </TableBody>
                        </Table>

                        <div class="mt-4">
                            <Button variant="secondary" @click="addItem">
                                <Plus class="mr-2 h-4 w-4" />
                                Tambah Item
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card class="md:mx-4">
                    <CardHeader>
                        <p class="mb-2 font-semibold">Ringkasan</p>
                        <hr />
                    </CardHeader>

                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="rounded-lg border p-4">
                                <p class="text-sm text-gray-500">Total Berat</p>
                                <p class="text-2xl font-bold">{{ totalBuybackWeight.toFixed(2) }} gr</p>
                            </div>

                            <div class="rounded-lg border p-4">
                                <p class="text-sm text-gray-500">Total Harga</p>
                                <p class="text-2xl font-bold">{{ formatRupiah(totalBuyback) }}</p>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <Button :disabled="form.processing || !form.items.length" @click="submit" class="px-6">
                                <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                Simpan Buyback Manual
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
