<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import StoreLayout from '@/layouts/store/Layout.vue';
import { LoaderCircle } from 'lucide-vue-next';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem } from '@/types';
import { Textarea } from '@/components/ui/textarea';


const { settings } = defineProps(['settings']);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pengaturan Toko', href: '#' },
];

const form = useForm({
    store_name: settings.store_name || '',
    phone: settings.phone || '',
    instagram: settings.instagram || '',
    address: settings.address ?? '',
    gold_invoice_color: settings.gold_invoice_color || '#FFD700',
    silver_invoice_color: settings.silver_invoice_color || '#C0C0C0',
    footer_gold_wholesale: settings.footer_gold_wholesale || '',
    footer_gold_retail: settings.footer_gold_retail || '',
    footer_silver_wholesale: settings.footer_silver_wholesale || '',
    footer_silver_retail: settings.footer_silver_retail || '',
});

const submit = () => {
    form.patch(route('store.settings.update'), { preserveScroll: true });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Pengaturan Toko" />

        <StoreLayout>
            <div class="flex max-w-3xl flex-col space-y-6">

                <HeadingSmall
                    title="Informasi Toko"
                    description="Perbarui informasi toko & konfigurasi nota"
                />

                <form @submit.prevent="submit" class="space-y-6">

                    <!-- Store Name -->
                    <div class="grid gap-2">
                        <Label for="store_name">Nama Toko</Label>
                        <Input id="store_name" v-model="form.store_name" required />
                        <InputError :message="form.errors.store_name" />
                    </div>

                    <!-- Phone -->
                    <div class="grid gap-2">
                        <Label for="phone">Nomor Telepon</Label>
                        <Input id="phone" v-model="form.phone" />
                        <InputError :message="form.errors.phone" />
                    </div>

                    <!-- Instagram -->
                    <div class="grid gap-2">
                        <Label for="instagram">Instagram</Label>
                        <Input id="instagram" v-model="form.instagram" />
                        <InputError :message="form.errors.instagram" />
                    </div>

                    <!-- Address -->
                    <div class="grid gap-2">
                        <Label for="address">Alamat Toko</Label>
                        <textarea
                            id="address"
                            v-model="form.address"
                            rows="2"
                            class="mt-1 block w-full rounded-md border p-2 text-sm"
                        />
                        <InputError :message="form.errors.address" />
                    </div>

                    <!-- Colors -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label>Warna Nota Emas</Label>
                            <Input type="color" v-model="form.gold_invoice_color" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Warna Nota Perak</Label>
                            <Input type="color" v-model="form.silver_invoice_color" />
                        </div>
                    </div>

                    <!-- Footer Gold Wholesale -->
                    <div class="grid gap-2">
                        <Label for="footer_gold_wholesale">Footer Emas (Grosir)</Label>
                        <Textarea
                            id="footer_gold_wholesale"
                            v-model="form.footer_gold_wholesale"
                            rows="5"
                            class="mt-1 block w-full rounded-md border p-2 text-sm"
                            placeholder="Teks footer untuk nota emas grosir"
                        />
                        <InputError :message="form.errors.footer_gold_wholesale" />
                    </div>

                    <!-- Footer Gold Retail -->
                    <div class="grid gap-2">
                        <Label for="footer_gold_retail">Footer Emas (Retail)</Label>
                        <Textarea
                            id="footer_gold_retail"
                            v-model="form.footer_gold_retail"
                            rows="5"
                            class="mt-1 block w-full rounded-md border p-2 text-sm"
                        />
                        <InputError :message="form.errors.footer_gold_retail" />
                    </div>

                    <!-- Footer Silver Wholesale -->
                    <div class="grid gap-2">
                        <Label for="footer_silver_wholesale">Footer Perak (Grosir)</Label>
                        <Textarea
                            id="footer_silver_wholesale"
                            v-model="form.footer_silver_wholesale"
                            rows="5"
                            class="mt-1 block w-full rounded-md border p-2 text-sm"
                        />
                        <InputError :message="form.errors.footer_silver_wholesale" />
                    </div>

                    <!-- Footer Silver Retail -->
                    <div class="grid gap-2">
                        <Label for="footer_silver_retail">Footer Perak (Retail)</Label>
                        <Textarea
                            id="footer_silver_retail"
                            v-model="form.footer_silver_retail"
                            rows="5"
                            class="mt-1 block w-full rounded-md border p-2 text-sm"
                        />
                        <InputError :message="form.errors.footer_silver_retail" />
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
