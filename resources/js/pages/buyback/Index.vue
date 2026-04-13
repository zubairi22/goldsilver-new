<script lang="ts" setup>
import Heading from '@/components/Heading.vue';
import Icon from '@/components/Icon.vue';
import ImageModal from '@/components/ImageModal.vue';
import PageNav from '@/components/PageNav.vue';
import SearchInput from '@/components/SearchInput.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import DialogDescription from '@/components/ui/dialog/DialogDescription.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useBarcodeScanner } from '@/composables/useBarcodeScanner';
import { useFormat } from '@/composables/useFormat';
import { useSearch } from '@/composables/useSearch';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { Eye, Printer } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps(['buybacks', 'filters', 'category']);

const categoryLabel = computed(() => (props.category === 'gold' ? 'Emas' : 'Perak'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: `Buyback ${categoryLabel.value}`, href: '#' },
];

const { formatRupiah, formatDate } = useFormat();

const { search } = useSearch('buyback.index', props.filters.search, ['buybacks'], { category: props.category });

useBarcodeScanner((code: string) => {
    search.value = code;
    toast.success(`Scan: ${code}`);
});

const payment_type = ref(props.filters.payment_type ?? 'all');
const date = ref(props.filters.date ?? null);
const qc_status = ref(props.filters.qc_status ?? 'all');
const sortBy = ref(props.filters.sort || 'created_at');
const sortDirection = ref(props.filters.direction || 'desc');

const qcModal = ref(false);
const qcItem = ref<any>(null);

const openQC = (item: any) => {
    qcItem.value = {
        ...item,
        newCondition: item.condition ?? null,
        newName: item.manual_name ?? item.item?.name,
        newWeight: item.weight,
        newSellPrice: item.item?.price_sell ?? 0,
    };
    qcModal.value = true;
};

const submitQC = () => {
    router.patch(
        route('buyback.item.qc', {
            category: props.category,
            buybackItem: qcItem.value.id,
        }),
        {
            name: qcItem.value.newName,
            weight: qcItem.value.newWeight,
            price_sell: qcItem.value.newSellPrice,
            condition: qcItem.value.newCondition,
        },
        { onSuccess: () => (qcModal.value = false) },
    );
};

const printLabel = (item: any, preview = false) => {
    const params: any = {
        category: props.category,
        buybackItem: item.id,
    };

    if (preview) params.preview = 1;

    window.open(route('buyback.item.print-label', params), '_blank');
};

const labelModal = ref(false);
const labelDateRange = ref<string[] | null>(null);

const openPrintModal = () => {
    labelModal.value = true;
};

const printBulkLabel = (preview = false) => {
    if (!labelDateRange.value) return;

    const [start, end] = labelDateRange.value;

    const params: any = {
        category: props.category,
        start_date: start,
        end_date: end,
    };

    if (preview) params.preview = 1;

    window.open(route('buyback.bulk.print-label', params), '_blank');

    labelModal.value = false;
};

const toggleSort = (column: string) => {
    if (sortBy.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortDirection.value = 'desc';
    }
    applyFilters();
};

const applyFilters = () => {
    const params: Record<string, any> = {};

    if (search.value) params.search = search.value;
    if (payment_type.value && payment_type.value !== 'all') params.payment_type = payment_type.value;
    if (qc_status.value && qc_status.value !== 'all') params.qc_status = qc_status.value;
    if (date.value) params.date = date.value;
    params.sort = sortBy.value;
    params.direction = sortDirection.value;

    router.get(route('buyback.index', { category: props.category }), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch([payment_type, date, qc_status], applyFilters);
</script>

<template>
    <Head :title="`Buyback ${categoryLabel}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <div class="mx-4 flex items-center justify-between">
                <Heading
                    :title="`Buyback ${categoryLabel}`"
                    :description="`Daftar transaksi buyback ${categoryLabel.toLowerCase()} (Pencarian pakai No Bukti atau Nama Barang)`"
                />

                <div class="flex items-center gap-2">
                    <Button variant="info" @click="openPrintModal">
                        <Printer />
                        Cetak Label
                    </Button>

                    <Button v-if="category === 'silver'" variant="secondary" @click="router.get(route('buyback.create.manual', { category }))">
                        + Buyback Manual
                    </Button>
                </div>
            </div>

            <div class="max-w-8xl mx-auto">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <!-- FILTERS -->
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                            <div class="flex flex-wrap items-center gap-4">
                                <div class="w-40">
                                    <Select v-model="payment_type">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pembayaran" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua</SelectItem>
                                            <SelectItem value="cash">Cash</SelectItem>
                                            <SelectItem value="non_cash">Non Cash</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="w-40">
                                    <Select v-model="qc_status">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Status QC" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua</SelectItem>
                                            <SelectItem value="pending">Belum QC</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div class="w-full sm:w-auto lg:w-80">
                                <VueDatePicker
                                    v-model="date"
                                    model-type="yyyy-MM-dd"
                                    :enable-time-picker="false"
                                    auto-apply
                                    placeholder="Pilih tanggal"
                                />
                            </div>
                        </div>

                        <!-- SEARCH -->
                        <div class="mb-3 flex w-full justify-end">
                            <div class="w-full lg:w-80">
                                <SearchInput v-model:search="search" placeholder="Cari No. Buyback / Nama Item..." />
                            </div>
                        </div>

                        <!-- TABLE -->
                        <div class="overflow-x-auto">
                            <Table class="w-full text-nowrap">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="cursor-pointer select-none" @click="toggleSort('created_at')">
                                            No Buyback
                                            <Icon
                                                :name="sortBy === 'created_at' ? (sortDirection === 'asc' ? 'ChevronUp' : 'ChevronDown') : 'ChevronsUpDown'"
                                                class="ml-1 inline-block h-3 w-3"
                                            />
                                        </TableHead>
                                        <TableHead>Gambar</TableHead>
                                        <TableHead>Nama Barang</TableHead>
                                        <TableHead>Kasir</TableHead>
                                        <TableHead class="cursor-pointer text-right select-none" @click="toggleSort('weight')">
                                            Berat
                                            <Icon
                                                :name="sortBy === 'weight' ? (sortDirection === 'asc' ? 'ChevronUp' : 'ChevronDown') : 'ChevronsUpDown'"
                                                class="ml-1 inline-block h-3 w-3"
                                            />
                                        </TableHead>
                                        <TableHead class="cursor-pointer text-right select-none" @click="toggleSort('price')">
                                            Harga/g
                                            <Icon
                                                :name="sortBy === 'price' ? (sortDirection === 'asc' ? 'ChevronUp' : 'ChevronDown') : 'ChevronsUpDown'"
                                                class="ml-1 inline-block h-3 w-3"
                                            />
                                        </TableHead>
                                        <TableHead class="cursor-pointer text-right select-none" @click="toggleSort('subtotal')">
                                            Subtotal
                                            <Icon
                                                :name="sortBy === 'subtotal' ? (sortDirection === 'asc' ? 'ChevronUp' : 'ChevronDown') : 'ChevronsUpDown'"
                                                class="ml-1 inline-block h-3 w-3"
                                            />
                                        </TableHead>
                                        <TableHead class="text-center">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <template v-for="it in buybacks.data" :key="it.id">
                                        <TableRow>
                                            <TableCell>
                                                <div class="font-semibold">{{ it.buyback?.buyback_no }}</div>
                                                <div class="text-xs text-muted-foreground">{{ formatDate(it.buyback?.created_at, 'dd MMM yyyy HH:mm') }}</div>
                                                <div class="text-xs text-muted-foreground italic">{{ it.buyback?.customer || '-' }}</div>
                                            </TableCell>
                                            <TableCell>
                                                <ImageModal v-if="it.image" :src="it.image" trigger />
                                            </TableCell>
                                            <TableCell>{{ it.manual_name ?? it.item?.name }}</TableCell>
                                            <TableCell>{{ it.buyback?.user?.name || '-' }}</TableCell>
                                            <TableCell class="text-right">{{ it.weight }} g</TableCell>
                                            <TableCell class="text-right">{{ formatRupiah(it.price) }}</TableCell>
                                            <TableCell class="text-right font-semibold">{{ formatRupiah(it.subtotal) }}</TableCell>
                                            <TableCell class="text-center">
                                                <div v-if="category === 'silver'" class="flex items-center justify-center gap-2">
                                                    <Badge> Selesai </Badge>
                                                </div>
                                                <template v-else>
                                                    <div v-if="it.condition === 'good'" class="flex items-center justify-center gap-1">
                                                        <Button variant="outline" size="sm" @click="printLabel(it, true)" title="Preview Label">
                                                            <Eye class="h-4 w-4" />
                                                        </Button>
                                                        <Button variant="info" size="sm" @click="printLabel(it)" title="Cetak Label">
                                                            <Printer class="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                    <Badge v-else-if="it.condition === 'broken'"> Rusak </Badge>
                                                    <Button v-else size="sm" @click="openQC(it)"> Proses QC </Button>
                                                </template>
                                            </TableCell>
                                        </TableRow>
                                    </template>

                                    <TableRow v-if="!buybacks.total">
                                        <TableCell colspan="7" class="py-4 text-center"> Tidak ada data buyback ditemukan. </TableCell>
                                    </TableRow>
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
    <Dialog :open="qcModal" @update:open="(v) => (qcModal = v)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Proses QC Item</DialogTitle>
            </DialogHeader>

            <div v-if="qcItem">
                <Label>Nama Item</Label>
                <Input v-model="qcItem.newName" class="mb-3" />

                <Label>Berat Akhir</Label>
                <Input v-model.number="qcItem.newWeight" type="number" step="0.01" class="mb-3" :disabled="qcItem.newCondition === 'broken'" />

                <Label>Harga Jual</Label>
                <Input v-model.number="qcItem.newSellPrice" type="number" class="mb-3" :disabled="qcItem.newCondition === 'broken'" />

                <Label>Kondisi</Label>
                <Select v-model="qcItem.newCondition">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="good">Siap Jual</SelectItem>
                        <SelectItem value="broken">Rusak</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <DialogFooter>
                <Button variant="secondary" @click="qcModal = false">Batal</Button>
                <Button @click="submitQC"> Simpan </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="labelModal" @update:open="(v) => (labelModal = v)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Cetak Label Buyback</DialogTitle>
                <DialogDescription> Pilih rentang tanggal buyback yang sudah QC selesai. </DialogDescription>
            </DialogHeader>

            <div class="mt-4 min-h-72">
                <VueDatePicker
                    v-model="labelDateRange"
                    range
                    model-type="yyyy-MM-dd"
                    :enable-time-picker="false"
                    auto-apply
                    placeholder="Pilih range tanggal"
                />
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <Button variant="outline" @click="labelModal = false"> Batal </Button>
                <Button variant="secondary" :disabled="!labelDateRange" @click="printBulkLabel(true)"> Preview Label </Button>
                <Button :disabled="!labelDateRange" @click="printBulkLabel(false)"> Tampilkan & Cetak </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
