<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from "vue";
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import DeleteButton from "@/components/DeleteButton.vue";
import EditButton from "@/components/EditButton.vue";
import SearchInput from '@/components/SearchInput.vue';
import PageNav from '@/components/PageNav.vue';
import Heading from '@/components/Heading.vue';
import ItemForm from '@/pages/item/partial/ItemForm.vue';
import { useSearch } from '@/composables/useSearch';
import { LoaderCircle } from 'lucide-vue-next';
import { useFormat } from '@/composables/useFormat';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Item', href: '#' },
];

defineProps(['items', 'itemTypes']);

const { search } = useSearch('store.items.index', route().params.search, ['items']);
const { formatRupiah } = useFormat();

const defaultForm = () => ({
    code: '',
    name: '',
    item_type_id: '',
    weight: '',
    price_buy: '',
    price_sell: '',
    status: 'ready',
    qr_code: '',
    description: '',
});

const addForm = useForm(defaultForm());
const editForm = useForm({ id: '', ...defaultForm() });
const deleteForm = useForm({ id: '' });

const addModal = ref(false);
const editModal = ref(false);
const deleteModal = ref(false);

const addItem = () => {
    addModal.value = true;
};

const editItem = (item: any) => {
    Object.assign(editForm, item);
    editModal.value = true;
};

const deleteItem = (item: any) => {
    deleteForm.id = item.id;
    deleteModal.value = true;
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
    editForm.patch(route('store.items.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            editModal.value = false;
            editForm.reset();
        },
    });
};

const handleDelete = () => {
    router.delete(route('store.items.destroy', deleteForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteModal.value = false;
            deleteForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Item" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Item" description="Kelola data item emas" />
            <div class="mx-auto max-w-8xl">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="flex flex-col justify-between md:flex-row mb-2">
                            <Button @click="addItem">Tambah Item</Button>
                            <div class="mb-3 mt-3 md:mt-0 md:text-right">
                                <SearchInput v-model:search="search" />
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Tipe</TableHead>
                                        <TableHead>Berat</TableHead>
                                        <TableHead>Harga Beli</TableHead>
                                        <TableHead>Harga Jual</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead class="w-8" />
                                        <TableHead class="w-8" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="item in items.data" :key="item.id">
                                        <TableCell>{{ item.code }}</TableCell>
                                        <TableCell>{{ item.name }}</TableCell>
                                        <TableCell>{{ item.type?.name || '-' }}</TableCell>
                                        <TableCell>{{ item.weight }} gr</TableCell>
                                        <TableCell>{{ formatRupiah(item.price_buy) }}</TableCell>
                                        <TableCell>{{ formatRupiah(item.price_sell) }}</TableCell>
                                        <TableCell>{{ item.status }}</TableCell>

                                        <TableCell>
                                            <EditButton @click="editItem(item)" />
                                        </TableCell>
                                        <TableCell>
                                            <DeleteButton @click="deleteItem(item)" />
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
                    <LoaderCircle v-if="addForm.processing" class="w-4 h-4 animate-spin mr-2" />
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
                    <LoaderCircle v-if="editForm.processing" class="w-4 h-4 animate-spin mr-2" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Delete Modal -->
    <Dialog :open="deleteModal" @update:open="(val) => deleteModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Hapus Item</DialogTitle>
                <DialogDescription>Yakin ingin menghapus item ini?</DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="deleteModal = false">Batal</Button>
                <Button variant="destructive" @click="handleDelete">Hapus</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
