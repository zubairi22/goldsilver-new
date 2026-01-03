<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import DeleteButton from '@/components/DeleteButton.vue';
import EditButton from '@/components/EditButton.vue';
import SearchInput from '@/components/SearchInput.vue';
import PageNav from '@/components/PageNav.vue';
import Heading from '@/components/Heading.vue';
import ItemForm from '@/pages/item/partial/ItemForm.vue';
import { useSearch } from '@/composables/useSearch';
import { LoaderCircle } from 'lucide-vue-next';
import { useFormat } from '@/composables/useFormat';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Icon from '@/components/Icon.vue';
import ImageModal from '@/components/ImageModal.vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Barang', href: '#' },
];

const {
    items,
    itemTypes,
    totalItems,
    totalWeight,
    itemTypeTotals,
    filters
} = defineProps([
    'items',
    'itemTypes',
    'totalItems',
    'totalWeight',
    'itemTypeTotals',
    'filters'
]);

const { search } = useSearch('store.items.index', route().params.search, ['items']);
const { formatRupiah } = useFormat();

const status = ref(filters.status || 'all');
const item_type_id = ref(filters.item_type_id || 'all');

const defaultForm = () => ({
    name: '',
    item_type_id: '',
    weight: 0,
    price_buy: 0,
    price_sell: 0,
    status: 'ready',
    image: null as string | null,
});

const addForm = useForm(defaultForm());
const editForm = useForm({ _method: 'patch', id: '', ...defaultForm() });

const addModal = ref(false);
const editModal = ref(false);

const addItem = () => addModal.value = true;

const editItem = (item: any) => {
    Object.assign(editForm, item);
    editModal.value = true;
};

const handleAdd = () => {
    addForm.post(route('store.items.store'), {
        preserveScroll: true,
        onSuccess: () => {
            addModal.value = false;
            addForm.reset();
        },
    });
};

const handleEdit = () => {
    if (typeof editForm.image === 'string' && editForm.image) editForm.image = null;

    editForm.post(route('store.items.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            editModal.value = false;
            editForm.reset();
        },
    });
};

const handleDelete = (id: any) => {
    router.delete(route('store.items.destroy', id), {
        preserveScroll: true,
    });
};

const applyFilters = () => {
    const params: Record<string, any> = {};
    if (search.value) params.search = search.value;
    if (status.value !== 'all') params.status = status.value;
    if (item_type_id.value !== 'all') params.item_type_id = item_type_id.value;

    router.get(route('store.items.index', params), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

watch([status, item_type_id], applyFilters);
</script>

<template>
    <Head title="Barang" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <!-- Bagian Atas -->
            <div class="mx-4 space-y-4 md:flex items-center md:justify-between md:space-y-0">

                <!-- Heading -->
                <Heading class="md:mr-6 flex-1" title="Barang" description="Kelola data barang emas dan perak" />

                <!-- Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full md:w-96">

                    <Card class="py-4 gap-1 border-emerald-200 bg-emerald-50">
                        <CardHeader class="flex flex-row items-center justify-between">
                            <CardTitle class="text-sm font-medium text-emerald-700">Total Barang</CardTitle>
                            <Icon name="DollarSign" class="h-4 w-4 text-emerald-600" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold text-emerald-800">{{ totalItems }} (pcs)</p>
                        </CardContent>
                    </Card>

                    <Card class="py-4 gap-1 border-yellow-200 bg-yellow-50">
                        <CardHeader class="flex flex-row items-center justify-between">
                            <CardTitle class="text-sm font-medium text-yellow-700">Total Berat</CardTitle>
                            <Icon name="CheckCircle" class="h-4 w-4 text-yellow-600" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold text-yellow-800">{{ totalWeight.toFixed(2) }} (gr)</p>
                        </CardContent>
                    </Card>

                </div>
            </div>

            <!-- Card Tipe Barang -->
            <div class="grid gap-4 py-2 px-4 pb-6">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                    <Card v-for="(total, itemTypeId) in itemTypeTotals" :key="itemTypeId" class="gap-1 bg-blue-50 border-blue-200 py-4">
                        <CardHeader class="flex flex-row items-center justify-between">
                            <CardTitle class="text-sm font-medium text-blue-700">{{ itemTypes[itemTypeId] }}</CardTitle>
                            <Icon name="Folder" class="w-4 h-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-md font-bold text-blue-800">{{ total.total_pieces }} (pcs), {{ total.total_weight.toFixed(2) }} (gr)</p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Tabel Utama -->
            <div class="max-w-8xl mx-auto">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="mt-2 mb-8 flex flex-wrap items-center justify-between gap-4">
                            <div class="flex flex-wrap items-center gap-4">
                                <div class="w-40">
                                    <Label class="mb-2">Status</Label>
                                    <Select v-model="status">
                                        <SelectTrigger><SelectValue placeholder="Status" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua</SelectItem>
                                            <SelectItem value="ready">Siap Jual</SelectItem>
                                            <SelectItem value="sold">Terjual</SelectItem>
                                            <SelectItem value="damaged">Rusak</SelectItem>
                                            <SelectItem value="buyback">Buyback</SelectItem>
                                            <SelectItem value="not_ready">Belum Siap</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="w-48">
                                    <Label class="mb-2">Tipe</Label>
                                    <Select v-model="item_type_id">
                                        <SelectTrigger><SelectValue placeholder="Tipe Barang" /></SelectTrigger>
                                        <SelectContent class="max-h-72 overflow-y-auto">
                                            <SelectItem value="all">Semua</SelectItem>
                                            <SelectItem v-for="(name, id) in itemTypes" :key="id" :value="id">
                                                {{ name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2 flex flex-col justify-between md:flex-row">
                            <Button @click="addItem">Tambah Item</Button>
                            <div class="mt-3 mb-3 md:mt-0 md:text-right">
                                <SearchInput v-model:search="search" />
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Gambar</TableHead>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Tipe</TableHead>
                                        <TableHead>Berat</TableHead>
                                        <TableHead>Harga Beli</TableHead>
                                        <TableHead>Harga Jual</TableHead>
                                        <TableHead class="text-center">Status</TableHead>
                                        <TableHead class="w-8" />
                                        <TableHead class="w-8" />
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow v-for="item in items.data" :key="item.id">
                                        <TableCell class="py-1">
                                            <ImageModal
                                                v-if="item.image"
                                                :src="item.image"
                                                :filename="`item-${item.name.replace(/\s+/g, '_')}.png`"
                                                trigger
                                            />
                                            <span v-else class="text-sm text-gray-400">Tidak ada</span>
                                        </TableCell>

                                        <TableCell>{{ item.code }}</TableCell>
                                        <TableCell>{{ item.name }}</TableCell>
                                        <TableCell>{{ item.type?.name || '-' }}</TableCell>
                                        <TableCell>{{ item.weight }} gr</TableCell>
                                        <TableCell>{{ formatRupiah(item.price_buy) }}</TableCell>
                                        <TableCell>{{ formatRupiah(item.price_sell) }}</TableCell>

                                        <TableCell class="text-center">
                                            <Badge>{{ item.status_label }}</Badge>
                                        </TableCell>

                                        <TableCell class="px-1">
                                            <EditButton @click="editItem(item)" />
                                        </TableCell>

                                        <TableCell class="px-1">
                                            <DeleteButton @confirm="handleDelete(item.id)" />
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="!items.total">
                                        <TableCell colspan="9" class="text-center text-gray-500">
                                            Item tidak ditemukan
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

    <!-- Add Modal -->
    <Dialog :open="addModal" @update:open="(val) => addModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tambah Item</DialogTitle>
                <DialogDescription>Isi form untuk menambahkan item baru.</DialogDescription>
            </DialogHeader>

            <ItemForm v-model:form="addForm" :itemTypes="itemTypes" />

            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="addModal = false">Batal</Button>
                <Button :disabled="addForm.processing" @click="handleAdd">
                    <LoaderCircle v-if="addForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Edit Modal -->
    <Dialog :open="editModal" @update:open="(val) => editModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Item</DialogTitle>
                <DialogDescription>Ubah informasi item yang dipilih.</DialogDescription>
            </DialogHeader>

            <ItemForm v-model:form="editForm" :itemTypes="itemTypes" />

            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="editModal = false">Batal</Button>
                <Button :disabled="editForm.processing" @click="handleEdit">
                    <LoaderCircle v-if="editForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
