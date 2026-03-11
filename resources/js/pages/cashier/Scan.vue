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
        <div class="py-12">
            <Heading class="mx-4" title="Cetak Nota" description="Scan QR atau masukkan nomor nota untuk mencetak" />

            <div class="mx-auto max-w-4xl">
                <Card class="py-10 md:mx-4">
                    <CardContent class="space-y-8">
                        <div class="space-y-6 text-center">
                            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                <Icon name="qr-code" size="40" />
                            </div>

                            <div class="space-y-2">
                                <h3 class="text-xl font-bold">Scan Nota Penjualan</h3>
                                <p class="text-muted-foreground">
                                    Silakan scan QR code yang tertera pada layar atau masukkan nomor nota secara manual di bawah ini.
                                </p>
                            </div>

                            <div class="mx-auto max-w-md space-y-4">
                                <div class="flex w-full flex-col gap-3 sm:flex-row">
                                    <Input
                                        v-model="scanForm.code"
                                        type="text"
                                        placeholder="Contoh: INV-2024..."
                                        class="w-full text-center text-lg"
                                        @keyup.enter="submitScan"
                                        autofocus
                                    />

                                    <Button :disabled="scanForm.processing" @click="submitScan" class="h-11 bg-blue-600 px-8 hover:bg-blue-700">
                                        <Icon name="search" class="mr-2" />
                                        Cari
                                    </Button>
                                </div>

                                <InputError :message="scanForm.errors.code" />
                            </div>

                            <div class="pt-4">
                                <p class="text-sm text-muted-foreground italic">
                                    *Pastikan kursor fokus pada input di atas jika menggunakan scanner hardware.
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
