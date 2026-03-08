<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFormat } from '@/composables/useFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    opname: any;
    previousOpname: any;
    missingItems: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stock Opname', href: route('store.stock-opnames.index') },
    { title: 'Perbandingan', href: '#' },
];

const { formatDate } = useFormat();
</script>

<template>
    <Head title="Perbandingan Stock Opname" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading
                class="mx-4"
                title="Perbandingan Stock Opname"
                description="Membandingkan item yang ditemukan pada opname sebelumnya dengan opname saat ini"
            />

            <div class="max-w-8xl mx-auto space-y-6">
                <!-- Info Opname -->
                <div class="grid grid-cols-1 gap-4 px-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm">Opname Sebelumnya</CardTitle>
                        </CardHeader>
                        <CardContent v-if="previousOpname">
                            <div class="text-lg font-bold">{{ previousOpname.code }}</div>
                            <div class="text-sm text-gray-500">{{ formatDate(previousOpname.opname_at, 'dd MMMM yyyy') }}</div>
                        </CardContent>
                        <CardContent v-else>
                            <div class="text-sm text-gray-500 italic">Tidak ada opname sebelumnya yang disetujui</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm">Opname Saat Ini</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-lg font-bold">{{ opname.code }}</div>
                            <div class="text-sm text-gray-500">{{ formatDate(opname.opname_at, 'dd MMMM yyyy') }}</div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Missing Items Table -->
                <Card class="py-4 md:mx-4">
                    <CardHeader class="border-b px-6 py-4">
                        <CardTitle>Item yang Hilang / Tidak Ditemukan</CardTitle>
                        <p class="text-sm text-muted-foreground">
                            Item ini ada pada opname sebelumnya tetapi tidak ditemukan pada opname saat ini (dan belum terjual).
                        </p>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Tipe</TableHead>
                                        <TableHead>Berat</TableHead>
                                        <TableHead>Status Saat Ini</TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow v-for="item in missingItems" :key="item.id">
                                        <TableCell class="font-medium text-red-600">{{ item.code }}</TableCell>
                                        <TableCell>{{ item.name }}</TableCell>
                                        <TableCell>{{ item.type?.name ?? '-' }}</TableCell>
                                        <TableCell>{{ item.weight }} gr</TableCell>
                                        <TableCell>
                                            <Badge variant="outline">{{ item.status_label ?? item.status }}</Badge>
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="!missingItems.length">
                                        <TableCell colspan="5" class="py-8 text-center text-gray-500">
                                            Tidak ada item yang hilang dibandingkan opname sebelumnya.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <Link :href="route('store.stock-opnames.index')">
                                <Button variant="outline">Kembali ke Daftar</Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
