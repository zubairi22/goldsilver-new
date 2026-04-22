<script lang="ts" setup>
import DeleteButton from '@/components/DeleteButton.vue';
import Heading from '@/components/Heading.vue';
import ImageModal from '@/components/ImageModal.vue';
import PageNav from '@/components/PageNav.vue';
import SearchInput from '@/components/SearchInput.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFormat } from '@/composables/useFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const { items, filters, category } = defineProps(['items', 'filters', 'category']);
const { formatRupiah } = useFormat();

const search = ref(filters.search || '');

const categoryLabel = computed(() => {
    const map: Record<string, string> = {
        gold: 'Emas',
        silver: 'Perak',
    };
    return map[category] ?? category;
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: `Produk Rusak (${categoryLabel.value})`, href: '#' },
];

function doSearch() {
    router.get(route('damaged.index', category), { search: search.value }, { preserveScroll: true, preserveState: true });
}

const restoreModal = ref(false);
const restoreItem = ref<any>(null);

function openRestore(item: any) {
    restoreItem.value = {
        ...item,
        newName: item.name,
        newWeight: item.weight,
        newSellPrice: item.price_sell,
    };
    restoreModal.value = true;
}

function submitRestore() {
    router.patch(
        route('damaged.restore', { category, item: restoreItem.value.id }),
        {
            name: restoreItem.value.newName,
            weight: restoreItem.value.newWeight,
            price_sell: restoreItem.value.newSellPrice,
        },
        {
            onSuccess: () => (restoreModal.value = false),
        },
    );
}

const deleteModal = ref(false);
const deleteItem = ref<any>(null);

const isBulkDelete = ref(false);

function openBulkDelete() {
    isBulkDelete.value = true;
    deleteModal.value = true;
}

function openDelete(item: any) {
    deleteItem.value = item;
    isBulkDelete.value = false;
    deleteModal.value = true;
}

const handleDelete = (type: 'delete' | 'not_ready') => {
    if (isBulkDelete.value) {
        router.post(
            route('damaged.bulk-destroy', { category }),
            {
                ids: selectedIds.value,
                type,
            },
            {
                onSuccess: () => {
                    deleteModal.value = false;
                    selectedIds.value = [];
                },
                preserveScroll: true,
                preserveState: true,
            },
        );
    } else {
        router.delete(route('damaged.destroy', { category, item: deleteItem.value.id }), {
            data: { type },
            onSuccess: () => (deleteModal.value = false),
            preserveScroll: true,
            preserveState: true,
        });
    }
};

const selectedIds = ref<number[]>([]);

const toggleSelectAll = (checked: boolean | 'indeterminate') => {
    if (checked === true) {
        selectedIds.value = items.data.map((i: any) => i.id);
    } else {
        selectedIds.value = [];
    }
};

const updateSelection = (id: number, checked: boolean | 'indeterminate') => {
    if (checked === true) {
        if (!selectedIds.value.includes(id)) {
            selectedIds.value.push(id);
        }
    } else {
        selectedIds.value = selectedIds.value.filter((i) => i !== id);
    }
};

watch(
    () => items.data,
    () => {
        selectedIds.value = [];
    },
);
</script>

<template>
    <Head :title="`Produk Rusak ${categoryLabel}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Produk Rusak" :description="`Daftar item ${categoryLabel} hasil buyback yang rusak`" />

            <div class="max-w-8xl mx-auto">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="mb-4 flex justify-end">
                            <div class="w-full lg:w-80">
                                <SearchInput v-model:search="search" @keyup.enter="doSearch" />
                            </div>
                        </div>

                        <div
                            v-if="selectedIds.length > 0"
                            class="mb-4 flex flex-wrap items-center gap-4 rounded-lg border border-blue-100 bg-blue-50 p-3"
                        >
                            <span class="text-sm font-medium whitespace-nowrap text-blue-700">{{ selectedIds.length }} item terpilih</span>
                            <div class="hidden h-6 w-px bg-blue-200 md:block" />

                            <div class="flex flex-wrap items-center gap-2">
                                <DeleteButton text="Hapus Massal" @confirm="openBulkDelete" />
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-10">
                                            <Checkbox
                                                :modelValue="selectedIds.length === items.data.length && items.data.length > 0"
                                                @update:modelValue="toggleSelectAll"
                                            />
                                        </TableHead>
                                        <TableHead>Gambar</TableHead>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Nama Produk</TableHead>
                                        <TableHead>Sumber Buyback</TableHead>
                                        <TableHead class="text-right">Berat (gr)</TableHead>
                                        <TableHead class="text-right">Harga Jual</TableHead>
                                        <TableHead class="text-center">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow v-for="it in items.data" :key="it.id">
                                        <TableCell>
                                            <Checkbox
                                                :modelValue="selectedIds.includes(it.id)"
                                                @update:modelValue="(v) => updateSelection(it.id, v)"
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <ImageModal v-if="it.image" :src="it.image" trigger />
                                        </TableCell>
                                        <TableCell>{{ it.code }}</TableCell>
                                        <TableCell>{{ it.name }}</TableCell>
                                        <TableCell>
                                            <div v-if="it.latest_buyback_item?.buyback" class="flex flex-col">
                                                <span class="text-xs font-semibold">{{ it.latest_buyback_item.buyback.buyback_no }}</span>
                                                <span class="text-[10px] text-gray-500">{{ it.latest_buyback_item.buyback.customer || '-' }}</span>
                                            </div>
                                            <span v-else class="text-[10px] text-gray-400">Manual / Migrasi</span>
                                        </TableCell>
                                        <TableCell class="text-right">{{ it.weight }}</TableCell>
                                        <TableCell class="text-right">{{ formatRupiah(it.price_sell) }}</TableCell>
                                        <TableCell class="text-center">
                                            <div class="flex justify-center gap-2">
                                                <Button @click="openRestore(it)"> Pulihkan Stok </Button>
                                                <DeleteButton @confirm="openDelete(it)" title="Hapus" />
                                            </div>
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="!items.total">
                                        <TableCell colspan="7" class="py-4 text-center text-gray-500">
                                            Tidak ada data produk rusak ditemukan.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>

                            <PageNav :data="items" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="restoreModal" @update:open="(v) => (restoreModal = v)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Pulihkan Produk ke Stok</DialogTitle>
            </DialogHeader>

            <div v-if="restoreItem">
                <Label>Nama Produk</Label>
                <Input v-model="restoreItem.newName" class="mb-3" />

                <Label>Berat</Label>
                <Input type="number" step="0.01" v-model="restoreItem.newWeight" class="mb-3" />

                <Label>Harga Jual</Label>
                <Input type="number" step="100" v-model="restoreItem.newSellPrice" class="mb-3" />
            </div>

            <DialogFooter>
                <Button variant="secondary" @click="restoreModal = false">Batal</Button>
                <Button @click="submitRestore">Simpan</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="deleteModal" @update:open="(v) => (deleteModal = v)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Opsi Penghapusan</DialogTitle>
            </DialogHeader>

            <div class="py-4">
                <p v-if="!isBulkDelete" class="mb-4 text-sm text-gray-600">
                    Bagaimana Anda ingin menangani item <strong>{{ deleteItem?.code }} - {{ deleteItem?.name }}</strong> ini?
                </p>
                <p v-else class="mb-4 text-sm text-gray-600">
                    Bagaimana Anda ingin menangani <strong>{{ selectedIds.length }}</strong> item yang dipilih?
                </p>
                <div class="space-y-3">
                    <Button variant="outline" class="h-12 w-full justify-start text-left" @click="handleDelete('not_ready')">
                        <div class="flex flex-col">
                            <span class="font-semibold text-gray-900">Hapus Pada Produk Rusak</span>
                            <span class="text-xs text-gray-500">Item akan muncul kembali di daftar barang dengan status 'Belum Siap'.</span>
                        </div>
                    </Button>
                    <Button variant="destructive" class="h-12 w-full justify-start text-left" @click="handleDelete('delete')">
                        <div class="flex flex-col text-white">
                            <span class="font-semibold">Hapus Permanen</span>
                            <span class="text-xs opacity-80">Item akan dihapus dari master item.</span>
                        </div>
                    </Button>
                </div>
            </div>

            <DialogFooter>
                <Button variant="secondary" @click="deleteModal = false">Batal</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
