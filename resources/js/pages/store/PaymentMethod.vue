<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import StoreLayout from '@/layouts/store/Layout.vue';
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
import HeadingSmall from '@/components/HeadingSmall.vue';

defineProps(['payment_methods'])

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Metode Pembayaran', href: '#' },
]

const defaultForm = () => ({
    name: '',
    code: '',
    image: null,
})

const addForm = useForm(defaultForm())
const editForm = useForm({_method: 'patch', id: '', ...defaultForm() })
const deleteForm = useForm({ id: '' })

const addModal = ref(false)
const editModal = ref(false)
const deleteModal = ref(false)

const openEdit = (row: any) => {
    editForm.id = row.id
    editForm.name = row.name
    editForm.code = row.code
    editModal.value = true
}
const openDelete = (row: any) => {
    deleteForm.id = row.id
    deleteModal.value = true
}

const handleAdd = () => {
    addForm.post(route('store.payment-methods.store'), {
        preserveScroll: true,
        onSuccess: () => { addModal.value = false; addForm.reset() },
    })
}

const handleEdit = () => {
    if (!(editForm.image as any instanceof File)) editForm.image = null

    editForm.post(route('store.payment-methods.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => { editModal.value = false; editForm.reset() },
    })
}

const handleDelete = () => {
    router.delete(route('store.payment-methods.destroy', deleteForm.id), {
        preserveScroll: true,
        onSuccess: () => { deleteModal.value = false; deleteForm.reset() },
    })
}
</script>

<template>
    <Head title="Metode Pembayaran" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <StoreLayout>
            <div class="space-y-6">
                <HeadingSmall class="mx-4" title="Metode Pembayaran" description="Kelola metode pembayaran untuk kasir" />
                <div class="mx-auto max-w-8xl">
                    <Card class="py-4 md:mx-4">
                        <CardContent>
                            <div class="flex flex-col justify-between md:flex-row mb-2">
                                <div class="mb-3 md:mb-0">
                                    <Button @click="addModal = true">Tambah Metode Pembayaran</Button>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <Table class="w-full">
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead class="w-16">Gambar</TableHead>
                                            <TableHead>Nama</TableHead>
                                            <TableHead>Kode</TableHead>
                                            <TableHead class="w-8"></TableHead>
                                            <TableHead class="w-8"></TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="pm in payment_methods.data" :key="pm.id">
                                            <TableCell>
                                                <img v-if="pm.image_path" :src="'/storage/' + pm.image_path" alt="logo" class="w-16 object-contain" />
                                            </TableCell>
                                            <TableCell>{{ pm.name }}</TableCell>
                                            <TableCell>{{ pm.code }}</TableCell>
                                            <TableCell class="px-0.5">
                                                <EditButton @click="openEdit(pm)"/>
                                            </TableCell>
                                            <TableCell class="px-0.5">
                                                <DeleteButton @click="openDelete(pm)"/>
                                            </TableCell>
                                        </TableRow>
                                        <TableRow v-if="!payment_methods.total">
                                            <TableCell colspan="6" class="text-center text-gray-500">Data tidak ditemukan.</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                                <PageNav :data="payment_methods" />
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </StoreLayout>
    </AppLayout>

    <Dialog :open="addModal" @update:open="v => addModal = v">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tambah Metode Pembayaran</DialogTitle>
                <DialogDescription>Masukkan detail metode pembayaran.</DialogDescription>
            </DialogHeader>
            <div class="space-y-3">
                <div>
                    <label class="text-sm mb-1 block">Nama</label>
                    <Input v-model="addForm.name" />
                    <InputError :message="addForm.errors.name" />
                </div>
                <div>
                    <label class="text-sm mb-1 block">Kode</label>
                    <Input v-model="addForm.code" />
                    <InputError :message="addForm.errors.code" />
                </div>
                <div>
                    <label class="text-sm mb-1 block">Gambar</label>
                    <Input type="file"
                           @change="(e: any) => addForm.image = e.target.files[0]" />
                    <InputError :message="addForm.errors.image" />
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
                <DialogTitle>Edit Metode Pembayaran</DialogTitle>
                <DialogDescription>Perbarui data metode pembayaran.</DialogDescription>
            </DialogHeader>
            <div class="space-y-3">
                <div>
                    <label class="text-sm mb-1 block">Nama</label>
                    <Input v-model="editForm.name" />
                    <InputError :message="editForm.errors.name" />
                </div>
                <div>
                    <label class="text-sm mb-1 block">Kode</label>
                    <Input v-model="editForm.code" />
                    <InputError :message="editForm.errors.code" />
                </div>
                <div>
                    <label class="text-sm mb-1 block">Gambar</label>
                    <Input type="file"
                           @change="(e: any) => editForm.image = e.target.files[0]" />
                    <InputError :message="editForm.errors.image" />
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
                <DialogTitle>Hapus Metode Pembayaran</DialogTitle>
                <DialogDescription>Data akan dihapus permanen.</DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="deleteModal = false">Batal</Button>
                <Button variant="destructive" @click="handleDelete">Hapus</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
