<script lang="ts" setup>
import DeleteButton from '@/components/DeleteButton.vue';
import EditButton from '@/components/EditButton.vue';
import Heading from '@/components/Heading.vue';
import ImageModal from '@/components/ImageModal.vue'; // ⬅ Tambahkan ini
import PageNav from '@/components/PageNav.vue';
import SearchInput from '@/components/SearchInput.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useSearch } from '@/composables/useSearch';
import AppLayout from '@/layouts/AppLayout.vue';
import UserForm from '@/pages/master/user/UserForm.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pengguna', href: '#' },
];

defineProps(['users', 'roles', 'can']);

const { search } = useSearch('master.users.index', route().params.search, ['users']);

const defaultForm = () => ({
    name: '',
    email: '',
    role: '',
    password: '',
});

const addForm = useForm(defaultForm());
const editForm = useForm({ id: '', ...defaultForm() });

const addUserModal = ref(false);
const editUserModal = ref(false);

const editUser = (user: any) => {
    Object.assign(editForm, user);
    editForm.role = user.roles[0].id;
    editForm.password = '';
    editUserModal.value = true;
};

const handleAddUser = () => {
    addForm.post(route('master.users.store'), {
        preserveScroll: true,
        onSuccess: () => {
            addUserModal.value = false;
            addForm.reset();
        },
    });
};

const handleEditUser = () => {
    editForm.patch(route('master.users.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            editUserModal.value = false;
            editForm.reset();
        },
    });
};

const handleDeleteUser = (userId: number) => {
    router.delete(route('master.users.destroy', userId), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Pengguna" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Pengguna" description="Kelola pengguna di aplikasi Anda" />

            <div class="max-w-8xl mx-auto">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="mb-2 flex flex-col justify-between md:flex-row">
                            <div class="mb-3 md:mb-0">
                                <Button @click="addUserModal = true">Tambah Pengguna</Button>
                            </div>

                            <div class="mb-3 md:text-right">
                                <SearchInput v-model:search="search" />
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Username</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead class="text-center">Role</TableHead>
                                        <TableHead class="text-center">QR</TableHead>
                                        <TableHead class="w-8" />
                                        <TableHead class="w-8" />
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow v-for="user in users.data" :key="user.id">
                                        <TableCell>{{ user.name }}</TableCell>
                                        <TableCell>{{ user.email }}</TableCell>

                                        <TableCell class="text-center">
                                            <Badge>{{ user.roles[0].name }}</Badge>
                                        </TableCell>

                                        <TableCell>
                                            <div class="flex justify-center">
                                                <ImageModal
                                                    v-if="user.qr_path"
                                                    :src="`/storage/${user.qr_path}`"
                                                    :filename="`qr-user-${user.name.replace(/\s+/g, '_')}.png`"
                                                    trigger
                                                />
                                            </div>
                                        </TableCell>

                                        <TableCell class="px-1">
                                            <EditButton v-show="can.update && user.roles[0].name !== 'super-admin'" @click="editUser(user)" />
                                        </TableCell>

                                        <TableCell class="px-1">
                                            <DeleteButton
                                                v-show="can.delete && user.roles[0].name !== 'super-admin'"
                                                @confirm="handleDeleteUser(user.id)"
                                            />
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>

                            <PageNav :data="users" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <!-- Add User Modal -->
    <Dialog :open="addUserModal" @update:open="(val) => (addUserModal = val)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tambah Pengguna</DialogTitle>
                <DialogDescription>Masukkan data pengguna baru ke dalam form berikut.</DialogDescription>
            </DialogHeader>

            <UserForm v-model:form="addForm" :roles="roles" />

            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="addUserModal = false">Batal</Button>
                <Button :disabled="addForm.processing" @click="handleAddUser">
                    <LoaderCircle v-if="addForm.processing" class="h-4 w-4 animate-spin" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Edit User Modal -->
    <Dialog :open="editUserModal" @update:open="(val) => (editUserModal = val)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Pengguna</DialogTitle>
                <DialogDescription>Update data pengguna di form berikut.</DialogDescription>
            </DialogHeader>

            <UserForm v-model:form="editForm" :roles="roles" />

            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="editUserModal = false">Batal</Button>
                <Button :disabled="editForm.processing" @click="handleEditUser">
                    <LoaderCircle v-if="editForm.processing" class="h-4 w-4 animate-spin" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
