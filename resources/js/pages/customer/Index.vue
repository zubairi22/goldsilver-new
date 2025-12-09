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
import CustomerForm from './partial/CustomerForm.vue';
import type { BreadcrumbItem } from '@/types';
import { useSearch } from '@/composables/useSearch';
import { LoaderCircle } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pelanggan', href: '#' },
];

const { customers } = defineProps(['customers']);
const { search } = useSearch('store.customers.index', route().params.search, ['customers']);

const defaultForm = () => ({
    name: '',
    phone: '',
    address: '',
});

const addForm = useForm(defaultForm());
const editForm = useForm({ id: '', ...defaultForm() });

const addCustomerModal = ref(false);
const editCustomerModal = ref(false);
const deleteCustomerModal = ref(false);

/* Edit customer */
const editCustomer = (customer: any) => {
    editForm.id = customer.id;
    editForm.name = customer.name;
    editForm.phone = customer.phone;
    editForm.address = customer.address;

    editCustomerModal.value = true;
};

/* Add customer */
const handleAddCustomer = () => {
    addForm.post(route('store.customers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            addCustomerModal.value = false;
            addForm.reset();
        },
    });
};

/* Update customer */
const handleEditCustomer = () => {
    editForm.patch(route('store.customers.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            editCustomerModal.value = false;
            editForm.reset();
        },
    });
};

/* Delete customer */
const handleDeleteCustomer = (customerId: number) => {
    router.delete(route('store.customers.destroy', customerId), {
        preserveScroll: true,
        onSuccess: () => {
            deleteCustomerModal.value = false;
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

                        <!-- Header -->
                        <div class="flex flex-col justify-between md:flex-row mb-2">
                            <Button @click="addCustomerModal = true">Tambah Pelanggan</Button>
                            <div class="mt-3 md:mt-0 md:text-right">
                                <SearchInput v-model:search="search" />
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Telepon</TableHead>
                                        <TableHead>Alamat</TableHead>
                                        <TableHead class="w-8" />
                                        <TableHead class="w-8" />
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow v-for="customer in customers.data" :key="customer.id">
                                        <TableCell>{{ customer.name }}</TableCell>
                                        <TableCell>{{ customer.phone || '-' }}</TableCell>
                                        <TableCell>{{ customer.address || '-' }}</TableCell>

                                        <TableCell class="px-0.5">
                                            <EditButton @click="editCustomer(customer)" />
                                        </TableCell>
                                        <TableCell class="px-0.5">
                                            <DeleteButton @confirm="handleDeleteCustomer(customer.id)" />
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="!customers.total">
                                        <TableCell colspan="5" class="text-center text-gray-500">
                                            Pelanggan tidak ditemukan.
                                        </TableCell>
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

    <!-- ADD CUSTOMER MODAL -->
    <Dialog :open="addCustomerModal" @update:open="(val) => addCustomerModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tambah Pelanggan</DialogTitle>
                <DialogDescription>Masukkan data pelanggan baru.</DialogDescription>
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

    <!-- EDIT CUSTOMER MODAL -->
    <Dialog :open="editCustomerModal" @update:open="(val) => editCustomerModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Pelanggan</DialogTitle>
                <DialogDescription>Perbarui data pelanggan.</DialogDescription>
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
</template>
