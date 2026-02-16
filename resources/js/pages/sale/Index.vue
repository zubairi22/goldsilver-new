<script lang="ts" setup>
import DeleteButton from '@/components/DeleteButton.vue';
import EditButton from '@/components/EditButton.vue';
import Heading from '@/components/Heading.vue';
import Icon from '@/components/Icon.vue';
import PageNav from '@/components/PageNav.vue';
import QrScanner from '@/components/QrScanner.vue';
import SearchInput from '@/components/SearchInput.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useFormat } from '@/composables/useFormat';
import { useSearch } from '@/composables/useSearch';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    category: string;
    sales: any;
    paymentMethods: any[];
    filters: any;
    cashiers: any[];
}>();

const categoryLabel = computed(() => {
    const map: Record<string, string> = {
        gold: 'Emas',
        silver: 'Perak',
    };
    return map[props.category] ?? props.category;
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: `Penjualan ${categoryLabel.value}`, href: '#' },
];

const { search } = useSearch('sales.index', props.filters.search, ['sales'], { category: props.category });

const { formatRupiah, formatDate } = useFormat();

const sale_type = ref(props.filters.sale_type);
const payment_method_id = ref(props.filters.payment_method_id);
const date = ref(props.filters.start && props.filters.end ? [props.filters.start, props.filters.end] : []);

const saleModal = ref(false);
const selectedSale = ref<any>(null);

const openSaleModal = (trx: any) => {
    selectedSale.value = trx;
    saleModal.value = true;
};

const goToBuyback = () => {
    router.get(
        route('buyback.create', {
            category: props.category,
            sale: selectedSale.value.id,
        }),
    );
};

const printReceipt = () => {
    window.open(
        route('sales.print', {
            category: props.category,
            sale: selectedSale.value.id,
        }),
        '_blank',
    );
};

const editSale = (sale: any) => {
    router.get(route('sales.edit', { category: props.category, sale: sale.id }));
};

const verifyModal = ref(false);
const scanModal = ref(false);
const saleToDelete = ref<any>(null);
const verifyForm = ref({
    cashier_id: props.cashiers?.[0]?.id ?? null,
    password: '',
    qr_token: '',
});

const deleteSale = (sale: any) => {
    saleToDelete.value = sale;
    verifyModal.value = true;
};

const onQrScanned = (token: string) => {
    verifyForm.value.qr_token = token;
    const cashier = props.cashiers.find((c: any) => c.qr_token === token);
    if (cashier) {
        verifyForm.value.cashier_id = cashier.id;
        toast.success('Admin terverifikasi via QR!');
        submitDelete();
    } else {
        toast.error('QR tidak dikenal.');
    }
};

const submitDelete = () => {
    if (!saleToDelete.value) return;

    router.delete(
        route('sales.destroy', {
            category: props.category,
            sale: saleToDelete.value.id,
        }),
        {
            data: {
                cashier_id: verifyForm.value.cashier_id,
                password: verifyForm.value.password,
                qr_token: verifyForm.value.qr_token,
            },
            preserveScroll: true,
            onSuccess: () => {
                verifyModal.value = false;
                saleToDelete.value = null;
                verifyForm.value.password = '';
                verifyForm.value.qr_token = '';
            },
            onError: (errors) => {
                if (errors.password) {
                    toast.error(errors.password);
                } else {
                    toast.error('Gagal menghapus penjualan.');
                }
            },
        },
    );
};

const applyFilters = () => {
    const params: Record<string, any> = {};

    if (search.value) params.search = search.value;
    if (sale_type.value && sale_type.value !== 'all') params.sale_type = sale_type.value;
    if (payment_method_id.value && payment_method_id.value !== 'all') {
        params.payment_method_id = payment_method_id.value;
    }
    if (date.value?.[0] && date.value?.[1]) {
        params.start = date.value[0];
        params.end = date.value[1];
    }

    router.get(route('sales.index', { category: props.category }), params, {
        preserveScroll: true,
        preserveState: true,
    });
};

watch([sale_type, payment_method_id, date], applyFilters);
</script>

<template>
    <Head :title="`Penjualan ${categoryLabel}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading
                class="mx-4"
                :title="`Penjualan ${categoryLabel}`"
                :description="`Daftar transaksi penjualan kategori ${categoryLabel.toLowerCase()}`"
            />

            <div class="max-w-8xl mx-auto">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <!-- FILTER -->
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                            <div class="flex flex-wrap items-center gap-4">
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

                                <div class="w-40">
                                    <Select v-model="payment_method_id">
                                        <SelectTrigger><SelectValue placeholder="Metode" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem value="all">Semua</SelectItem>
                                                <SelectItem v-for="pm in paymentMethods" :key="pm.id" :value="pm.id">
                                                    {{ pm.name }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div class="w-full sm:w-auto lg:w-80">
                                <VueDatePicker
                                    v-model="date"
                                    range
                                    :enable-time-picker="false"
                                    model-type="yyyy-MM-dd"
                                    locale="id"
                                    placeholder="Rentang tanggal"
                                />
                            </div>
                        </div>

                        <!-- SEARCH -->
                        <div class="mb-3 flex w-full justify-end">
                            <div class="w-full lg:w-80">
                                <SearchInput v-model:search="search" />
                            </div>
                        </div>

                        <!-- TABLE -->
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Tanggal</TableHead>
                                        <TableHead>Invoice</TableHead>
                                        <TableHead>Kasir</TableHead>
                                        <TableHead>Jenis</TableHead>
                                        <TableHead class="text-right">Berat</TableHead>
                                        <TableHead class="text-right">Total</TableHead>
                                        <TableHead class="text-right">Dibayar</TableHead>
                                        <TableHead class="text-right">Sisa</TableHead>
                                        <TableHead class="text-center">Metode</TableHead>
                                        <TableHead class="w-8" />
                                        <TableHead class="w-8" />
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow
                                        v-for="sale in sales.data"
                                        :key="sale.id"
                                        class="cursor-pointer hover:bg-muted/50"
                                        @click="openSaleModal(sale)"
                                    >
                                        <TableCell>{{ formatDate(sale.created_at, 'dd MMM yyyy HH:mm') }}</TableCell>
                                        <TableCell>{{ sale.invoice_no }}</TableCell>
                                        <TableCell>{{ sale.user?.name || '-' }}</TableCell>
                                        <TableCell>{{ sale.sale_type === 'retail' ? 'Eceran' : 'Grosir' }}</TableCell>
                                        <TableCell class="text-right">{{ sale.total_weight }}</TableCell>
                                        <TableCell class="text-right">{{ formatRupiah(sale.total_price) }}</TableCell>
                                        <TableCell class="text-right">{{ formatRupiah(sale.paid_amount) }}</TableCell>
                                        <TableCell class="text-right">{{ formatRupiah(sale.remaining_amount) }}</TableCell>
                                        <TableCell class="text-center">
                                            {{ sale.payment_method?.name || '-' }}
                                        </TableCell>
                                        <TableCell class="px-1">
                                            <EditButton @click.stop="editSale(sale)" />
                                        </TableCell>
                                        <TableCell class="px-1">
                                            <DeleteButton @confirm.stop="deleteSale(sale)" />
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="!sales.total">
                                        <TableCell colspan="10" class="py-4 text-center">
                                            Belum ada penjualan {{ categoryLabel.toLowerCase() }}.
                                        </TableCell>
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

    <!-- MODAL DETAIL -->
    <Dialog :open="saleModal" @update:open="(val) => (saleModal = val)">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Detail Penjualan</DialogTitle>
                <hr />
            </DialogHeader>

            <div v-if="selectedSale">
                <!-- Info utama + QR -->
                <div class="mb-4 grid grid-cols-3 gap-4 text-sm">
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
                    </div>

                    <!-- Kanan (1 kolom): QR Code -->
                    <div class="flex flex-col items-center justify-center p-4 text-center">
                        <!-- QR dari storage Laravel -->
                        <img :src="'/storage/' + selectedSale.qr_path" alt="QR" class="h-24 w-24 rounded object-contain shadow" />

                        <p class="text-md mt-4 font-semibold">
                            {{ selectedSale.invoice_no }}
                        </p>
                    </div>
                </div>

                <hr class="mb-2" />

                <!-- Tabs Section -->
                <Tabs default-value="item">
                    <TabsList class="mb-4">
                        <TabsTrigger value="item">Item</TabsTrigger>
                        <TabsTrigger value="payment">Pembayaran</TabsTrigger>
                    </TabsList>

                    <!-- TAB ITEM -->
                    <TabsContent value="item">
                        <h3 class="mb-2 font-semibold">Item</h3>

                        <Table>
                            <TableBody>
                                <template v-for="it in selectedSale.items" :key="it.id">
                                    <!-- NAMA ITEM -->
                                    <TableRow class="bg-muted/30">
                                        <TableCell colspan="3" class="font-medium">
                                            {{ it.manual_name ?? it.item?.name }}
                                        </TableCell>
                                    </TableRow>

                                    <!-- DETAIL ITEM -->
                                    <TableRow>
                                        <TableCell class="text-xs text-muted-foreground">
                                            Berat
                                            <div class="text-sm font-medium text-foreground">{{ it.weight }} gr</div>
                                        </TableCell>

                                        <TableCell class="text-right text-xs text-muted-foreground">
                                            Harga
                                            <div class="text-sm font-medium text-foreground">
                                                {{ formatRupiah(it.price) }}
                                            </div>
                                        </TableCell>

                                        <TableCell class="text-right text-xs text-muted-foreground">
                                            Subtotal
                                            <div class="text-sm font-semibold text-foreground">
                                                {{ formatRupiah(it.subtotal) }}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </template>
                            </TableBody>
                        </Table>
                    </TabsContent>

                    <!-- TAB PAYMENT -->
                    <TabsContent value="payment">
                        <h3 class="mb-2 font-semibold">Pembayaran</h3>

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

            <DialogFooter class="mt-4 flex justify-between">
                <Button variant="secondary" @click="saleModal = false">Tutup</Button>

                <Button v-if="selectedSale?.paid_amount > 0" @click="printReceipt">
                    <Icon name="printer" />
                </Button>

                <Button v-if="selectedSale && selectedSale.status === 'paid'" @click="goToBuyback()"> Proses Buyback </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- MODAL: VERIFIKASI HAPUS -->
    <Dialog v-model:open="verifyModal">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Verifikasi Penghapusan</DialogTitle>
                <DialogDescription>
                    Anda sedang mencoba menghapus penjualan <b>{{ saleToDelete?.invoice_no }}</b
                    >. Tindakan ini memerlukan verifikasi admin.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div>
                    <Label>Pilih Admin</Label>
                    <Select v-model="verifyForm.cashier_id">
                        <SelectTrigger><SelectValue placeholder="Pilih admin" /></SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem v-for="c in cashiers" :key="c.id" :value="c.id">
                                    {{ c.name }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <Label>Password / QR Admin</Label>
                    <div class="flex items-center gap-2">
                        <Input type="password" v-model="verifyForm.password" class="flex-1" />
                        <Button type="button" variant="secondary" @click="scanModal = true">
                            <icon name="camera" />
                        </Button>
                    </div>
                </div>
            </div>

            <DialogFooter class="mt-4">
                <Button variant="outline" @click="verifyModal = false">Batal</Button>
                <Button variant="destructive" @click="submitDelete">Verifikasi & Hapus</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- MODAL: SCAN QR -->
    <QrScanner v-model:open="scanModal" @scanned="onQrScanned" />
</template>
