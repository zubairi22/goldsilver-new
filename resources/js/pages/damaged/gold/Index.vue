<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Card, CardContent } from '@/components/ui/card'
import Heading from '@/components/Heading.vue'
import SearchInput from '@/components/SearchInput.vue'
import PageNav from '@/components/PageNav.vue'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useFormat } from '@/composables/useFormat'

const { items, filters } = defineProps(['items', 'filters'])
const { formatRupiah } = useFormat()

const search = ref(filters.search || '')

function doSearch() {
    router.get(route('gold.damaged.index'), { search: search.value }, {
        preserveScroll: true,
        preserveState: true,
    })
}

// Modal State
const restoreModal = ref(false)
const restoreItem = ref<any>(null)

function openRestore(item: any) {
    restoreItem.value = {
        ...item,
        newName: item.name,
        newWeight: item.weight,
        newSellPrice: item.price_sell,
    }
    restoreModal.value = true
}

function submitRestore() {
    router.patch(
        route('gold.damaged.restore', restoreItem.value.id),
        {
            name: restoreItem.value.newName,
            weight: restoreItem.value.newWeight,
            price_sell: restoreItem.value.newSellPrice,
        },
        {
            onSuccess: () => restoreModal.value = false
        }
    )
}
</script>

<template>
    <Head title="Produk Rusak - Emas" />

    <AppLayout>
        <div class="py-8">
            <Heading class="mx-4" title="Produk Rusak" description="Daftar item emas hasil buyback yang rusak" />

            <div class="max-w-8xl mx-auto">
                <Card class="py-4 md:mx-4">
                    <CardContent>

                        <!-- SEARCH -->
                        <div class="flex justify-end mb-4">
                            <div class="w-full lg:w-80">
                                <SearchInput v-model:search="search" @keyup.enter="doSearch" />
                            </div>
                        </div>

                        <!-- TABLE -->
                        <div class="overflow-x-auto">
                            <Table class="w-full">

                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Nama Produk</TableHead>
                                        <TableHead class="text-right">Berat (gr)</TableHead>
                                        <TableHead class="text-right">Harga Jual</TableHead>
                                        <TableHead class="text-center">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow v-for="it in items.data" :key="it.id">
                                        <TableCell>{{ it.code }}</TableCell>
                                        <TableCell>{{ it.name }}</TableCell>
                                        <TableCell class="text-right">{{ it.weight }}</TableCell>
                                        <TableCell class="text-right">{{ formatRupiah(it.price_sell) }}</TableCell>

                                        <TableCell class="text-center">
                                            <Button @click="openRestore(it)">
                                                Pulihkan Stok
                                            </Button>
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="!items.total">
                                        <TableCell colspan="5" class="text-center py-4">
                                            Tidak ada data
                                        </TableCell>
                                    </TableRow>
                                </TableBody>

                            </Table>

                            <PageNav :data="items" />
                        </div>

                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <!-- RESTORE MODAL -->
    <Dialog :open="restoreModal" @update:open="(v) => restoreModal = v">
        <DialogContent class="max-w-md">

            <DialogHeader>
                <DialogTitle>Pulihkan Produk ke Stok</DialogTitle>
            </DialogHeader>

            <div v-if="restoreItem">

                <Label>Nama Produk</Label>
                <Input v-model="restoreItem.newName" class="mb-3" />

                <Label>Berat</Label>
                <Input type="number" step="0.01" v-model="restoreItem.newWeight" class="mb-3" />

                <Label>Harga Jual</Label>
                <Input type="number" step="100" v-model="restoreItem.newSellPrice" class="mb-3" />

            </div>

            <DialogFooter>
                <Button variant="secondary" @click="restoreModal = false">Batal</Button>
                <Button @click="submitRestore">Simpan</Button>
            </DialogFooter>

        </DialogContent>
    </Dialog>

</template>
