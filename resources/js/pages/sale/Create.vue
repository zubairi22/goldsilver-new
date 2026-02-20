<script lang="ts" setup>
import CameraUploader from '@/components/CameraUploader.vue';
import CurrencyInput from '@/components/CurrencyInput.vue';
import DeleteButton from '@/components/DeleteButton.vue';
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import QrScanner from '@/components/QrScanner.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { useFormat } from '@/composables/useFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Multiselect from '@vueform/multiselect';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    category: 'gold' | 'silver';
    paymentMethods: any[];
    customers: any[];
    items: any[];
    cashiers: any[];
}>();

const { formatRupiah } = useFormat();

const categoryLabel = computed(() => (props.category === 'gold' ? 'Emas' : 'Perak'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: `Penjualan ${categoryLabel.value}`, href: `/sale/${props.category}` },
    { title: 'Tambah', href: '#' },
];

const form = useForm({
    sale_type: 'retail',
    mode: 'auto',
    customer_id: '',
    payment_method_id: props.paymentMethods?.[0]?.id ?? null,
    paid_amount: 0,
    cashier_id: props.cashiers?.[0]?.id ?? null,
    password: '',
    qr_token: '',
    notes: '',
    items: [] as any[],
});

const verifyModal = ref(false);
const successModal = ref(false);
const savedSale = ref<any>(null);

const showAddItemModal = ref(false);
const editIndex = ref<number | null>(null);

const modalItem = ref<any>({
    id: null,
    mode: 'auto',
    manual_name: '',
    weight: 0,
    price: 0,
    subtotal: 0,
    image: undefined,
});

const addItem = () => {
    modalItem.value = {
        id: null,
        mode: form.mode,
        manual_name: '',
        weight: 0,
        price: 0,
        subtotal: 0,
        image: undefined,
    };
    editIndex.value = null;
    showAddItemModal.value = true;
};

const editItem = (index: number) => {
    const it = form.items[index];
    modalItem.value = {
        id: it.id ?? null,
        mode: it.mode,
        manual_name: it.manual_name ?? '',
        weight: it.weight,
        price: it.price,
        subtotal: it.subtotal,
        image: it.image,
    };
    editIndex.value = index;
    showAddItemModal.value = true;
};

const removeItem = (index: number) => {
    form.items.splice(index, 1);
};

watch(
    () => [modalItem.value.weight, modalItem.value.price],
    ([w, p]) => {
        modalItem.value.subtotal = Math.round(Number(w) * Number(p));
    },
);

const saveModalItem = () => {
    if (modalItem.value.mode === 'auto' && !modalItem.value.id) {
        toast.error('Silakan pilih barang dari stok.');
        return;
    }
    if (modalItem.value.mode === 'manual' && !modalItem.value.manual_name) {
        toast.error('Nama barang harus diisi.');
        return;
    }
    if (modalItem.value.subtotal <= 0) {
        toast.error('Subtotal harus valid.');
        return;
    }

    form.items.push({
        id: modalItem.value.id,
        manual_name: modalItem.value.manual_name,
        weight: modalItem.value.weight,
        price: modalItem.value.price,
        subtotal: modalItem.value.subtotal,
        image: modalItem.value.image,
        mode: modalItem.value.mode,
    });

    showAddItemModal.value = false;
};

const updateModalItem = () => {
    if (editIndex.value === null) return;

    form.items[editIndex.value] = {
        id: modalItem.value.id,
        manual_name: modalItem.value.manual_name,
        weight: modalItem.value.weight,
        price: modalItem.value.price,
        subtotal: modalItem.value.subtotal,
        image: modalItem.value.image,
        mode: modalItem.value.mode,
    };

    editIndex.value = null;
    showAddItemModal.value = false;
};

const totalPrice = computed(() => Math.round(form.items.reduce((sum, i) => sum + Number(i.subtotal || 0), 0)));

const totalWeight = computed(() => Number(form.items.reduce((sum, i) => sum + Number(i.weight || 0), 0).toFixed(2)));

const change = computed(() => {
    const raw = Number(form.paid_amount) - totalPrice.value;
    return raw < 0 ? 0 : Math.round(raw);
});

const setExactPayment = () => {
    form.paid_amount = totalPrice.value;
};

const openVerifyModal = () => {
    if (!form.items.length) {
        toast.error('Minimal 1 item harus ditambahkan.');
        return;
    }
    verifyModal.value = true;
};

const submitSaleFinal = () => {
    form.post(route('sales.store', { category: props.category }), {
        preserveScroll: true,
        onSuccess: (page) => {
            savedSale.value = page.props.flash.sale;
            verifyModal.value = false;
            successModal.value = true;
            form.reset();
        },
    });
};

const printReceipt = () => {
    window.open(
        route('sales.print', {
            category: props.category,
            sale: savedSale.value.id,
        }),
        '_blank',
    );
};

const scanModal = ref(false);

const onQrScanned = (token: string) => {
    form.qr_token = token;
    const cashier = props.cashiers.find((c: any) => c.qr_token === token);
    if (cashier) {
        form.cashier_id = cashier.id;
        toast.success('Kasir terverifikasi via QR!');
    } else {
        toast.error('QR tidak dikenal.');
    }
};

watch(successModal, (val) => {
    if (!val && savedSale.value) {
        setTimeout(() => {
            router.visit(route('sales.index', { category: props.category }));
        }, 1500);
    }
});
</script>

<template>
    <Head :title="`Tambah Penjualan ${categoryLabel}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-6">
            <div class="max-w-8xl mx-auto space-y-6">
                <!-- INFO PENJUALAN -->
                <Card>
                    <CardHeader>
                        <CardTitle class="mb-1">Informasi Penjualan Emas</CardTitle>
                        <hr />
                    </CardHeader>

                    <CardContent class="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label>Tipe Penjualan</Label>
                            <Select v-model="form.sale_type">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="retail">Eceran</SelectItem>
                                    <SelectItem value="wholesale">Partai</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label>Mode Input</Label>
                            <Select v-model="form.mode">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="auto">Dari Stok</SelectItem>
                                    <SelectItem value="manual">Manual</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                <!-- ITEM -->
                <Card>
                    <CardHeader>
                        <div class="mb-1 flex items-center justify-between">
                            <CardTitle>Daftar Item</CardTitle>
                            <Button @click="addItem">Tambah Item</Button>
                        </div>
                        <hr />
                    </CardHeader>

                    <CardContent>
                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-[35%]">Nama Barang</TableHead>
                                        <TableHead class="w-28">Berat (g)</TableHead>
                                        <TableHead class="w-32">Harga/gram</TableHead>
                                        <TableHead class="w-32">Subtotal</TableHead>
                                        <TableHead class="w-8"></TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow
                                        v-for="(item, index) in form.items"
                                        :key="index"
                                        class="cursor-pointer hover:bg-muted/50"
                                        @click="editItem(index)"
                                    >
                                        <TableCell>
                                            <span v-if="item.mode === 'auto'">
                                                {{ items.find((i: any) => i.id === item.id)?.name ?? '-' }}
                                            </span>
                                            <span v-else>{{ item.manual_name }}</span>
                                        </TableCell>

                                        <TableCell>{{ item.weight }} g</TableCell>
                                        <TableCell>{{ formatRupiah(item.price) }}</TableCell>
                                        <TableCell>{{ formatRupiah(item.subtotal) }}</TableCell>

                                        <TableCell @click.stop>
                                            <DeleteButton size="icon" @confirm="removeItem(index)" />
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="!form.items.length">
                                        <TableCell colspan="10" class="py-4 text-center"> Belum ada item yang ditambahkan. </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

                <!-- PEMBAYARAN -->
                <Card>
                    <CardHeader>
                        <CardTitle class="mb-1">Pembayaran</CardTitle>
                        <hr />
                    </CardHeader>

                    <CardContent class="space-y-8">
                        <!-- Summary Box -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="rounded-lg border bg-muted/40 p-4">
                                <div class="text-sm text-muted-foreground">Total Berat</div>
                                <div class="mt-1 text-2xl font-bold">{{ totalWeight }} g</div>
                            </div>

                            <div class="rounded-lg border bg-muted/40 p-4">
                                <div class="text-sm text-muted-foreground">Total Harga</div>
                                <div class="mt-1 text-2xl font-bold">{{ formatRupiah(totalPrice) }}</div>
                            </div>

                            <div class="rounded-lg border bg-muted/40 p-4">
                                <div class="text-sm text-muted-foreground">Kembalian</div>
                                <div class="mt-1 text-2xl font-bold">{{ formatRupiah(change) }}</div>
                            </div>
                        </div>

                        <!-- Detail Payment -->
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="flex flex-col gap-6">
                                <div>
                                    <Label>Jenis Pembayaran</Label>
                                    <Select v-model="form.payment_method_id">
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem v-for="pm in paymentMethods" :key="pm.id" :value="pm.id">
                                                    {{ pm.name }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label>Bayar</Label>
                                    <div class="flex items-center gap-2">
                                        <CurrencyInput v-model="form.paid_amount" class="flex-1" />
                                        <Button type="button" @click="setExactPayment">PAS</Button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-6">
                                <div>
                                    <Label>Pelanggan</Label>
                                    <Multiselect
                                        v-model="form.customer_id"
                                        :options="customers"
                                        searchable
                                        placeholder="Pilih atau tambah pelanggan"
                                        :create-option="true"
                                        :add-option="true"
                                    />
                                </div>

                                <div>
                                    <Label>Keterangan</Label>
                                    <Textarea v-model="form.notes" rows="2" placeholder="Tulis keterangan..." />
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 text-right">
                            <Button @click="openVerifyModal" class="px-6">Simpan Transaksi</Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- MODAL: ADD ITEM -->
        <Dialog v-model:open="showAddItemModal" @update:open="(val) => (showAddItemModal = val)">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ editIndex === null ? 'Tambah Item' : 'Edit Item' }}</DialogTitle>
                </DialogHeader>

                <div class="space-y-4">
                    <div v-if="modalItem.mode === 'auto'">
                        <Label>Barang dari Stok</Label>
                        <Multiselect
                            v-model="modalItem.id"
                            :options="items"
                            value-prop="id"
                            label="name"
                            searchable
                            placeholder="Pilih barang"
                            @change="
                                (value) => {
                                    const it = items.find((p: any) => p.id === value);
                                    modalItem.price = it?.price_sell ?? 0;
                                    modalItem.weight = it?.weight ?? 0;
                                }
                            "
                        />
                    </div>

                    <div v-else>
                        <Label>Nama Barang</Label>
                        <Input v-model="modalItem.manual_name" />

                        <Label class="mt-2">Foto Barang</Label>
                        <CameraUploader v-model="modalItem.image" />
                    </div>

                    <div>
                        <Label>Berat (g)</Label>
                        <Input type="number" v-model.number="modalItem.weight" />
                    </div>

                    <div>
                        <Label>Harga</Label>
                        <Input type="number" v-model.number="modalItem.price" />
                    </div>

                    <div>
                        <Label>Subtotal</Label>
                        <Input type="number" v-model.number="modalItem.subtotal" />
                        <p class="mt-1 text-xs text-muted-foreground">Otomatis terjumlah (Berat * Harga), silakan ubah jika perlu pembulatan.</p>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showAddItemModal = false">Batal</Button>
                    <Button v-if="editIndex === null" @click="saveModalItem">Tambah</Button>
                    <Button v-else @click="updateModalItem">Simpan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- MODAL: VERIFIKASI TRANSAKSI -->
        <Dialog v-model:open="verifyModal" @update:open="(val) => (verifyModal = val)">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Verifikasi Transaksi</DialogTitle>
                    <DialogDescription>Pilih kasir & masukkan password kasir untuk melanjutkan.</DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div>
                        <Label>Pilih Kasir</Label>
                        <Select v-model="form.cashier_id" :disabled="!$page.props.auth.isAdmin">
                            <SelectTrigger><SelectValue placeholder="Pilih kasir" /></SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem v-for="c in cashiers" :key="c.id" :value="c.id">
                                        {{ c.name }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>

                        <InputError :message="form.errors.cashier_id" />
                    </div>

                    <div>
                        <Label>Password / QR Kasir</Label>

                        <div class="flex items-center gap-2">
                            <Input type="password" v-model="form.password" class="flex-1" />

                            <!-- Tombol Scan QR -->
                            <Button type="button" variant="secondary" @click="scanModal = true">
                                <icon name="camera" />
                            </Button>
                        </div>

                        <InputError :message="form.errors.password" />
                    </div>
                </div>

                <DialogFooter class="mt-4">
                    <Button variant="outline" @click="verifyModal = false">Batal</Button>
                    <Button @click="submitSaleFinal">Verifikasi & Simpan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- MODAL: SCAN QR -->
        <QrScanner v-model:open="scanModal" @scanned="onQrScanned" />

        <!-- MODAL: SUCCESS -->
        <Dialog :open="successModal" @update:open="(val) => (successModal = val)">
            <DialogContent class="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Transaksi Berhasil</DialogTitle>
                </DialogHeader>

                <div v-if="savedSale" class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Invoice</span>
                        <span class="font-semibold">{{ savedSale.invoice_no }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Total Berat</span>
                        <span class="font-semibold">{{ savedSale.total_weight }} gr</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Total Harga</span>
                        <span class="font-semibold">{{ formatRupiah(savedSale.total_price) }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Dibayar</span>
                        <span class="font-semibold">{{ formatRupiah(savedSale.paid_amount) }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Kembalian</span>
                        <span class="font-semibold">{{ formatRupiah(savedSale.change_amount) }}</span>
                    </div>

                    <div v-if="savedSale.notes" class="flex justify-between">
                        <span class="text-muted-foreground">Catatan</span>
                        <span class="font-semibold">{{ savedSale.notes }}</span>
                    </div>
                </div>

                <DialogFooter class="mt-4 flex justify-end gap-3">
                    <Button variant="secondary" @click="successModal = false">Tutup</Button>
                    <Button @click="printReceipt()">Cetak Nota</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<style src="@vueform/multiselect/themes/default.css" />
