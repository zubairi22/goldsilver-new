<script setup lang="ts">
import CurrencyInput from '@/components/CurrencyInput.vue';
import Heading from '@/components/Heading.vue';
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useBarcodeScanner } from '@/composables/useBarcodeScanner';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const { session } = defineProps<{
    session: any | null;
}>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Kasir', href: '#' },
];

const openForm = useForm({
    gold_initial_cash: 0,
    silver_initial_cash: 0,
});

const closeForm = useForm({
    gold_closing_cash: 0,
    silver_closing_cash: 0,
});

const scanForm = useForm({
    code: '',
});

useBarcodeScanner((barcode: string) => {
    scanForm.code = barcode;
    submitScan();
});

const submitOpen = () => {
    openForm.post(route('cashier.open'), { preserveScroll: true });
};

const submitClose = () => {
    closeForm.post(route('cashier.close'), { preserveScroll: true });
};

const submitScan = () => {
    if (!scanForm.code) return;
    scanForm.post(route('cashier.scan'), {
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
    <Head title="Kasir" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Kasir" description="Kelola sesi kasir dan scan transaksi" />

            <div class="max-w-8xl mx-auto">
                <!-- CARD UTAMA -->
                <Card class="py-6 md:mx-4">
                    <CardContent class="space-y-8">
                        <div v-if="$page.props.auth.isAdmin" class="grid grid-cols-1 gap-10 lg:grid-cols-2">
                            <div class="space-y-6">
                                <p class="text-lg font-semibold">Status Kasir</p>

                                <Badge :variant="session ? 'default' : 'secondary'" class="px-4 py-1 text-base">
                                    {{ session ? 'Sedang Dibuka' : 'Tertutup' }}
                                </Badge>

                                <!-- Detail session -->
                                <div v-if="session" class="grid grid-cols-2 gap-4 rounded-lg border bg-gray-50 p-4 text-sm">
                                    <div>
                                        <p class="text-gray-600">Modal Awal Emas</p>
                                        <p class="font-semibold">
                                            {{ session.gold_initial_cash }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-600">Modal Awal Perak</p>
                                        <p class="font-semibold">
                                            {{ session.silver_initial_cash }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-600">Dibuka Oleh</p>
                                        <p class="font-semibold">{{ session.opened_by?.name }}</p>
                                    </div>

                                    <div>
                                        <p class="text-gray-600">Dibuka Pada</p>
                                        <p class="font-semibold">{{ session.opened_at }}</p>
                                    </div>
                                </div>

                                <!-- Input Closing Cash -->
                                <div v-if="session" class="mt-2 space-y-2">
                                    <div class="space-y-2">
                                        <Label for="gold_closing_cash">Kas Akhir Emas</Label>

                                        <CurrencyInput id="gold_closing_cash" v-model="closeForm.gold_closing_cash" class="w-full lg:w-80" />

                                        <InputError :message="closeForm.errors.gold_closing_cash" />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="silver_closing_cash">Kas Akhir Perak</Label>

                                        <CurrencyInput id="silver_closing_cash" v-model="closeForm.silver_closing_cash" class="w-full lg:w-80" />

                                        <InputError :message="closeForm.errors.silver_closing_cash" />
                                    </div>
                                </div>

                                <!-- Tombol Tutup Kasir -->
                                <Button v-if="session" :disabled="closeForm.processing" @click="submitClose" class="mt-3 bg-red-600 hover:bg-red-700">
                                    <Icon name="door" class="mr-2" />
                                    Tutup Kasir
                                </Button>

                                <!-- Jika kasir belum dibuka, tombol buka ditaruh di sebelah kanan -->
                            </div>

                            <!-- ======================================= -->
                            <!-- KOLOM KANAN -->
                            <!-- ======================================= -->

                            <!-- FORM BUKA KASIR (HANYA JIKA BELUM ADA SESSION) -->
                            <div v-if="!session" class="space-y-6">
                                <p class="text-lg font-semibold">Buka Kasir</p>

                                <div class="space-y-2">
                                    <Label for="gold_initial_cash">Modal Awal Emas</Label>

                                    <CurrencyInput id="gold_initial_cash" v-model="openForm.gold_initial_cash" class="w-full lg:w-80" />

                                    <InputError :message="openForm.errors.gold_initial_cash" />
                                </div>

                                <div class="space-y-2">
                                    <Label for="silver_initial_cash">Modal Awal Perak</Label>

                                    <CurrencyInput id="silver_initial_cash" v-model="openForm.silver_initial_cash" class="w-full lg:w-80" />

                                    <InputError :message="openForm.errors.silver_initial_cash" />
                                </div>

                                <Button :disabled="openForm.processing" @click="submitOpen" class="bg-green-600 hover:bg-green-700">
                                    <Icon name="check" class="mr-2" />
                                    Buka Kasir
                                </Button>
                            </div>

                            <!-- PANEL RINGKASAN (TAMPIL SAAT SESSION AKTIF) -->
                            <div v-else class="space-y-6">
                                <p class="text-lg font-semibold">Ringkasan Hari Ini</p>

                                <div class="space-y-2 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm">
                                    <p class="text-gray-600">Status operasional kasir:</p>
                                    <p class="font-semibold text-blue-800">Kasir aktif & siap digunakan</p>
                                </div>

                                <p class="text-xs text-gray-500">*Panel ini bisa diisi total transaksi, total penjualan, pembelian, dll.</p>
                            </div>
                        </div>

                        <hr v-if="$page.props.auth.isAdmin" />

                        <!-- ======================================= -->
                        <!-- SCAN QR / KODE -->
                        <!-- ======================================= -->
                        <div class="space-y-4">
                            <p class="text-lg font-semibold">Scan / Masukkan Nomor Nota</p>

                            <div class="flex w-full flex-col gap-3 sm:flex-row">
                                <Input
                                    v-model="scanForm.code"
                                    type="text"
                                    placeholder="Scan QR atau masukkan nomor nota"
                                    class="w-full"
                                    @keyup.enter="submitScan"
                                />

                                <Button :disabled="scanForm.processing" @click="submitScan" class="w-full bg-blue-600 hover:bg-blue-700 sm:w-auto">
                                    <Icon name="search" class="mr-2" />
                                    Cari
                                </Button>
                            </div>

                            <InputError :message="scanForm.errors.code" />

                            <p class="text-xs text-gray-500">Berupa <strong>Nomor Nota</strong>.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
