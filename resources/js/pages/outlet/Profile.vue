<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import OutletLayout from '@/layouts/outlet/Layout.vue';
import { LoaderCircle } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';
import HeadingSmall from '@/components/HeadingSmall.vue';

const { outlet } = defineProps(['outlet']);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pengaturan Profil Outlet',
        href: '#',
    },
];
const form = useForm({
    name: outlet?.name || '',
    address: outlet?.address || '' ,
    phone_number: outlet?.phone_number|| '' ,
    email: outlet?.email|| '' ,
});

const submit = () => {
    form.patch(route('outlet.settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Profil Outlet" />

        <OutletLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Informasi Profil Outlet" description="Perbarui nama, alamat, telepon, dan email outlet Anda" />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Nama Outlet</Label>
                        <Input id="name" class="mt-1 block w-full" v-model="form.name" required placeholder="Nama outlet" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="address">Alamat Outlet</Label>
                        <Input id="address" class="mt-1 block w-full" v-model="form.address" required placeholder="Alamat outlet" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone_number">Nomor Telepon</Label>
                        <Input id="phone_number" class="mt-1 block w-full" v-model="form.phone_number" required placeholder="Nomor telepon outlet" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Alamat Email</Label>
                        <Input id="email" type="email" class="mt-1 block w-full" v-model="form.email" required placeholder="Alamat email outlet" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                            Simpan
                        </Button>
                    </div>
                </form>
            </div>
        </OutletLayout>
    </AppLayout>
</template>
