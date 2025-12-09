<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Card, CardContent } from '@/components/ui/card'
import Heading from '@/components/Heading.vue'
import SearchInput from '@/components/SearchInput.vue'
import PageNav from '@/components/PageNav.vue'
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import VueDatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import { ref, watch } from 'vue'
import { useSearch } from '@/composables/useSearch'
import { useFormat } from '@/composables/useFormat'
import type { BreadcrumbItem } from '@/types'
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Badge } from '@/components/ui/badge';
import Icon from '@/components/Icon.vue';

const { filters } = defineProps(['sales', 'paymentMethods', 'filters'])

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Penjualan Emas', href: '#' },
]

const { search } = useSearch('gold.transactions.sales.index', filters.search, ['sales'])
const { formatRupiah, formatDate } = useFormat()

const status = ref(filters.status)
const sale_type = ref(filters.sale_type)
const payment_method_id = ref(filters.payment_method_id)
const date = ref(filters.start && filters.end ? [filters.start, filters.end] : [])

const saleModal = ref(false);
const selectedSale = ref<any>(null);

function openSaleModal(trx: any) {
    selectedSale.value = trx;
    saleModal.value = true;
}

const printReceipt = () => {
    window.open(route('gold.transactions.sales.print', selectedSale.value.id), '_blank')
}

const applyFilters = () => {
    const params: Record<string, any> = { category: 'gold' }

    if (search.value) params.search = search.value
    if (status.value !== 'all') params.status = status.value
    if (sale_type.value !== 'all') params.sale_type = sale_type.value
    if (payment_method_id.value !== 'all') params.payment_method_id = payment_method_id.value
    if (date.value && date.value[0] && date.value[1]) {
        params.start = date.value[0]
        params.end = date.value[1]
    }

    router.get(route('gold.transactions.sales.index', params), {}, {
        preserveScroll: true,
        preserveState: true,
    })
}

watch([status, sale_type, payment_method_id, date], applyFilters)
</script>

<template>
    <Head title="Penjualan Emas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Penjualan Emas" description="Daftar transaksi penjualan kategori emas" />

            <div class="max-w-8xl mx-auto">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <!-- Filter Section -->
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                            <div class="flex flex-wrap items-center gap-4">
                                <!-- Status -->
                                <div class="w-40">
                                    <Select v-model="status">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua</SelectItem>
                                            <SelectItem value="unpaid">Belum Lunas</SelectItem>
                                            <SelectItem value="partial">Sebagian</SelectItem>
                                            <SelectItem value="completed">Lunas</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Jenis Penjualan -->
                                <div class="w-40">
                                    <Select v-model="sale_type">
                                        <SelectTrigger><SelectValue placeholder="Jenis" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua</SelectItem>
                                            <SelectItem value="retail">Eceran</SelectItem>
                                            <SelectItem value="wholesale">Partai</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Metode Pembayaran -->
                                <div class="w-40">
                                    <Select v-model="payment_method_id">
                                        <SelectTrigger><SelectValue placeholder="Metode Pembayaran" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem value="all">Semua</SelectItem>
                                                <SelectItem
                                                    v-for="pm in paymentMethods"
                                                    :key="pm.id"
                                                    :value="pm.id"
                                                >
                                                    {{ pm.name }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <!-- Date Picker -->
                            <div class="w-full sm:w-auto lg:w-80">
                                <VueDatePicker
                                    v-model="date"
                                    range
                                    :enable-time-picker="false"
                                    model-type="yyyy-MM-dd"
                                    locale="id"
                                    placeholder="Rentang tanggal"
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <!-- Search -->
                        <div class="flex justify-end w-full mb-3">
                            <div class="w-full lg:w-80">
                                <SearchInput v-model:search="search" />
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-40">Tanggal</TableHead>
                                        <TableHead>Invoice</TableHead>
                                        <TableHead>Kasir</TableHead>
                                        <TableHead>Pelanggan</TableHead>
                                        <TableHead class="text-right">Total Berat (gr)</TableHead>
                                        <TableHead class="text-right">Total Harga</TableHead>
                                        <TableHead class="text-right">Dibayar</TableHead>
                                        <TableHead class="text-right">Sisa</TableHead>
                                        <TableHead class="text-center">Status</TableHead>
                                        <TableHead class="text-center">Metode</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="sale in sales.data" :key="sale.id" @click="openSaleModal(sale)">
                                        <TableCell>{{ formatDate(sale.created_at, 'dd MMM yyyy HH:mm') }}</TableCell>
                                        <TableCell>{{ sale.invoice_no }}</TableCell>
                                        <TableCell>{{ sale.user?.name || '-' }}</TableCell>
                                        <TableCell>{{ sale.customer?.name || '-' }}</TableCell>
                                        <TableCell class="text-right">{{ sale.total_weight }}</TableCell>
                                        <TableCell class="text-right">{{ formatRupiah(sale.total_price) }}</TableCell>
                                        <TableCell class="text-right">{{ formatRupiah(sale.paid_amount) }}</TableCell>
                                        <TableCell class="text-right">{{ formatRupiah(sale.remaining_amount) }}</TableCell>
                                        <TableCell class="text-center capitalize">
                                            <Badge>
                                                {{ sale.status_label }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-center">{{ sale.payment_method?.name || '-' }}</TableCell>
                                    </TableRow>

                                    <TableRow v-if="!sales.total">
                                        <TableCell colspan="10" class="text-center py-4">Belum ada penjualan emas.</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <PageNav :data="sales" />
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="saleModal" @update:open="(val) => saleModal = val">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle class="mb-1">Detail Penjualan</DialogTitle>
                <hr>
            </DialogHeader>

            <div v-if="selectedSale">
                <!-- Info utama + QR -->
                <div class="grid grid-cols-3 gap-4 mb-4 text-sm">

                    <!-- Kiri (2 kolom): Detail -->
                    <div class="col-span-2 grid grid-cols-2 gap-4">
                        <div>
                            <p class="font-semibold">Invoice</p>
                            <p>{{ selectedSale.invoice_no }}</p>
                        </div>
                        <div>
                            <p class="font-semibold">Tanggal</p>
                            <p>{{ formatDate(selectedSale.created_at, 'dd MMM yyyy HH:mm') }}</p>
                        </div>
                        <div>
                            <p class="font-semibold">Pelanggan</p>
                            <p>{{ selectedSale.customer?.name || '-' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold">Kasir</p>
                            <p>{{ selectedSale.user?.name || '-' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold">Metode Pembayaran</p>
                            <p>{{ selectedSale.payment_method?.name || '-' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold">Status</p>
                            <p class="capitalize">{{ selectedSale.status_label }}</p>
                        </div>
                    </div>

                    <!-- Kanan (1 kolom): QR Code -->
                    <div class="flex flex-col items-center justify-center p-4 text-center">

                        <!-- QR dari storage Laravel -->
                        <img
                            :src="'/storage/' + selectedSale.qr_path"
                            alt="QR"
                            class="h-24 w-24 rounded shadow object-contain"
                        />

                        <p class="mt-4 text-md font-semibold">
                            {{ selectedSale.invoice_no }}
                        </p>
                    </div>
                </div>

                <hr class="mb-2">

                <!-- Tabs Section -->
                <Tabs default-value="item">
                    <TabsList class="mb-4">
                        <TabsTrigger value="item">Item</TabsTrigger>
                        <TabsTrigger value="payment">Pembayaran</TabsTrigger>
                    </TabsList>

                    <!-- TAB ITEM -->
                    <TabsContent value="item">
                        <h3 class="font-semibold mb-2">Item</h3>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead class="text-right">Berat (gr)</TableHead>
                                    <TableHead class="text-right">Harga</TableHead>
                                    <TableHead class="text-right">Subtotal</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="it in selectedSale.items" :key="it.id">
                                    <TableCell>{{ it.manual_name ?? it.item?.name }}</TableCell>
                                    <TableCell class="text-right">{{ it.weight }}</TableCell>
                                    <TableCell class="text-right">{{ formatRupiah(it.price) }}</TableCell>
                                    <TableCell class="text-right">{{ formatRupiah(it.subtotal) }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </TabsContent>

                    <!-- TAB PAYMENT -->
                    <TabsContent value="payment">
                        <h3 class="font-semibold mb-2">Pembayaran</h3>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Tanggal</TableHead>
                                    <TableHead class="text-right">Jumlah</TableHead>
                                    <TableHead>Catatan</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="pay in selectedSale.payments" :key="pay.id">
                                    <TableCell>{{ formatDate(pay.created_at, 'dd MMM yyyy HH:mm') }}</TableCell>
                                    <TableCell class="text-right">{{ formatRupiah(pay.amount) }}</TableCell>
                                    <TableCell>{{ pay.note }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </TabsContent>
                </Tabs>
            </div>

            <DialogFooter class="flex justify-between mt-4">
                <Button variant="secondary" @click="saleModal = false">
                    Tutup
                </Button>

                <Button
                    v-if="selectedSale && selectedSale.paid_amount > 0"
                    @click="printReceipt()"
                    class="bg-blue-500 hover:bg-blue-500/90"
                >
                    <icon name="printer"/>
                </Button>

                <Button
                    v-if="selectedSale && selectedSale.status === 'paid'"
                    @click="router.get(route('buyback.gold.create',selectedSale.id))"
                >
                    Proses Buyback
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

</template>
