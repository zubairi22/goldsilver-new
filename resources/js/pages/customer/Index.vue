<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import DeleteButton from '@/components/DeleteButton.vue';
import EditButton from '@/components/EditButton.vue';
import SearchInput from '@/components/SearchInput.vue';
import PageNav from '@/components/PageNav.vue';
import Heading from '@/components/Heading.vue';
import CustomerForm from './CustomerForm.vue'; // Buat file ini
import type { BreadcrumbItem } from '@/types';
import { useSearch } from '@/composables/useSearch';
import { LoaderCircle } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pelanggan', href: '#' },
];

const { customers } = defineProps(['customers']);
const { search } = useSearch('customers.index', '', ['customers']);

const defaultForm = () => ({
    name: '', phone: '', email: '', address: ''
});

const addForm = useForm(defaultForm());
const editForm = useForm({ id: '', ...defaultForm() });
const deleteForm = useForm({ id: '' });

const addCustomerModal = ref(false);
const editCustomerModal = ref(false);
const deleteCustomerModal = ref(false);

const editCustomer = (customer: any) => {
    Object.assign(editForm, customer);
    editCustomerModal.value = true;
};

const deleteCustomer = (customer: any) => {
    deleteForm.id = customer.id;
    deleteCustomerModal.value = true;
};

const handleAddCustomer = () => {
    addForm.post(route('customers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            addCustomerModal.value = false;
            addForm.reset();
        },
    });
};

const handleEditCustomer = () => {
    editForm.patch(route('customers.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            editCustomerModal.value = false;
            editForm.reset();
        },
    });
};

const handleDeleteCustomer = () => {
    router.delete(route('customers.destroy', deleteForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteCustomerModal.value = false;
            deleteForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Pelanggan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Pelanggan" description="Kelola data pelanggan Anda" />

            <div class="mx-auto max-w-8xl">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="flex flex-col justify-between md:flex-row mb-2">
                            <div class="mb-3 md:mb-0">
                                <Button @click="addCustomerModal = true">Tambah Pelanggan</Button>
                            </div>
                            <div class="mb-3 md:text-right">
                                <SearchInput v-model="search" />
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Telepon</TableHead>
                                        <TableHead>Alamat</TableHead>
                                        <TableHead class="w-8" />
                                        <TableHead class="w-8" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="customer in customers.data" :key="customer.id">
                                        <TableCell>{{ customer.name }}</TableCell>
                                        <TableCell>{{ customer.email }}</TableCell>
                                        <TableCell>{{ customer.phone }}</TableCell>
                                        <TableCell>{{ customer.address }}</TableCell>
                                        <TableCell class="px-1">
                                            <EditButton @click="editCustomer(customer)" />
                                        </TableCell>
                                        <TableCell class="px-1">
                                            <DeleteButton @click="deleteCustomer(customer)" />
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="!customers.total">
                                        <TableCell colspan="6">Pelanggan tidak ditemukan</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <PageNav :data="customers" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="addCustomerModal" @update:open="(val) => addCustomerModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tambah Pelanggan</DialogTitle>
                <DialogDescription>Masukkan data pelanggan baru ke dalam form berikut.</DialogDescription>
            </DialogHeader>

            <CustomerForm v-model:form="addForm" />

            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="addCustomerModal = false">Batal</Button>
                <Button :disabled="addForm.processing" @click="handleAddCustomer">
                    <LoaderCircle v-if="addForm.processing" class="h-4 w-4 animate-spin" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="editCustomerModal" @update:open="(val) => editCustomerModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Pelanggan</DialogTitle>
                <DialogDescription>Perbarui data pelanggan di form berikut.</DialogDescription>
            </DialogHeader>

            <CustomerForm v-model:form="editForm" />

            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="editCustomerModal = false">Batal</Button>
                <Button :disabled="editForm.processing" @click="handleEditCustomer">
                    <LoaderCircle v-if="editForm.processing" class="h-4 w-4 animate-spin" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="deleteCustomerModal" @update:open="(val) => deleteCustomerModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Hapus Pelanggan</DialogTitle>
                <DialogDescription class="mt-2">
                    Apakah Anda yakin ingin menghapus pelanggan ini? Data akan hilang permanen.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="deleteCustomerModal = false">Batal</Button>
                <Button variant="destructive" @click="handleDeleteCustomer">Hapus</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
