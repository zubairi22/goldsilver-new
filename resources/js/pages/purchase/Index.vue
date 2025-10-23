<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Card, CardContent } from '@/components/ui/card'
import Heading from '@/components/Heading.vue'
import SearchInput from '@/components/SearchInput.vue'
import PageNav from '@/components/PageNav.vue'
import DeleteButton from '@/components/DeleteButton.vue'
import { useSearch } from '@/composables/useSearch'
import { useFormat } from '@/composables/useFormat'
import type { BreadcrumbItem } from '@/types'
import { Badge } from '@/components/ui/badge';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Purchase Order', href: '#' },
]

defineProps<{ purchases:any }>()

const { search } = useSearch('outlet.purchases.index', route().params.search, ['purchases'])
const { formatRupiah } = useFormat()

const receive = (id:number) => {
    router.patch(route('outlet.purchases.receive', id))
}

const destroyPO = (id:number) => {
    router.delete(route('outlet.purchases.destroy', id))
}
</script>

<template>
    <Head title="Purchase Order" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Purchase Order" description="Kelola pembelian & penerimaan stok" />
            <div class="mx-auto max-w-8xl">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="flex flex-col justify-between md:flex-row mb-2">
                            <Link :href="route('outlet.purchases.create')">
                                <Button>Tambah PO</Button>
                            </Link>
                            <div class="mb-3 mt-3 md:mt-0 md:text-right">
                                <SearchInput v-model:search="search" />
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Supplier</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Total</TableHead>
                                        <TableHead>Proses</TableHead>
                                        <TableHead class="w-8"/>
                                        <TableHead class="w-8"/>
                                        <TableHead class="w-8"/>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="po in purchases.data" :key="po.id">
                                        <TableCell>{{ po.purchase_number }}</TableCell>
                                        <TableCell>{{ po.supplier?.name ?? '-' }}</TableCell>
                                        <TableCell class="capitalize">{{ po.status }}</TableCell>
                                        <TableCell>
                                            {{ formatRupiah(po.total_purchase) }}
                                        </TableCell>
                                        <TableCell>
                                            <Badge :variant="po.posted_at ? 'success' : 'destructive'">
                                                {{ po.posted_at ? 'Ya' : 'Belum' }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="px-1">
                                            <Link :href="route('outlet.purchases.edit', po.id)">
                                                <Button size="sm" variant="secondary">Edit</Button>
                                            </Link>
                                        </TableCell>
                                        <TableCell class="px-1">
                                            <Button size="sm" :disabled="!!po.posted_at" @click="receive(po.id)">Terima</Button>
                                        </TableCell>
                                        <TableCell class="px-1">
                                            <DeleteButton :disabled="!!po.posted_at" size="sm" @click="destroyPO(po.id)" />
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="!purchases.total">
                                        <TableCell colspan="8">PO tidak ditemukan</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <PageNav :data="purchases" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
