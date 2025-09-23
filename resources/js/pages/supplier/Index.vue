<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Card, CardContent } from '@/components/ui/card'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import DeleteButton from '@/components/DeleteButton.vue'
import EditButton from '@/components/EditButton.vue'
import SearchInput from '@/components/SearchInput.vue'
import PageNav from '@/components/PageNav.vue'
import Heading from '@/components/Heading.vue'

import type { BreadcrumbItem } from '@/types'
import { useSearch } from '@/composables/useSearch'
import { LoaderCircle } from 'lucide-vue-next'
import SupplierForm from '@/pages/supplier/partial/SupplierForm.vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Supplier', href: '#' },
]

const { suppliers } = defineProps<{ suppliers: any }>()

const { search } = useSearch('master.suppliers.index', '', ['suppliers'])

const defaultForm = () => ({
    name: '',
    phone: '',
    address: '',
})

const addForm = useForm(defaultForm())
const editForm = useForm({ id: '', ...defaultForm() })
const deleteForm = useForm({ id: '' })

const addSupplierModal = ref(false)
const editSupplierModal = ref(false)
const deleteSupplierModal = ref(false)

const addSupplier = () => {
    Object.assign(addForm, defaultForm())
    addSupplierModal.value = true
}

const editSupplier = (supplier: any) => {
    editForm.id = supplier.id
    Object.assign(editForm, supplier)
    editSupplierModal.value = true
}

const deleteSupplier = (supplier: any) => {
    deleteForm.id = supplier.id
    deleteSupplierModal.value = true
}

const handleAddSupplier = () => {
    addForm.post(route('outlet.suppliers.store'), {
        preserveScroll: true,
        onSuccess: () => { addSupplierModal.value = false; addForm.reset() },
    })
}

const handleEditSupplier = () => {
    editForm.patch(route('outlet.suppliers.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => { editSupplierModal.value = false; editForm.reset() },
    })
}

const handleDeleteSupplier = () => {
    router.delete(route('outlet.suppliers.destroy', deleteForm.id), {
        preserveScroll: true,
        onSuccess: () => { deleteSupplierModal.value = false; deleteForm.reset() },
    })
}
</script>

<template>
    <Head title="Supplier" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Supplier" description="Kelola data supplier toko Anda" />
            <div class="mx-auto max-w-8xl">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="mb-2 flex flex-col justify-between md:flex-row">
                            <div class="mb-3 md:mb-0">
                                <Button @click="addSupplier">Tambah Supplier</Button>
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
                                        <TableHead>Telepon</TableHead>
                                        <TableHead>Alamat</TableHead>
                                        <TableHead class="w-8" />
                                        <TableHead class="w-8" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="supplier in suppliers.data" :key="supplier.id">
                                        <TableCell>{{ supplier.name }}</TableCell>
                                        <TableCell>{{ supplier.phone || '-' }}</TableCell>
                                        <TableCell>
                                            <span class="line-clamp-2 max-w-[520px]">{{ supplier.address || '-' }}</span>
                                        </TableCell>
                                        <TableCell class="px-1">
                                            <EditButton @click="editSupplier(supplier)" />
                                        </TableCell>
                                        <TableCell class="px-1">
                                            <DeleteButton @click="deleteSupplier(supplier)" />
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="!suppliers.total">
                                        <TableCell colspan="5">Supplier tidak ditemukan</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <PageNav :data="suppliers" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="addSupplierModal" @update:open="(v)=>addSupplierModal=v">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tambah Supplier</DialogTitle>
                <DialogDescription>Isi form untuk menambahkan supplier baru.</DialogDescription>
            </DialogHeader>
            <SupplierForm v-model:form="addForm" />
            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="addSupplierModal=false">Batal</Button>
                <Button :class="{ 'opacity-25': addForm.processing }" :disabled="addForm.processing" @click="handleAddSupplier">
                    <LoaderCircle v-if="addForm.processing" class="h-4 w-4 animate-spin" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="editSupplierModal" @update:open="(v)=>editSupplierModal=v">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Supplier</DialogTitle>
                <DialogDescription>Ubah data supplier yang dipilih.</DialogDescription>
            </DialogHeader>
            <SupplierForm v-model:form="editForm" />
            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="editSupplierModal=false">Batal</Button>
                <Button :class="{ 'opacity-25': editForm.processing }" :disabled="editForm.processing" @click="handleEditSupplier">
                    <LoaderCircle v-if="editForm.processing" class="h-4 w-4 animate-spin" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="deleteSupplierModal" @update:open="(v)=>deleteSupplierModal=v">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Hapus Supplier</DialogTitle>
                <DialogDescription class="mt-2">
                    Yakin ingin menghapus supplier ini? Tindakan ini tidak dapat dibatalkan.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="deleteSupplierModal=false">Batal</Button>
                <Button variant="destructive" @click="handleDeleteSupplier">Hapus</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
