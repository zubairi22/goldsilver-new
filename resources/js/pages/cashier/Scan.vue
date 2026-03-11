<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useBarcodeScanner } from '@/composables/useBarcodeScanner';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Cetak Nota', href: '#' },
];

const scanForm = useForm({
    code: '',
});

useBarcodeScanner((barcode: string) => {
    scanForm.code = barcode;
    submitScan();
});

const submitScan = () => {
    if (!scanForm.code) return;
    scanForm.post(route('cashier.scan.submit'), {
        preserveScroll: true,
        onSuccess: (page: any) => {
            if (page.props.flash?.sale) {
                window.open(page.props.flash.sale, '_blank');
            }
            scanForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Cetak Nota" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Cetak Nota" description="Scan QR atau masukkan nomor nota untuk mencetak" />

            <div class="max-w-8xl mx-auto">
                <Card class="py-10 md:mx-4">
                    <CardContent class="space-y-8">
                        <div class="space-y-6 text-center">
                            <div class="space-y-2">
                                <h3 class="text-xl font-bold">Scan Nota Penjualan</h3>
                                <p class="text-muted-foreground">
                                    Silakan scan QR code yang tertera pada layar atau masukkan nomor nota secara manual di bawah ini.
                                </p>
                            </div>

                            <div class="mx-auto max-w-6xl space-y-4">
                                <div class="flex w-full flex-col gap-3 sm:flex-row">
                                    <Input
                                        v-model="scanForm.code"
                                        type="text"
                                        placeholder="Contoh: INV-2024..."
                                        class="w-full text-center text-lg"
                                        @keyup.enter="submitScan"
                                        autofocus
                                    />

                                    <Button :disabled="scanForm.processing" @click="submitScan">
                                        <Icon name="search" class="mr-2" />
                                        Cari
                                    </Button>
                                </div>

                                <InputError :message="scanForm.errors.code" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
