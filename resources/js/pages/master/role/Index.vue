<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DeleteButton from '@/components/DeleteButton.vue';
import EditButton from '@/components/EditButton.vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table/index.js';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import PageNav from '@/components/PageNav.vue';
import RoleForm from '@/pages/master/role/RoleForm.vue';
import type { BreadcrumbItem } from '@/types/index.js';
import { Badge } from '@/components/ui/badge';
import Heading from '@/components/Heading.vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard', },
    { title: 'Peran', href: '#', },
];

defineProps(['roles', 'permissions']);

const defaultForm = () => ({
    name: "",
    guard_name: "web",
    permissions: [],
});

const addForm = useForm(defaultForm());
const editForm = useForm({id: "", ...defaultForm() });

const addRoleModal = ref(false);
const editRoleModal = ref(false);

const editRole = (role: any) => {
    Object.assign(editForm, role);
    editForm.permissions = role.permissions.map((permission: any) => permission.name);
    editRoleModal.value = true;
};

const handleAddRole = () => {
    addForm.post(route('master.roles.store'), {
        preserveScroll: true,
        onSuccess: () => {
            addRoleModal.value = false;
            addForm.reset();
        },
    });
};

const handleEditRole = () => {
    editForm.patch(route('master.roles.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            editRoleModal.value = false;
            editForm.reset();
        },
    });
};

const handleDeleteRole = (roleId: number) => {
    router.delete(route('master.roles.destroy', roleId), {
        preserveScroll: true,
    });
};

</script>


<template>
    <Head title="Peran" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Peran" description="Kelola peran di aplikasi Anda" />
            <div class="mx-auto max-w-8xl">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="flex flex-col justify-between md:flex-row mb-2">
                            <div class="mb-3">
                                <Button @click="addRoleModal = true">Tambah Peran</Button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama Peran</TableHead>
                                        <TableHead>Guard Name</TableHead>
                                        <TableHead>Izin</TableHead>
                                        <TableHead class="w-8" />
                                        <TableHead class="w-8" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="role in roles.data" :key="role.id">
                                        <TableCell>{{ role.name }}</TableCell>
                                        <TableCell>{{ role.guard_name }}</TableCell>
                                        <TableCell class="flex flex-wrap px-4 py-4">
                                            <Badge class="m-1" v-for="(permission) in role.permissions" :key="permission.id">
                                                {{ permission.name }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="px-1">
                                            <EditButton @click="editRole(role)" />
                                        </TableCell>
                                        <TableCell class="px-1">
                                            <DeleteButton v-show="role.name !== 'super-admin'" @confirm="handleDeleteRole(role.id)" />
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="!roles.total">
                                        <TableCell colspan="3">Peran tidak ditemukan</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <PageNav :data="roles" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="addRoleModal" @update:open="(val) => addRoleModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tambah Peran</DialogTitle>
                <DialogDescription>Masukkan data peran baru ke dalam form berikut.</DialogDescription>
            </DialogHeader>
            <RoleForm v-model:form="addForm" :permissions="permissions"/>
            <DialogFooter>
                <Button variant="secondary" @click="addRoleModal = false">Batal</Button>
                <Button :disabled="addForm.processing" @click="handleAddRole">
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="editRoleModal" @update:open="(val) => editRoleModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Peran</DialogTitle>
                <DialogDescription>Update data peran ke dalam form berikut.</DialogDescription>
            </DialogHeader>
            <RoleForm v-model:form="editForm" :permissions="permissions" />
            <DialogFooter>
                <Button variant="secondary" @click="editRoleModal = false">Batal</Button>
                <Button :disabled="editForm.processing" @click="handleEditRole">
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
