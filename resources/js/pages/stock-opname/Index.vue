<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Card, CardContent } from '@/components/ui/card'
import Heading from '@/components/Heading.vue'
import PageNav from '@/components/PageNav.vue'
import DeleteButton from '@/components/DeleteButton.vue'
import { Badge } from '@/components/ui/badge'
import { useFormat } from '@/composables/useFormat'
import type { BreadcrumbItem } from '@/types'

defineProps<{
    opnames: any
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stock Opname', href: '#' },
]

const { formatDate } = useFormat()

const approve = (id: number) => {
    router.patch(route('stock-opnames.approve', id))
}

const destroyOpname = (id: number) => {
    router.delete(route('stock-opnames.destroy', id))
}
</script>

<template>
    <Head title="Stock Opname" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading
                class="mx-4"
                title="Stock Opname"
                description="Audit fisik item emas dan penyesuaian status stok"
            />

            <div class="mx-auto max-w-8xl">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="mb-3 flex flex-col md:flex-row justify-between">
                            <Link :href="route('stock-opnames.create')">
                                <Button>Tambah Opname</Button>
                            </Link>
                        </div>

                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Tanggal</TableHead>
                                        <TableHead>Dibuat Oleh</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Item Sistem</TableHead>
                                        <TableHead>Item Scan</TableHead>
                                        <TableHead>Hilang</TableHead>
                                        <TableHead class="w-8" />
                                        <TableHead class="w-8" />
                                        <TableHead class="w-8" />
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow
                                        v-for="op in opnames.data"
                                        :key="op.id"
                                    >
                                        <TableCell class="font-medium">
                                            {{ op.code }}
                                        </TableCell>

                                        <TableCell>
                                            {{ formatDate(op.opname_at) }}
                                        </TableCell>

                                        <TableCell>
                                            {{ op.user?.name ?? '-' }}
                                        </TableCell>

                                        <TableCell>
                                            <Badge
                                                :variant="op.status === 'approved' ? 'success' : 'secondary'"
                                            >
                                                {{ op.status_label }}
                                            </Badge>
                                        </TableCell>

                                        <TableCell>
                                            {{ op.total_items_system }}
                                        </TableCell>

                                        <TableCell>
                                            {{ op.total_items_scanned }}
                                        </TableCell>

                                        <TableCell>
                                            <span
                                                :class="op.missing_items > 0 ? 'text-red-600 font-semibold' : ''"
                                            >
                                                {{ op.missing_items }}
                                            </span>
                                        </TableCell>

                                        <TableCell class="px-1">
                                            <Link
                                                :href="route('stock-opnames.edit', op.id)"
                                            >
                                                <Button size="sm" variant="secondary">
                                                    Detail
                                                </Button>
                                            </Link>
                                        </TableCell>

                                        <TableCell class="px-1">
                                            <Button
                                                size="sm"
                                                :disabled="op.status === 'approved'"
                                                @click="approve(op.id)"
                                            >
                                                Approve
                                            </Button>
                                        </TableCell>

                                        <TableCell class="px-1">
                                            <DeleteButton
                                                size="sm"
                                                :disabled="op.status === 'approved'"
                                                @click="destroyOpname(op.id)"
                                            />
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="!opnames.total">
                                        <TableCell colspan="10">
                                            Data tidak ditemukan
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>

                            <PageNav :data="opnames" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
