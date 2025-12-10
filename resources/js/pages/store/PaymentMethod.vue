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
import { Badge } from '@/components/ui/badge'
import {
    Select, SelectContent, SelectGroup, SelectItem,
    SelectTrigger, SelectValue
} from '@/components/ui/select'

defineProps(['payment_methods'])

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Metode Pembayaran', href: '#' },
]

// default form
const defaultForm = () => ({
    name: '',
    is_active: 1,
})

const addForm = useForm(defaultForm())
const editForm = useForm({ _method: 'patch', id: '', ...defaultForm() })

const addModal = ref(false)
const editModal = ref(false)

const openEdit = (row: any) => {
    editForm.id = row.id
    editForm.name = row.name
    editForm.is_active = row.is_active
    editModal.value = true
}

const handleAdd = () => {
    addForm.post(route('store.payment-methods.store'), {
        preserveScroll: true,
        onSuccess: () => {
            addModal.value = false
            addForm.reset()
        },
    })
}

const handleEdit = () => {
    editForm.post(route('store.payment-methods.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            editModal.value = false
            editForm.reset()
        },
    })
}

const handleDelete = (paymentId: number) => {
    router.delete(route('store.payment-methods.destroy', paymentId), {
        preserveScroll: true,
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
                            <div class="flex justify-between mb-3">
                                <Button @click="addModal = true">Tambah Metode Pembayaran</Button>
                            </div>

                            <div class="overflow-x-auto">
                                <Table class="w-full">
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Nama</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead class="w-8" />
                                            <TableHead class="w-8" />
                                        </TableRow>
                                    </TableHeader>

                                    <TableBody>
                                        <TableRow v-for="pm in payment_methods.data" :key="pm.id">
                                            <TableCell>{{ pm.name }}</TableCell>

                                            <TableCell>
                                                <Badge
                                                    :variant="pm.is_active ? 'success' : 'destructive'"
                                                    class="capitalize"
                                                >
                                                    {{ pm.is_active ? 'Aktif' : 'Nonaktif' }}
                                                </Badge>
                                            </TableCell>

                                            <TableCell class="px-1">
                                                <EditButton @click="openEdit(pm)" />
                                            </TableCell>

                                            <TableCell class="px-1">
                                                <DeleteButton @confirm="handleDelete(pm.id)" />
                                            </TableCell>
                                        </TableRow>

                                        <TableRow v-if="!payment_methods.total">
                                            <TableCell colspan="4" class="text-center text-gray-500">
                                                Data tidak ditemukan.
                                            </TableCell>
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

    <!-- Modal Tambah -->
    <Dialog :open="addModal" @update:open="v => addModal = v">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tambah Metode Pembayaran</DialogTitle>
                <DialogDescription>Masukkan detail metode pembayaran.</DialogDescription>
            </DialogHeader>

            <div class="space-y-3">
                <div>
                    <Label class="text-sm mb-1 block">Nama</Label>
                    <Input v-model="addForm.name" />
                    <InputError :message="addForm.errors.name" />
                </div>

                <div>
                    <Label class="text-sm mb-1 block">Status</Label>

                    <Select v-model="addForm.is_active">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Pilih Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem :value="1">Aktif</SelectItem>
                                <SelectItem :value="0">Nonaktif</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>

                    <InputError :message="addForm.errors.is_active" />
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="addModal = false">Batal</Button>
                <Button :disabled="addForm.processing" @click="handleAdd">
                    <LoaderCircle v-if="addForm.processing" class="h-4 w-4 animate-spin" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Modal Edit -->
    <Dialog :open="editModal" @update:open="v => editModal = v">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Metode Pembayaran</DialogTitle>
                <DialogDescription>Perbarui data metode pembayaran.</DialogDescription>
            </DialogHeader>

            <div class="space-y-3">
                <div>
                    <Label class="text-sm mb-1 block">Nama</Label>
                    <Input v-model="editForm.name" />
                    <InputError :message="editForm.errors.name" />
                </div>

                <div>
                    <Label class="text-sm mb-1 block">Status</Label>

                    <Select v-model="editForm.is_active">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Pilih Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem :value="1">Aktif</SelectItem>
                                <SelectItem :value="0">Nonaktif</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>

                    <InputError :message="editForm.errors.is_active" />
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="editModal = false">Batal</Button>
                <Button :disabled="editForm.processing" @click="handleEdit">
                    <LoaderCircle v-if="editForm.processing" class="h-4 w-4 animate-spin" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

</template>
