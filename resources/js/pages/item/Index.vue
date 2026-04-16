<script lang="ts" setup>
import DeleteButton from '@/components/DeleteButton.vue';
import EditButton from '@/components/EditButton.vue';
import Heading from '@/components/Heading.vue';
import Icon from '@/components/Icon.vue';
import ImageModal from '@/components/ImageModal.vue';
import PageNav from '@/components/PageNav.vue';
import QrScanner from '@/components/QrScanner.vue';
import SearchInput from '@/components/SearchInput.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import Input from '@/components/ui/input/Input.vue';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useBarcodeScanner } from '@/composables/useBarcodeScanner';
import { useFormat } from '@/composables/useFormat';
import { useSearch } from '@/composables/useSearch';
import AppLayout from '@/layouts/AppLayout.vue';
import ItemForm from '@/pages/item/partial/ItemForm.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Printer } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Barang', href: '#' },
];

const { items, itemTypes, totalItems, totalWeight, itemTypeTotals, filters } = defineProps([
    'items',
    'itemTypes',
    'totalItems',
    'totalWeight',
    'itemTypeTotals',
    'filters',
]);

const { search } = useSearch('store.items.index', route().params.search, ['items']);

useBarcodeScanner((code: string) => {
    search.value = code;
    toast.success(`Scan: ${code}`);
});

const { formatRupiah } = useFormat();

const status = ref(filters.status || 'all');
const item_type_id = ref(filters.item_type_id || 'all');
const sortBy = ref(filters.sort || 'code');
const sortDirection = ref(filters.direction || 'desc');

const toggleSort = (column: string) => {
    if (sortBy.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortDirection.value = 'desc';
    }
    applyFilters();
};

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

const addItem = () => (addModal.value = true);

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
    params.sort = sortBy.value;
    params.direction = sortDirection.value;

    router.get(
        route('store.items.index', params),
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

watch([status, item_type_id], applyFilters);

const labelModal = ref(false);
const printRange = ref({
    start: '',
    end: '',
});

const openPrintModal = () => {
    labelModal.value = true;
};

const printBulkLabel = () => {
    if (!printRange.value.start || !printRange.value.end) return;

    window.open(
        route('store.items.print-label', {
            start: printRange.value.start,
            end: printRange.value.end,
        }),
        '_blank',
    );

    labelModal.value = false;
};

const printSingleLabel = (id: number) => {
    window.open(route('store.items.print-single-label', id), '_blank');
};

const scanModal = ref(false);
const onScanned = (code: string) => {
    search.value = code;
    toast.success(`Scan berhasil: ${code}`);
};
</script>

<template>
    <Head title="Barang" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <div class="mx-4 items-center space-y-4 md:flex md:justify-between md:space-y-0">
                <Heading class="flex-1 md:mr-6" title="Barang" description="Kelola data barang emas dan perak" />

                <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 md:w-96">
                    <Card class="gap-1 border-emerald-200 bg-emerald-50 py-4">
                        <CardHeader class="flex flex-row items-center justify-between">
                            <CardTitle class="text-sm font-medium text-emerald-700">Total Barang</CardTitle>
                            <Icon name="DollarSign" class="h-4 w-4 text-emerald-600" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold text-emerald-800">{{ totalItems }} (pcs)</p>
                        </CardContent>
                    </Card>

                    <Card class="gap-1 border-yellow-200 bg-yellow-50 py-4">
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

            <div class="grid gap-4 px-4 py-2 pb-6">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                    <Card v-for="(total, itemTypeId) in itemTypeTotals" :key="itemTypeId" class="gap-1 border-blue-200 bg-blue-50 py-4">
                        <CardHeader class="flex flex-row items-center justify-between">
                            <CardTitle class="text-sm font-medium text-blue-700">{{ itemTypes[itemTypeId] }}</CardTitle>
                            <Icon name="Folder" class="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-md font-bold text-blue-800">{{ total.total_pieces }} (pcs), {{ total.total_weight.toFixed(2) }} (gr)</p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div class="max-w-8xl mx-auto">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="mt-2 mb-8 flex flex-wrap items-end justify-between gap-4">
                            <div class="flex flex-wrap items-end gap-4">
                                <div class="w-40">
                                    <Label class="mb-2">Status</Label>
                                    <Select v-model="status">
                                        <SelectTrigger><SelectValue placeholder="Status" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua</SelectItem>
                                            <SelectItem value="ready">Ready</SelectItem>
                                            <SelectItem value="sold">Kosong</SelectItem>
                                            <SelectItem value="damaged">Rusak</SelectItem>
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

                            <div class="flex items-end">
                                <Button variant="info" @click="openPrintModal">
                                    <Printer class="mr-2 h-4 w-4" />
                                    Cetak Label
                                </Button>
                            </div>
                        </div>

                        <div class="mb-2 flex flex-col justify-between md:flex-row">
                            <Button @click="addItem">Tambah Item</Button>
                            <div class="mt-3 mb-3 flex items-center gap-2 md:mt-0 md:text-right">
                                <SearchInput v-model:search="search" />
                                <Button variant="secondary" size="icon" @click="scanModal = true" title="Scan QR">
                                    <Icon name="Camera" class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>No</TableHead>
                                        <TableHead>Gambar</TableHead>
                                        <TableHead class="cursor-pointer select-none" @click="toggleSort('name')">
                                            Nama
                                            <Icon
                                                :name="sortBy === 'name' ? (sortDirection === 'asc' ? 'ChevronUp' : 'ChevronDown') : 'ChevronsUpDown'"
                                                class="ml-1 inline-block h-3 w-3"
                                            />
                                        </TableHead>
                                        <TableHead>Tipe</TableHead>
                                        <TableHead class="cursor-pointer select-none" @click="toggleSort('weight')">
                                            Berat
                                            <Icon
                                                :name="
                                                    sortBy === 'weight' ? (sortDirection === 'asc' ? 'ChevronUp' : 'ChevronDown') : 'ChevronsUpDown'
                                                "
                                                class="ml-1 inline-block h-3 w-3"
                                            />
                                        </TableHead>
                                        <TableHead class="cursor-pointer select-none" @click="toggleSort('price_buy')">
                                            Harga Beli
                                            <Icon
                                                :name="
                                                    sortBy === 'price_buy'
                                                        ? sortDirection === 'asc'
                                                            ? 'ChevronUp'
                                                            : 'ChevronDown'
                                                        : 'ChevronsUpDown'
                                                "
                                                class="ml-1 inline-block h-3 w-3"
                                            />
                                        </TableHead>
                                        <TableHead class="cursor-pointer select-none" @click="toggleSort('price_sell')">
                                            Harga Jual
                                            <Icon
                                                :name="
                                                    sortBy === 'price_sell'
                                                        ? sortDirection === 'asc'
                                                            ? 'ChevronUp'
                                                            : 'ChevronDown'
                                                        : 'ChevronsUpDown'
                                                "
                                                class="ml-1 inline-block h-3 w-3"
                                            />
                                        </TableHead>
                                        <TableHead class="text-center">Status</TableHead>
                                        <TableHead class="w-8" />
                                        <TableHead class="w-8" />
                                        <TableHead class="w-8" />
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow v-for="item in items.data" :key="item.id">
                                        <TableCell>{{ item.id }}</TableCell>
                                        <TableCell class="py-1">
                                            <ImageModal
                                                v-if="item.image"
                                                :src="item.image"
                                                :filename="`item-${item.name.replace(/\s+/g, '_')}.png`"
                                                trigger
                                            />
                                            <span v-else class="text-sm text-gray-400">Tidak ada</span>
                                        </TableCell>

                                        <TableCell>{{ item.name }}</TableCell>
                                        <TableCell>{{ item.type?.name || '-' }}</TableCell>
                                        <TableCell>{{ item.weight }} gr</TableCell>
                                        <TableCell>{{ formatRupiah(item.price_buy) }}</TableCell>
                                        <TableCell>{{ formatRupiah(item.price_sell) }}</TableCell>

                                        <TableCell class="text-center">
                                            <Badge>{{ item.status_label }}</Badge>
                                        </TableCell>

                                        <TableCell class="px-1">
                                            <Button variant="outline" @click="printSingleLabel(item.id)" title="Cetak Label">
                                                <Printer class="h-4 w-4" />
                                            </Button>
                                        </TableCell>

                                        <TableCell class="px-1">
                                            <EditButton @click="editItem(item)" />
                                        </TableCell>

                                        <TableCell class="px-1">
                                            <DeleteButton @confirm="handleDelete(item.id)" />
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="!items.total">
                                        <TableCell colspan="9" class="text-center text-gray-500"> Item tidak ditemukan </TableCell>
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
    <Dialog :open="addModal" @update:open="(val) => (addModal = val)">
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
    <Dialog :open="editModal" @update:open="(val) => (editModal = val)">
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

    <Dialog :open="labelModal" @update:open="(v) => (labelModal = v)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Cetak Label Item</DialogTitle>
                <DialogDescription> Pilih rentang item yang ingin dicetak. </DialogDescription>
            </DialogHeader>

            <div class="mt-4 space-y-4">
                <div>
                    <Label>Dari urutan</Label>
                    <Input type="number" v-model="printRange.start" placeholder="Contoh: 1" />
                </div>

                <div>
                    <Label>Sampai urutan</Label>
                    <Input type="number" v-model="printRange.end" placeholder="Contoh: 20" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <Button variant="outline" @click="labelModal = false"> Batal </Button>

                <Button :disabled="!printRange.start || !printRange.end" @click="printBulkLabel"> Tampilkan & Cetak </Button>
            </div>
        </DialogContent>
    </Dialog>

    <QrScanner v-model:open="scanModal" @scanned="onScanned" />
</template>
