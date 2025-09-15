<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Card, CardContent } from '@/components/ui/card'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import InputError from '@/components/InputError.vue'
import PageNav from '@/components/PageNav.vue'
import { LoaderCircle } from 'lucide-vue-next'
import type { BreadcrumbItem } from '@/types'
import EditButton from '@/components/EditButton.vue';
import DeleteButton from '@/components/DeleteButton.vue';
import Heading from '@/components/Heading.vue';

defineProps(['units'])

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Satuan', href: '#' },
]

const defaultForm = () => ({
    name: '',
})

const addForm = useForm(defaultForm())
const editForm = useForm({ id: '', ...defaultForm() })
const deleteForm = useForm({ id: '' })

const addModal = ref(false)
const editModal = ref(false)
const deleteModal = ref(false)

const openEdit = (row: any) => {
    editForm.id = row.id
    editForm.name = row.name
    editModal.value = true
}
const openDelete = (row: any) => {
    deleteForm.id = row.id
    deleteModal.value = true
}

const handleAdd = () => {
    addForm.post(route('outlet.units.store'), {
        preserveScroll: true,
        onSuccess: () => { addModal.value = false; addForm.reset() },
    })
}

const handleEdit = () => {
    editForm.patch(route('outlet.units.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => { editModal.value = false; editForm.reset() },
    })
}

const handleDelete = () => {
    router.delete(route('outlet.units.destroy', deleteForm.id), {
        preserveScroll: true,
        onSuccess: () => { deleteModal.value = false; deleteForm.reset() },
    })
}
</script>

<template>
    <Head title="Satuan" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8 space-y-6">
            <Heading class="mx-4" title="Satuan" description="Kelola daftar satuan produk" />
            <div class="mx-auto max-w-8xl">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="flex flex-col justify-between md:flex-row mb-2">
                            <div class="mb-3 md:mb-0">
                                <Button @click="addModal = true">Tambah Satuan</Button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama</TableHead>
                                        <TableHead class="w-8"></TableHead>
                                        <TableHead class="w-8"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="unit in units.data" :key="unit.id">
                                        <TableCell>{{ unit.name }}</TableCell>
                                        <TableCell class="px-0.5">
                                            <EditButton @click="openEdit(unit)"/>
                                        </TableCell>
                                        <TableCell class="px-0.5">
                                            <DeleteButton @click="openDelete(unit)"/>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="!units.total">
                                        <TableCell colspan="3" class="text-center text-gray-500">Data tidak ditemukan.</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <PageNav :data="units" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="addModal" @update:open="v => addModal = v">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tambah Satuan</DialogTitle>
                <DialogDescription>Masukkan nama satuan produk.</DialogDescription>
            </DialogHeader>
            <div class="space-y-3">
                <div>
                    <label class="text-sm mb-1 block">Nama</label>
                    <Input v-model="addForm.name" />
                    <InputError :message="addForm.errors.name" />
                </div>
            </div>
            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="addModal = false">Batal</Button>
                <Button :disabled="addForm.processing" @click="handleAdd">
                    <LoaderCircle v-if="addForm.processing" class="h-4 w-4 animate-spin" /> Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="editModal" @update:open="v => editModal = v">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Satuan</DialogTitle>
                <DialogDescription>Perbarui nama satuan produk.</DialogDescription>
            </DialogHeader>
            <div class="space-y-3">
                <div>
                    <label class="text-sm mb-1 block">Nama</label>
                    <Input v-model="editForm.name" />
                    <InputError :message="editForm.errors.name" />
                </div>
            </div>
            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="editModal = false">Batal</Button>
                <Button :disabled="editForm.processing" @click="handleEdit">
                    <LoaderCircle v-if="editForm.processing" class="h-4 w-4 animate-spin" /> Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="deleteModal" @update:open="v => deleteModal = v">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Hapus Satuan</DialogTitle>
                <DialogDescription>Data akan dihapus permanen.</DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="deleteModal = false">Batal</Button>
                <Button variant="destructive" @click="handleDelete">Hapus</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
