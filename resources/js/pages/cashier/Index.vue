<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import CurrencyInput from '@/components/CurrencyInput.vue';

const { session } = defineProps<{
    session: any | null;
}>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Kasir', href: '#' },
];

const openForm = useForm({
    initial_cash: 0,
});

const closeForm = useForm({
    closing_cash: 0,
});

const scanForm = useForm({
    code: '',
});

const submitOpen = () => {
    openForm.post(route('cashier.open'), { preserveScroll: true });
};

const submitClose = () => {
    closeForm.post(route('cashier.close'), { preserveScroll: true });
};

const submitScan = () => {
    scanForm.post(route('cashier.scan'), { preserveScroll: true });
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
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                            <div class="space-y-6">

                                <p class="text-lg font-semibold">Status Kasir</p>

                                <Badge
                                    :variant="session ? 'default' : 'secondary'"
                                    class="px-4 py-1 text-base"
                                >
                                    {{ session ? 'Sedang Dibuka' : 'Tertutup' }}
                                </Badge>

                                <!-- Detail session -->
                                <div
                                    v-if="session"
                                    class="grid grid-cols-2 gap-4 bg-gray-50 border p-4 rounded-lg text-sm"
                                >
                                    <div>
                                        <p class="text-gray-600">Modal Awal</p>
                                        <p class="font-semibold">{{ session.initial_cash }}</p>
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
                                <div v-if="session" class="space-y-2 mt-2">
                                    <Label for="closing_cash">Kas Akhir</Label>

                                    <CurrencyInput
                                        id="closing_cash"
                                        v-model="closeForm.closing_cash"
                                        class="w-full lg:w-80"
                                    />

                                    <InputError :message="closeForm.errors.closing_cash" />
                                </div>

                                <!-- Tombol Tutup Kasir -->
                                <Button
                                    v-if="session"
                                    :disabled="closeForm.processing"
                                    @click="submitClose"
                                    class="bg-red-600 hover:bg-red-700 mt-3"
                                >
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
                                    <Label for="initial_cash">Modal Awal</Label>

                                    <CurrencyInput
                                        id="initial_cash"
                                        v-model="openForm.initial_cash"
                                        class="w-full lg:w-80"
                                    />

                                    <InputError :message="openForm.errors.initial_cash" />
                                </div>

                                <Button
                                    :disabled="openForm.processing"
                                    @click="submitOpen"
                                    class="bg-green-600 hover:bg-green-700"
                                >
                                    <Icon name="check" class="mr-2" />
                                    Buka Kasir
                                </Button>
                            </div>

                            <!-- PANEL RINGKASAN (TAMPIL SAAT SESSION AKTIF) -->
                            <div v-else class="space-y-6">

                                <p class="text-lg font-semibold">Ringkasan Hari Ini</p>

                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-2 text-sm">
                                    <p class="text-gray-600">Status operasional kasir:</p>
                                    <p class="font-semibold text-blue-800">Kasir aktif & siap digunakan</p>
                                </div>

                                <p class="text-xs text-gray-500">
                                    *Panel ini bisa diisi total transaksi, total penjualan, pembelian, dll.
                                </p>
                            </div>

                        </div>

                        <hr />

                        <!-- ======================================= -->
                        <!-- SCAN QR / KODE -->
                        <!-- ======================================= -->
                        <div class="space-y-4">
                            <p class="text-lg font-semibold">Scan / Masukkan Kode Transaksi</p>

                            <div class="flex flex-col sm:flex-row gap-3 w-full">

                                <Input
                                    v-model="scanForm.code"
                                    type="text"
                                    placeholder="Scan QR atau masukkan kode transaksi"
                                    class="w-full"
                                    @keyup.enter="submitScan"
                                />

                                <Button
                                    :disabled="scanForm.processing"
                                    @click="submitScan"
                                    class="bg-blue-600 hover:bg-blue-700 w-full sm:w-auto"
                                >
                                    <Icon name="search" class="mr-2" />
                                    Cari
                                </Button>
                            </div>

                            <InputError :message="scanForm.errors.code" />

                            <p class="text-xs text-gray-500">
                                Bisa berupa <strong>invoice_no</strong>, <strong>qr_token</strong>, atau <strong>nomor buyback</strong>.
                            </p>
                        </div>
                    </CardContent>
                </Card>

            </div>
        </div>
    </AppLayout>
</template>
