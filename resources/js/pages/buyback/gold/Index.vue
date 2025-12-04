<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Card, CardContent } from '@/components/ui/card'
import Heading from '@/components/Heading.vue'
import PageNav from '@/components/PageNav.vue'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import VueDatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import { ref, watch } from 'vue'
import { useSearch } from '@/composables/useSearch'
import { useFormat } from '@/composables/useFormat'
import type { BreadcrumbItem } from '@/types'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'

const { buybacks, filters } = defineProps(['buybacks', 'filters'])

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Buyback Emas', href: '#' },
]

const { search } = useSearch('gold.buyback.index', filters.search, ['buybacks'])
const { formatRupiah, formatDate } = useFormat()

const status = ref(filters.status)
const payment_type = ref(filters.payment_type)
const date = ref(filters.start && filters.end ? [filters.start, filters.end] : [])

// QC State
const qcModal = ref(false)
const qcItem = ref<any>(null)

function openQC(item: any) {
    qcItem.value = {
        ...item,
        newCondition: item.condition ?? null,
        newName: item.manual_name ?? item.item?.name,
        newWeight: item.weight,
        newSellPrice: item.item?.price_sell ?? 0,
    }

    qcModal.value = true
}

function submitQC() {
    router.patch(
        route('buyback.gold.item.qc', qcItem.value.id),
        {
            name: qcItem.value.newName,
            weight: qcItem.value.newWeight,
            price_sell: qcItem.value.newSellPrice,
            condition: qcItem.value.newCondition
        },
        {
            onSuccess: () => qcModal.value = false
        }
    )
}

const applyFilters = () => {
    const params: Record<string, any> = { category: 'gold' }

    if (search.value) params.search = search.value
    if (status.value !== 'all') params.status = status.value
    if (payment_type.value !== 'all') params.payment_type = payment_type.value
    if (date.value?.[0] && date.value?.[1]) {
        params.start = date.value[0]
        params.end = date.value[1]
    }

    router.get(route('gold.buyback.index'), params, {
        preserveState: true,
        preserveScroll: true,
    })
}

watch([status, payment_type, date], applyFilters)
</script>

<template>
    <Head title="Buyback Emas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Buyback Emas" description="Daftar transaksi buyback emas" />

            <div class="max-w-8xl mx-auto">
                <Card class="py-4 md:mx-4">
                    <CardContent>

                        <!-- FILTERS -->
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                            <div class="flex flex-wrap items-center gap-4">

                                <div class="w-40">
                                    <Select v-model="status">
                                        <SelectTrigger><SelectValue placeholder="Status" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua</SelectItem>
                                            <SelectItem value="pending">Pending</SelectItem>
                                            <SelectItem value="approved">Disetujui</SelectItem>
                                            <SelectItem value="rejected">Ditolak</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="w-40">
                                    <Select v-model="payment_type">
                                        <SelectTrigger><SelectValue placeholder="Pembayaran" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua</SelectItem>
                                            <SelectItem value="cash">Cash</SelectItem>
                                            <SelectItem value="non_cash">Non Cash</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                            </div>

                            <div class="w-full sm:w-auto lg:w-80">
                                <VueDatePicker v-model="date" range model-type="yyyy-MM-dd" />
                            </div>
                        </div>

                        <!-- TABLE -->
                        <div class="overflow-x-auto">
                            <Table class="w-full">

                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-40">Tanggal</TableHead>
                                        <TableHead>No Buyback</TableHead>
                                        <TableHead>Pelanggan</TableHead>
                                        <TableHead>Kasir</TableHead>
                                        <TableHead class="text-right">Total Berat</TableHead>
                                        <TableHead class="text-right">Total Harga</TableHead>
                                        <TableHead class="text-center">Pembayaran</TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>

                                    <template v-for="bb in buybacks.data" :key="bb.id">

                                        <!-- HEADER -->
                                        <TableRow class="bg-white">
                                            <TableCell>{{ formatDate(bb.created_at) }}</TableCell>
                                            <TableCell>{{ bb.buyback_no }}</TableCell>
                                            <TableCell>{{ bb.customer?.name || '-' }}</TableCell>
                                            <TableCell>{{ bb.user?.name || '-' }}</TableCell>
                                            <TableCell class="text-right">{{ bb.total_weight }}</TableCell>
                                            <TableCell class="text-right">{{ formatRupiah(bb.total_price) }}</TableCell>
                                            <TableCell class="text-center">{{ bb.payment_type }}</TableCell>
                                        </TableRow>

                                        <!-- ITEM -->
                                        <TableRow class="bg-gray-50">
                                            <TableCell colspan="7">
                                                <div class="p-4 border rounded bg-white shadow-sm">

                                                    <Table class="w-full">
                                                        <TableHeader class="border-b-2">
                                                            <TableRow>
                                                                <TableHead>Nama</TableHead>
                                                                <TableHead class="text-right">Berat</TableHead>
                                                                <TableHead class="text-right">Harga</TableHead>
                                                                <TableHead class="text-right">Subtotal</TableHead>
                                                                <TableHead class="text-center">QC</TableHead>
                                                            </TableRow>
                                                        </TableHeader>

                                                        <TableBody>
                                                            <TableRow v-for="it in bb.items" :key="it.id">

                                                                <TableCell>{{ it.manual_name ?? it.item?.name }}</TableCell>
                                                                <TableCell class="text-right">{{ it.weight }}</TableCell>
                                                                <TableCell class="text-right">{{ formatRupiah(it.price) }}</TableCell>
                                                                <TableCell class="text-right">{{ formatRupiah(it.subtotal) }}</TableCell>

                                                                <TableCell class="text-center">
                                                                    <span v-if="it.condition" class="px-2 py-1 rounded bg-gray-100">
                                                                        {{ it.condition_label }}
                                                                    </span>

                                                                    <Button v-else @click="openQC(it)">Proses QC</Button>
                                                                </TableCell>

                                                            </TableRow>
                                                        </TableBody>
                                                    </Table>

                                                </div>
                                            </TableCell>
                                        </TableRow>

                                    </template>

                                </TableBody>

                            </Table>
                        </div>

                        <PageNav :data="buybacks" />

                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <!-- QC MODAL -->
    <Dialog :open="qcModal" @update:open="(v) => qcModal = v">
        <DialogContent class="max-w-md">

            <DialogHeader>
                <DialogTitle>Proses QC Item</DialogTitle>
            </DialogHeader>

            <div v-if="qcItem">

                <Label class="font-medium">Nama Item</Label>
                <Input v-model="qcItem.newName" class="w-full border rounded px-3 py-2 mb-3" />

                <Label class="font-medium">Berat Akhir</Label>
                <Input
                    v-model.number="qcItem.newWeight"
                    type="number"
                    step="0.01"
                    class="w-full border rounded px-3 py-2 mb-3"
                    :disabled="qcItem.newCondition === 'broken'"
                />

                <Label class="font-medium">Harga Jual</Label>
                <Input
                    v-model.number="qcItem.newSellPrice"
                    type="number"
                    step="100"
                    class="w-full border rounded px-3 py-2 mb-3"
                    :disabled="qcItem.newCondition === 'broken'"
                />

                <Label class="font-medium">Kondisi</Label>
                <Select v-model="qcItem.newCondition">
                    <SelectTrigger><SelectValue placeholder="Pilih Kondisi" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="good">Siap Jual</SelectItem>
                        <SelectItem value="broken">Rusak</SelectItem>
                    </SelectContent>
                </Select>

            </div>

            <DialogFooter>
                <Button variant="secondary" @click="qcModal = false">Batal</Button>
                <Button @click="submitQC">Simpan</Button>
            </DialogFooter>

        </DialogContent>
    </Dialog>

</template>
