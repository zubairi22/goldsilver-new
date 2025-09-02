<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import OutletLayout from '@/layouts/outlet/Layout.vue';
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Card, CardContent } from '@/components/ui/card'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Input } from '@/components/ui/input'
import InputError from '@/components/InputError.vue'
import PageNav from '@/components/PageNav.vue'
import { LoaderCircle } from 'lucide-vue-next'
import type { BreadcrumbItem } from '@/types'
import EditButton from '@/components/EditButton.vue';
import DeleteButton from '@/components/DeleteButton.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';

const { accounts } = defineProps(['accounts'])

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Akun Keuangan', href: '#' },
]

const defaultForm = () => ({
    name: '',
    code: '',
    type: 'cash',
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
    editForm.code = row.code
    editForm.type = row.type
    editModal.value = true
}
const openDelete = (row: any) => {
    deleteForm.id = row.id
    deleteModal.value = true
}

const handleAdd = () => {
    addForm.post(route('outlet.financial-accounts.store'), {
        preserveScroll: true,
        onSuccess: () => { addModal.value = false; addForm.reset() },
    })
}

const handleEdit = () => {
    editForm.patch(route('outlet.financial-accounts.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => { editModal.value = false; editForm.reset() },
    })
}

const handleDelete = () => {
    router.delete(route('outlet.financial-accounts.destroy', deleteForm.id), {
        preserveScroll: true,
        onSuccess: () => { deleteModal.value = false; deleteForm.reset() },
    })
}
</script>

<template>
    <Head title="Akun Keuangan" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <OutletLayout>
            <div class="space-y-6">
                <HeadingSmall class="mx-4" title="Akun Keuangan" description="Kelola akun kas/bank/e-wallet" />
                <div class="mx-auto max-w-8xl">
                    <Card class="py-4 md:mx-4">
                        <CardContent>
                            <div class="flex flex-col justify-between md:flex-row mb-2">
                                <div class="mb-3 md:mb-0">
                                    <Button @click="addModal = true">Tambah Akun</Button>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <Table class="w-full">
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Nama</TableHead>
                                            <TableHead>Kode</TableHead>
                                            <TableHead>Tipe</TableHead>
                                            <TableHead class="w-8"></TableHead>
                                            <TableHead class="w-8"></TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="acc in accounts.data" :key="acc.id">
                                            <TableCell>{{ acc.name }}</TableCell>
                                            <TableCell>{{ acc.code }}</TableCell>
                                            <TableCell class="capitalize">{{ acc.type }}</TableCell>
                                            <TableCell class="px-0.5">
                                                <EditButton @click="openEdit(acc)"/>
                                            </TableCell>
                                            <TableCell class="px-0.5">
                                                <DeleteButton @click="openDelete(acc)"/>
                                            </TableCell>
                                        </TableRow>
                                        <TableRow v-if="!accounts.total">
                                            <TableCell colspan="6" class="text-center text-gray-500">Data tidak ditemukan.</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                                <PageNav :data="accounts" />
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </OutletLayout>
    </AppLayout>

    <Dialog :open="addModal" @update:open="v => addModal = v">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tambah Akun</DialogTitle>
                <DialogDescription>Masukkan detail akun keuangan.</DialogDescription>
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
                    <label class="text-sm mb-1 block">Tipe</label>
                    <Select v-model="addForm.type">
                        <SelectTrigger><SelectValue placeholder="Pilih tipe" /></SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="cash">Cash</SelectItem>
                                <SelectItem value="bank">Bank</SelectItem>
                                <SelectItem value="e-wallet">E-Wallet</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <InputError :message="addForm.errors.type" />
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
                <DialogTitle>Edit Akun</DialogTitle>
                <DialogDescription>Perbarui data akun keuangan.</DialogDescription>
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
                    <label class="text-sm mb-1 block">Tipe</label>
                    <Select v-model="editForm.type">
                        <SelectTrigger><SelectValue placeholder="Pilih tipe" /></SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="cash">Cash</SelectItem>
                                <SelectItem value="bank">Bank</SelectItem>
                                <SelectItem value="ewallet">E-Wallet</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <InputError :message="editForm.errors.type" />
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
                <DialogTitle>Hapus Akun</DialogTitle>
                <DialogDescription>Data akan dihapus permanen.</DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="deleteModal = false">Batal</Button>
                <Button variant="destructive" @click="handleDelete">Hapus</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
