<script lang="ts" setup>
import DeleteButton from '@/components/DeleteButton.vue';
import EditButton from '@/components/EditButton.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { GitMerge, LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import Multiselect from '@vueform/multiselect';

defineProps(['itemTypes']);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Jenis Item', href: '#' },
];

const defaultForm = () => ({
    name: '',
});

const addForm = useForm(defaultForm());
const editForm = useForm({ id: '', ...defaultForm() });
const mergeForm = useForm({
    source_id: '',
    target_id: '',
});

const addModal = ref(false);
const editModal = ref(false);
const mergeModal = ref(false);
const sourceName = ref('');

const openEdit = (row: any) => {
    editForm.id = row.id;
    editForm.name = row.name;
    editModal.value = true;
};

const openMerge = (row: any) => {
    sourceName.value = row.name;
    mergeForm.source_id = row.id;
    mergeForm.target_id = '';
    mergeModal.value = true;
};

const handleAdd = () => {
    addForm.post(route('store.item-types.store'), {
        preserveScroll: true,
        onSuccess: () => {
            addModal.value = false;
            addForm.reset();
        },
    });
};

const handleEdit = () => {
    editForm.patch(route('store.item-types.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            editModal.value = false;
            editForm.reset();
        },
    });
};

const handleMerge = () => {
    mergeForm.post(route('store.item-types.merge'), {
        preserveScroll: true,
        onSuccess: () => {
            mergeModal.value = false;
            mergeForm.reset();
        },
    });
};

const handleDelete = (id: any) => {
    router.delete(route('store.item-types.destroy', id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Jenis Item" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 py-8">
            <Heading class="mx-4" title="Jenis Item" description="Kelola daftar jenis item" />
            <div class="max-w-8xl mx-auto">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="mb-4 flex justify-between">
                            <Button @click="addModal = true">Tambah Jenis Item</Button>
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
                                    <TableRow v-for="t in itemTypes" :key="t.id">
                                        <TableCell>{{ t.name }}</TableCell>
                                        <TableCell class="flex items-center justify-end gap-1 text-right">
                                            <Button variant="outline" size="sm" @click="openMerge(t)" title="Gabungkan">
                                                <GitMerge class="h-4 w-4" />
                                            </Button>
                                            <EditButton @click="openEdit(t)" />
                                        </TableCell>
                                        <TableCell class="px-0.5">
                                            <DeleteButton @confirm="handleDelete(t.id)" />
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="!itemTypes.length">
                                        <TableCell colspan="4" class="text-center text-gray-500"> Data tidak ditemukan. </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <!-- Add Modal -->
    <Dialog :open="addModal" @update:open="(v) => (addModal = v)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tambah Jenis Item</DialogTitle>
                <DialogDescription>Masukkan nama item type.</DialogDescription>
            </DialogHeader>

            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-sm">Nama</label>
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

    <!-- Edit Modal -->
    <Dialog :open="editModal" @update:open="(v) => (editModal = v)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Jenis Item</DialogTitle>
                <DialogDescription>Perbarui nama item type.</DialogDescription>
            </DialogHeader>

            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-sm">Nama</label>
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

    <!-- Merge Modal -->
    <Dialog :open="mergeModal" @update:open="(v) => (mergeModal = v)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Gabungkan Jenis Item</DialogTitle>
                <DialogDescription>
                    Pindahkan semua item dari <strong>{{ sourceName }}</strong> ke jenis item yang dipilih di bawah ini. Jenis item
                    <strong>{{ sourceName }}</strong> akan dihapus setelah proses selesai.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div>
                    <Label>Pilih Jenis Item Tujuan</Label>
                    <Multiselect
                        v-model="mergeForm.target_id"
                        :options="itemTypes.filter((o: any) => o.id !== mergeForm.source_id)"
                        label="name"
                        value-prop="id"
                        searchable
                        placeholder="Pilih Tujuan..."
                        class="mt-1"
                    />
                    <InputError :message="mergeForm.errors.target_id" />
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="mergeModal = false">Batal</Button>
                <Button :disabled="mergeForm.processing || !mergeForm.target_id" @click="handleMerge" variant="destructive">
                    <LoaderCircle v-if="mergeForm.processing" class="h-4 w-4 animate-spin" /> Gabungkan & Hapus
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<style src="@vueform/multiselect/themes/default.css" />
