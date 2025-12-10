<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import {Head, router, useForm} from '@inertiajs/vue3';
import {ref} from "vue";
import DeleteButton from "@/components/DeleteButton.vue";
import EditButton from "@/components/EditButton.vue";
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table/index.js';
import { Badge } from '@/components/ui/badge/index.js';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import PageNav from '@/components/PageNav.vue';
import MenuForm from '@/pages/master/menu/MenuForm.vue';
import type { BreadcrumbItem } from '@/types/index.js';
import Heading from '@/components/Heading.vue';
import { LoaderCircle } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Menu',
        href: '#',
    },
];

const {menus, parents} = defineProps(['menus', 'parents']);

const defaultForm = () => ({
    title: '', url: '', parent_id: '', sort: '', icon: '', permissions: []
});

const addForm = useForm(defaultForm());
const editForm = useForm({id: '', ...defaultForm()});

const addMenuModal = ref(false);
const editMenuModal = ref(false);

const editMenu = (menu: any) => {
    Object.assign(editForm, menu);
    editForm.permissions = menu.permissions.map((permission: any) => permission.name);
    editMenuModal.value = true;
};

const handleAddMenu = () => {
    addForm.post(route('master.menus.store'), {
        preserveScroll: true,
        onSuccess: () => {
            addMenuModal.value = false
            addForm.reset()
        },
    });
};

const handleEditMenu = () => {
    editForm.patch(route('master.menus.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            editMenuModal.value = false
            editForm.reset()
        },
    });
};

const handleDeleteMenu = (menuId: number) => {
    router.delete(route('master.menus.destroy', menuId), {
        preserveScroll: true,
    });
};

</script>

<template>
    <Head title="Menu"/>

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Menu" description="Kelola menu di aplikasi Anda" />
            <div class="mx-auto max-w-8xl">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="flex flex-col justify-between md:flex-row mb-2">
                            <div class="mb-3">
                                <Button @click="addMenuModal = true">Tambah Menu</Button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Title</TableHead>
                                        <TableHead>Route Url</TableHead>
                                        <TableHead>Permissions</TableHead>
                                        <TableHead class="w-8"/>
                                        <TableHead class="w-8"/>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="menu in menus.data" :key="menu.id">
                                        <TableCell class="px-4 py-4">{{ menu.title }}</TableCell>
                                        <TableCell class="px-4 py-4">{{ menu.url }}</TableCell>
                                        <TableCell class="flex flex-wrap px-4 py-4">
                                            <Badge class="m-1" v-for="(permission) in menu.permissions" :key="permission.id">
                                                {{ permission.name }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="px-1">
                                            <EditButton @click="editMenu(menu)"/>
                                        </TableCell>
                                        <TableCell class="px-1">
                                            <DeleteButton @click="handleDeleteMenu(menu.id)"/>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="!menus.total">
                                        <TableCell colspan="2">Menu tidak ditemukan</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <PageNav :data="menus"/>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="addMenuModal" @update:open="(val) => addMenuModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    Tambah Menu
                </DialogTitle>
                <DialogDescription>
                    Masukkan data menu baru ke dalam form berikut.
                </DialogDescription>
            </DialogHeader>
            <MenuForm v-model:form="addForm" :parents="parents"/>
            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="addMenuModal = false">
                    Batal
                </Button>
                <Button
                    :class="{ 'opacity-25': addForm.processing }"
                    :disabled="addForm.processing"
                    @click="handleAddMenu"
                >
                    <LoaderCircle v-if="addForm.processing" class="h-4 w-4 animate-spin" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="editMenuModal" @update:open="(val) => editMenuModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    Edit Menu
                </DialogTitle>
                <DialogDescription>
                    Update data menu ke dalam form berikut.
                </DialogDescription>
            </DialogHeader>
            <MenuForm v-model:form="editForm" :parents="parents"/>
            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="editMenuModal = false">
                    Batal
                </Button>
                <Button
                    :class="{ 'opacity-25': editForm.processing }"
                    :disabled="editForm.processing"
                    @click="handleEditMenu"
                >
                    <LoaderCircle v-if="editForm.processing" class="h-4 w-4 animate-spin" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
