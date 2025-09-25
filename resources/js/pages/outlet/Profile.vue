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
import InputError from '@/components/InputError.vue';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ref } from 'vue';
import axios from 'axios';

const { outlet } = defineProps(['outlet']);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pengaturan Profil Outlet',
        href: '#',
    },
];
const form = useForm({
    name: outlet?.name || '',
    address: outlet?.address || '',
    phone_number: outlet?.phone_number || '',
    email: outlet?.email || '',
    instagram: outlet?.instagram || '',
    receipt_footer: outlet?.receipt_footer || '',
});

const previewModal = ref(false);
const previewContent = ref('');

const openPreview = async () => {
    try {
        const { data } = await axios.get(route('outlet.preview'));
        previewContent.value = data.receipt;
        previewModal.value = true;
    } catch (e) {
        previewContent.value = 'Gagal memuat preview';
        previewModal.value = true;
        console.error(e)
    }
};

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
            <div class="flex max-w-xl flex-col space-y-6">
                <div class="flex justify-between items-center">
                    <HeadingSmall
                        title="Informasi Profil Outlet"
                        description="Perbarui data dari outlet Anda"
                    />

                    <Button type="button" variant="outline" @click="openPreview">
                        Preview Struk
                    </Button>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Nama Outlet</Label>
                        <Input id="name" class="mt-1 block w-full" v-model="form.name" required placeholder="Nama outlet" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="address">Alamat Outlet</Label>
                        <Input id="address" class="mt-1 block w-full" v-model="form.address" placeholder="Alamat outlet" />
                        <InputError :message="form.errors.address" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone_number">Nomor Telepon</Label>
                        <Input id="phone_number" class="mt-1 block w-full" v-model="form.phone_number" placeholder="Nomor telepon outlet" />
                        <InputError :message="form.errors.phone_number" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Alamat Email</Label>
                        <Input id="email" type="email" class="mt-1 block w-full" v-model="form.email" placeholder="Alamat email outlet" />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="instagram">Instagram</Label>
                        <Input id="instagram" class="mt-1 block w-full" v-model="form.instagram" placeholder="@username" />
                        <InputError :message="form.errors.instagram" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="receipt_footer">Teks Footer Struk</Label>
                        <textarea
                            id="receipt_footer"
                            v-model="form.receipt_footer"
                            rows="4"
                            class="mt-1 block w-full rounded-md border p-2 text-sm"
                            placeholder="Masukkan teks footer struk (misal syarat retur, dll)"
                        />
                        <InputError :message="form.errors.receipt_footer" />
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

    <Dialog :open="previewModal" @update:open="(val) => (previewModal = val)">
        <DialogContent class="max-w-sm">
            <DialogHeader>
                <DialogTitle>Preview Struk</DialogTitle>
            </DialogHeader>

            <div class="mx-auto text-sm whitespace-pre bg-white p-4 rounded shadow"
                 style="max-width: 52ch;">
                <pre>{{ previewContent }}</pre>
            </div>

            <DialogFooter>
                <Button @click="previewModal = false">Tutup</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
