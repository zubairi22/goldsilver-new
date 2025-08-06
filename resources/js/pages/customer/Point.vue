<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import Heading from '@/components/Heading.vue';
import type { BreadcrumbItem } from '@/types';
import { useFormat } from '@/composables/useFormat';
import { Badge } from '@/components/ui/badge';
import PageNav from '@/components/PageNav.vue';

defineProps(['customer', 'point_logs']);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pelanggan', href: '/outlet/customers' },
    { title: 'Detail Poin', href: '/#' },
];

const { formatDate } = useFormat();

</script>

<template>
    <Head :title="'Detail Poin ' + customer.name " />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Detail Poin" description="Daftar Transaksi Poin" />

            <div class="mx-auto max-w-8xl">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-20 text-center">#</TableHead>
                                        <TableHead>Tanggal</TableHead>
                                        <TableHead>Jenis</TableHead>
                                        <TableHead class="text-center">Poin</TableHead>
                                        <TableHead>Deskripsi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="(log, index) in point_logs.data" :key="log.id">
                                        <TableCell class="text-center">{{ index + 1 }}</TableCell>
                                        <TableCell>{{ formatDate(log.created_at) }}</TableCell>
                                        <TableCell>
                                            <Badge :variant="log.type === 'earn' ? 'success' : 'destructive'">
                                                {{ log.type === 'earn' ? 'Dapat' : 'Tebus' }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-center font-bold">
                                            {{ log.points }}
                                        </TableCell>
                                        <TableCell>{{ log.description || '-' }}</TableCell>
                                    </TableRow>
                                    <TableRow v-if="!point_logs.total">
                                        <TableCell colspan="5" class="text-center text-gray-500">
                                            Belum ada aktivitas poin.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <PageNav :data="point_logs" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
