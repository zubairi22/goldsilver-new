<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import {Head, router, useForm} from '@inertiajs/vue3';
import {ref} from "vue";
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import DeleteButton from "@/components/DeleteButton.vue";
import EditButton from "@/components/EditButton.vue";
import SearchInput from '@/components/SearchInput.vue';
import PageNav from '@/components/PageNav.vue';
import Heading from '@/components/Heading.vue';
import ProductForm from '@/pages/product/partial/ProductForm.vue';
import { useSearch } from '@/composables/useSearch';
import { LoaderCircle } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';
import { useFormat } from '@/composables/useFormat';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produk', href: '#' },
];

defineProps(['products', 'categories', 'units']);

const { search } = useSearch('outlet.products.index', '', ['products']);

const {formatRupiah} = useFormat()

const defaultForm = () => ({
    name: '',
    stock: '',
    category_id: '',
    units: [] as {
        id: number | null;
        pivot: {
            sku: string;
            purchase_price: string;
            selling_price: string;
            conversion: number;
        }
    }[]
});


const addForm = useForm(defaultForm());
const editForm = useForm({ id: '', ...defaultForm() });
const deleteForm = useForm({ id: '' });

const addModal = ref(false);
const editModal = ref(false);
const deleteModal = ref(false);

const addProduct = () => {
    addForm.units.push({
        id: null,
        pivot: {
            sku: '',
            purchase_price: '0',
            selling_price: '0',
            conversion: 1,
        }
    });
    addModal.value = true;
}

const editProduct = (product: any) => {
    Object.assign(editForm, product);
    editModal.value = true;
};

const deleteProduct = (product: any) => {
    deleteForm.id = product.id;
    deleteModal.value = true;
};

const handleAdd = () => {
    addForm.post(route('products.store'), {
        preserveScroll: true,
        onSuccess: () => {
            addModal.value = false;
            addForm.reset();
        },
    });
};

const handleEdit = () => {
    editForm.patch(route('products.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            editModal.value = false;
            editForm.reset();
        },
    });
};

const handleDelete = () => {
    router.delete(route('products.destroy', deleteForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteModal.value = false;
            deleteForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Produk" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Produk" description="Kelola data produk yang tersedia di toko Anda" />
            <div class="mx-auto max-w-8xl">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="flex flex-col justify-between md:flex-row mb-2">
                            <Button @click="addProduct">Tambah Produk</Button>
                            <div class="mb-3 mt-3 md:mt-0 md:text-right">
                                <SearchInput v-model="search"/>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>SKU</TableHead>
                                        <TableHead>Harga Beli</TableHead>
                                        <TableHead>Harga Jual</TableHead>
                                        <TableHead>Stok</TableHead>
                                        <TableHead class="w-8" />
                                        <TableHead class="w-8" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="product in products.data" :key="product.id">
                                        <TableCell>{{ product.name }}</TableCell>
                                        <TableCell>{{ product.units[0].pivot.sku || '-' }}</TableCell>
                                        <TableCell>
                                            {{ product.units.length ? formatRupiah(product.units[0].pivot.purchase_price) : 'N/A' }}
                                        </TableCell>
                                        <TableCell>
                                            {{ product.units.length ? formatRupiah(product.units[0].pivot.selling_price) : 'N/A' }}
                                        </TableCell>
                                        <TableCell>{{ product.stock }}</TableCell>
                                        <TableCell class="px-1">
                                            <EditButton @click="editProduct(product)" />
                                        </TableCell>
                                        <TableCell class="px-1">
                                            <DeleteButton @click="deleteProduct(product)" />
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="!products.total">
                                        <TableCell colspan="6">Produk tidak ditemukan</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <PageNav :data="products" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="addModal" @update:open="(val) => addModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tambah Produk</DialogTitle>
                <DialogDescription>Isi form untuk menambahkan produk baru.</DialogDescription>
            </DialogHeader>
            <ProductForm v-model:form="addForm" :categories="categories" :units="units" />
            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="addModal = false">Batal</Button>
                <Button :disabled="addForm.processing" @click="handleAdd">
                    <LoaderCircle v-if="addForm.processing" class="w-4 h-4 animate-spin mr-2" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="editModal" @update:open="(val) => editModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Produk</DialogTitle>
                <DialogDescription>Ubah informasi produk yang dipilih.</DialogDescription>
            </DialogHeader>
            <ProductForm v-model:form="editForm" :categories="categories" :units="units"/>
            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="editModal = false">Batal</Button>
                <Button :disabled="editForm.processing" @click="handleEdit">
                    <LoaderCircle v-if="editForm.processing" class="w-4 h-4 animate-spin mr-2" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="deleteModal" @update:open="(val) => deleteModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Hapus Produk</DialogTitle>
                <DialogDescription>Yakin ingin menghapus produk ini?</DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="deleteModal = false">Batal</Button>
                <Button variant="destructive" @click="handleDelete">Hapus</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
