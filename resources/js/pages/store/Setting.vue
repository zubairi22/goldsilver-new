<script setup lang="ts">
import CameraUploader from '@/components/CameraUploader.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import StoreLayout from '@/layouts/store/Layout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const { setting, category } = defineProps<{
    setting: any;
    category: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pengaturan Toko', href: '#' }];

const form = useForm({
    _method: 'patch',
    store_name: setting.store_name || '',
    phone: setting.phone || '',
    instagram: setting.instagram || '',
    address: setting.address ?? '',
    logo: undefined as File | undefined,
    invoice_color: setting.invoice_color || '#FFD700',
    header: setting.header || '',
    footer_wholesale: setting.footer_wholesale || '',
    footer_retail: setting.footer_retail || '',
});

const submit = () => {
    form.post(route('store.settings.update', { category }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Pengaturan Toko ${category}`" />

        <StoreLayout>
            <div class="flex max-w-3xl flex-col space-y-6">
                <HeadingSmall
                    :title="`Pengaturan Toko ${category === 'gold' ? 'Emas' : 'Perak'}`"
                    description="Perbarui informasi toko & konfigurasi nota"
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Store Name -->
                    <div class="grid gap-2">
                        <Label>Nama Toko</Label>
                        <Input v-model="form.store_name" required />
                        <InputError :message="form.errors.store_name" />
                    </div>

                    <!-- Phone -->
                    <div class="grid gap-2">
                        <Label>Nomor Telepon</Label>
                        <Input v-model="form.phone" />
                        <InputError :message="form.errors.phone" />
                    </div>

                    <!-- Instagram -->
                    <div class="grid gap-2">
                        <Label>Instagram</Label>
                        <Input v-model="form.instagram" />
                        <InputError :message="form.errors.instagram" />
                    </div>

                    <!-- Address -->
                    <div class="grid gap-2">
                        <Label>Alamat Toko</Label>
                        <Textarea v-model="form.address" rows="2" />
                        <InputError :message="form.errors.address" />
                    </div>

                    <!-- Logo -->
                    <div class="grid gap-2">
                        <Label>Logo Toko</Label>
                        <CameraUploader v-model="form.logo" :compress="false" />
                        <InputError :message="form.errors.logo" />

                        <div v-if="setting.logo" class="mt-2">
                            <Label>Logo Saat Ini</Label>
                            <img :src="setting.logo" alt="Logo Toko" class="mt-4 h-20 rounded object-contain" />
                        </div>
                    </div>

                    <!-- Invoice Color -->
                    <div class="grid gap-2">
                        <Label>Warna Nota</Label>
                        <Input type="color" v-model="form.invoice_color" />
                        <InputError :message="form.errors.invoice_color" />
                    </div>

                    <!-- Header -->
                    <div class="grid gap-2">
                        <Label>Header Nota</Label>
                        <Textarea v-model="form.header" rows="3" />
                        <InputError :message="form.errors.header" />
                    </div>

                    <!-- Footer Wholesale -->
                    <div class="grid gap-2">
                        <Label>Footer Grosir</Label>
                        <Textarea v-model="form.footer_wholesale" rows="5" />
                        <InputError :message="form.errors.footer_wholesale" />
                    </div>

                    <!-- Footer Retail -->
                    <div class="grid gap-2">
                        <Label>Footer Retail</Label>
                        <Textarea v-model="form.footer_retail" rows="5" />
                        <InputError :message="form.errors.footer_retail" />
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                            Simpan
                        </Button>
                    </div>
                </form>
            </div>
        </StoreLayout>
    </AppLayout>
</template>
