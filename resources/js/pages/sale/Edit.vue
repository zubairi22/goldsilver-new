<script lang="ts" setup>
import CameraUploader from '@/components/CameraUploader.vue';
import CurrencyInput from '@/components/CurrencyInput.vue';
import DeleteButton from '@/components/DeleteButton.vue';
import Icon from '@/components/Icon.vue';
import ImageModal from '@/components/ImageModal.vue';
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
import { useBarcodeScanner } from '@/composables/useBarcodeScanner';
import { useFormat } from '@/composables/useFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Multiselect from '@vueform/multiselect';
import { Eye, EyeOff } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    category: 'gold' | 'silver';
    sale: any;
    paymentMethods: any[];
    items: any[];
    cashiers: any[];
}>();

const { formatRupiah } = useFormat();

const categoryLabel = computed(() => (props.category === 'gold' ? 'Emas' : 'Perak'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: `Penjualan ${categoryLabel.value}`, href: route('sales.index', { category: props.category }) },
    { title: 'Edit', href: '#' },
];

const form = useForm({
    sale_type: props.sale.sale_type,
    mode: props.category === 'gold' ? 'auto' : 'manual',
    image: undefined as File | undefined,
    customer: props.sale.customer ?? '',
    payment_method_id: Number(props.sale.payment_method_id) ?? 1,
    paid_amount: props.sale.paid_amount,
    cashier_id: props.sale.user_id || props.cashiers?.[0]?.id,
    password: '',
    qr_token: '',
    notes: props.sale.notes ?? '',
    items: props.sale.items.map((i: any) => ({
        sale_item_id: i.id,
        id: i.item_id,
        manual_name: i.manual_name,
        weight: i.weight,
        price: i.price,
        subtotal: i.subtotal,
        mode: i.source === 'manual' ? 'manual' : 'auto',
        image: i.manual_image,
    })),
});

const verifyModal = ref(false);
const successModal = ref(false);
const savedSale = ref<any>(null);

const showAddItemModal = ref(false);
const editIndex = ref<number | null>(null);
const isAddingItem = ref(false);
const isRemovingItem = ref(false);
const isDirty = ref(false);

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
        sale_item_id: null,
    };
    isDirty.value = false;
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
        sale_item_id: it.sale_item_id,
    };
    isDirty.value = false;
    editIndex.value = index;
    showAddItemModal.value = true;
};

const pendingAction = ref<{ type: string; payload?: any } | null>(null);

const executePendingAction = () => {
    if (!pendingAction.value) return;

    const verificationData =
        props.sale.status !== 'draft'
            ? {
                  cashier_id: form.cashier_id,
                  password: form.password,
                  qr_token: form.qr_token,
              }
            : {};

    const { type, payload } = pendingAction.value;

    if (type === 'addItem') {
        isAddingItem.value = true;
        router.post(
            route('sales.addItem', { category: props.category, sale: props.sale.id }),
            { ...payload, ...verificationData },
            {
                onSuccess: () => {
                    toast.success('Item berhasil disimpan.');
                    showAddItemModal.value = false;
                    verifyModal.value = false;
                    pendingAction.value = null;
                    resetVerification();
                },
                onFinish: () => {
                    isAddingItem.value = false;
                },
            },
        );
    } else if (type === 'removeItem') {
        isRemovingItem.value = true;
        router.delete(route('sales.removeItem', { category: props.category, sale: props.sale.id }), {
            data: { ...payload, ...verificationData },
            onFinish: () => {
                isRemovingItem.value = false;
            },
            onSuccess: () => {
                toast.success('Item berhasil dihapus.');
                verifyModal.value = false;
                pendingAction.value = null;
                resetVerification();
            },
        });
    } else if (type === 'updateSale') {
        form.transform((data) => ({
            ...data,
            ...verificationData,
            _method: 'PATCH',
        })).post(route('sales.update', { category: props.category, sale: props.sale.id }), {
            preserveScroll: true,
            onSuccess: (page) => {
                savedSale.value = page.props.flash.sale;
                verifyModal.value = false;
                successModal.value = true;
                pendingAction.value = null;
                resetVerification();
            },
        });
    }
};

const removeItem = (index: number) => {
    const item = form.items[index];
    pendingAction.value = { type: 'removeItem', payload: { sale_item_id: item.sale_item_id } };
    if (props.sale.status !== 'draft') {
        verifyModal.value = true;
    } else {
        executePendingAction();
    }
};

const saveModalItem = () => {
    if (modalItem.value.mode === 'auto' && !modalItem.value.id) {
        toast.error('Silakan pilih barang dari stok.');
        return;
    }
    if (modalItem.value.subtotal <= 0) {
        toast.error('Subtotal harus valid.');
        return;
    }

    pendingAction.value = { type: 'addItem', payload: modalItem.value };
    if (props.sale.status !== 'draft') {
        verifyModal.value = true;
    } else {
        executePendingAction();
    }
};

const updateModalItem = () => {
    pendingAction.value = { type: 'addItem', payload: modalItem.value };
    if (props.sale.status !== 'draft') {
        verifyModal.value = true;
    } else {
        executePendingAction();
    }
};

const resetVerification = () => {
    if (props.sale.status === 'draft') return;

    form.password = '';
    form.qr_token = '';
};

const totalPrice = computed(() => Math.round(form.items.reduce((sum: number, i: any) => sum + Number(i.subtotal || 0), 0)));

const totalWeight = computed(() => Number(form.items.reduce((sum: number, i: any) => sum + Number(i.weight || 0), 0).toFixed(2)));

const change = computed(() => {
    const raw = Number(form.paid_amount) - totalPrice.value;
    return raw < 0 ? 0 : Math.round(raw);
});

watch(
    () => [modalItem.value.weight, modalItem.value.price],
    ([w, p]) => {
        if (!isDirty.value) return;
        modalItem.value.subtotal = Math.round(Number(w) * Number(p));
    },
);

const setExactPayment = () => {
    form.paid_amount = totalPrice.value;
};

const cannotModifyItems = computed(() => props.sale.status !== 'draft' && props.category === 'gold');

const openVerifyModal = () => {
    if (!form.items.length) {
        toast.error('Minimal 1 item harus ditambahkan.');
        return;
    }
    pendingAction.value = { type: 'updateSale' };
    verifyModal.value = true;
};

const submitSaleFinal = () => {
    executePendingAction();
};

const printReceipt = () => {
    window.open(
        route('sales.print', {
            category: props.category,
            sale: props.sale.id,
        }),
        '_blank',
    );
};

const scanModal = ref(false);
const scanItemModal = ref(false);

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

watch(
    () => props.sale.items,
    (newItems) => {
        form.items = newItems.map((i: any) => ({
            sale_item_id: i.id,
            id: i.item_id,
            manual_name: i.manual_name,
            weight: i.weight,
            price: i.price,
            subtotal: i.subtotal,
            image: i.manual_image,
            mode: i.source === 'manual' ? 'manual' : 'auto',
        }));
    },
    { immediate: true, deep: true },
);

watch(successModal, (val) => {
    if (!val) {
        router.visit(route('sales.index', { category: props.category }));
    }
});

const itemsWithWeight = computed(() => {
    return props.items.map((it: any) => ({
        ...it,
        label: `${it.name} (${it.weight} gr)`,
    }));
});

const onBarcodeScanned = (code: string) => {
    console.log('SCAN:', code);
    console.log(
        'ITEMS:',
        props.items.map((i: any) => i.code),
    );
    const item = props.items.find((i: any) => i.code === code);
    if (item) {
        modalItem.value = {
            id: item.id,
            mode: 'auto',
            manual_name: '',
            weight: item.weight,
            price: item.price_sell,
            subtotal: Math.round(Number(item.weight) * Number(item.price_sell)),
            image: undefined,
        };
        editIndex.value = null;
        showAddItemModal.value = true;
        toast.success(`Item ditemukan: ${item.name}`);
    } else {
        toast.error(`Barcode ${code} tidak ditemukan.`);
    }
};

useBarcodeScanner(onBarcodeScanned);

(window as any).scan = (code: string) => {
    onBarcodeScanned(code);
};

const isPasswordVisible = ref(false);

const togglePasswordVisibility = () => {
    isPasswordVisible.value = !isPasswordVisible.value;
};
</script>

<template>
    <Head :title="`Edit Penjualan ${categoryLabel}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-6">
            <div class="max-w-8xl mx-auto space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="mb-1">Informasi Penjualan {{ categoryLabel }}</CardTitle>
                        <hr />
                    </CardHeader>

                    <CardContent class="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label>Tipe Penjualan</Label>
                            <Select disabled v-model="form.sale_type">
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
                                    <SelectItem v-if="category === 'gold'" value="auto">Dari Stok</SelectItem>
                                    <SelectItem value="manual">Manual</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <div class="mb-1 flex items-center justify-between">
                            <CardTitle>Daftar Item</CardTitle>
                            <div class="flex items-center gap-2" v-if="!cannotModifyItems">
                                <Button variant="secondary" @click="scanItemModal = true">
                                    <Icon name="camera" class="mr-2 h-4 w-4" />
                                    Scan Item
                                </Button>
                                <Button @click="addItem">Tambah Item</Button>
                            </div>
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
                                        :class="cannotModifyItems ? '' : 'cursor-pointer hover:bg-muted/50'"
                                        @click="!cannotModifyItems && editItem(Number(index))"
                                    >
                                        <TableCell>
                                            <span v-if="item.mode === 'auto'">
                                                {{ items.find((i: any) => Number(i.id) === Number(item.id))?.name ?? '-' }}
                                            </span>
                                            <span v-else>{{ item.manual_name }}</span>
                                        </TableCell>

                                        <TableCell>{{ item.weight }} g</TableCell>
                                        <TableCell>{{ formatRupiah(Number(item.price)) }}</TableCell>
                                        <TableCell>{{ formatRupiah(Number(item.subtotal)) }}</TableCell>

                                        <TableCell @click.stop v-if="!cannotModifyItems">
                                            <DeleteButton size="icon" @confirm="removeItem(Number(index))" />
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

                <Card>
                    <CardHeader>
                        <CardTitle class="mb-1">Pembayaran</CardTitle>
                        <hr />
                    </CardHeader>

                    <CardContent class="space-y-8">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="rounded-lg border bg-muted/40 p-4">
                                <div class="text-sm text-muted-foreground">Total Berat</div>
                                <div class="mt-1 text-2xl font-bold">{{ totalWeight }} g</div>
                            </div>

                            <div class="rounded-lg border bg-muted/40 p-4">
                                <div class="text-sm text-muted-foreground">Total Harga</div>
                                <div class="mt-1 text-2xl font-bold">{{ formatRupiah(Number(totalPrice)) }}</div>
                            </div>

                            <div class="rounded-lg border bg-muted/40 p-4">
                                <div class="text-sm text-muted-foreground">Kembalian</div>
                                <div class="mt-1 text-2xl font-bold">{{ formatRupiah(Number(change)) }}</div>
                            </div>
                        </div>

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
                                    <InputError :message="form.errors.payment_method_id" />
                                </div>

                                <div>
                                    <Label>Bayar</Label>
                                    <div class="flex items-center gap-2">
                                        <CurrencyInput v-model="form.paid_amount" class="flex-1" />
                                        <Button type="button" @click="setExactPayment">PAS</Button>
                                    </div>
                                    <InputError :message="form.errors.paid_amount" />
                                </div>
                            </div>

                            <div class="flex flex-col gap-6">
                                <div>
                                    <Label>Pelanggan</Label>
                                    <Input type="text" v-model="form.customer" />
                                </div>
                                <div>
                                    <Label>Upload Foto</Label>
                                    <div class="flex items-center gap-2">
                                        <CameraUploader v-model="form.image" />
                                        <ImageModal v-if="sale.sale_image" :src="sale.sale_image" trigger filename="foto-transaksi.webp" />
                                    </div>
                                </div>

                                <div>
                                    <Label>Keterangan</Label>
                                    <Textarea v-model="form.notes" rows="2" placeholder="Tulis keterangan..." />
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <Button @click="openVerifyModal" class="px-6" :disabled="form.processing"> Simpan Transaksi </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

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
                            :options="itemsWithWeight"
                            value-prop="id"
                            label="label"
                            searchable
                            placeholder="Pilih barang"
                            @change="
                                (value) => {
                                    const it = items.find((p: any) => p.id === value);
                                    modalItem.price = it?.price_sell ?? 0;
                                    modalItem.weight = it?.weight ?? 0;
                                    modalItem.subtotal = Math.round(Number(modalItem.weight) * Number(modalItem.price));
                                }
                            "
                        />
                    </div>

                    <div v-else>
                        <Label>Nama Barang</Label>
                        <Input v-model="modalItem.manual_name" />

                        <template v-if="category === 'gold'">
                            <Label class="mt-2">Foto Barang</Label>
                            <div class="flex items-center gap-2">
                                <CameraUploader v-model="modalItem.image" />
                                <ImageModal
                                    v-if="modalItem.image && typeof modalItem.image === 'string'"
                                    :src="modalItem.image"
                                    trigger
                                    filename="foto-barang.webp"
                                />
                            </div>
                        </template>
                    </div>

                    <div>
                        <Label>Berat (g)</Label>
                        <Input type="number" v-model.number="modalItem.weight" @input="isDirty = true" />
                    </div>

                    <div>
                        <Label>Harga</Label>
                        <Input type="number" v-model.number="modalItem.price" @input="isDirty = true" />
                    </div>

                    <div>
                        <Label>Subtotal</Label>
                        <Input type="number" v-model.number="modalItem.subtotal" />
                        <p class="mt-1 text-xs text-muted-foreground">Otomatis terjumlah (Berat * Harga), silakan ubah jika perlu pembulatan.</p>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" :disabled="isAddingItem" @click="showAddItemModal = false">Batal</Button>
                    <Button v-if="editIndex === null" :disabled="isAddingItem" @click="saveModalItem">
                        <Icon v-if="isAddingItem" name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                        Tambah
                    </Button>
                    <Button v-else :disabled="isAddingItem" @click="updateModalItem">
                        <Icon v-if="isAddingItem" name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                        Simpan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="verifyModal" @update:open="(val) => (verifyModal = val)">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Verifikasi Perubahan</DialogTitle>
                    <DialogDescription>Masukkan password Admin untuk menyimpan perubahan.</DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div>
                        <Label>Admin / Kasir</Label>
                        <Select v-model="form.cashier_id">
                            <SelectTrigger><SelectValue placeholder="Pilih user" /></SelectTrigger>
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
                        <Label>Password / QR Admin</Label>

                        <div class="flex w-full items-center gap-2">
                            <div class="relative w-full">
                                <Input
                                    id="password"
                                    :type="isPasswordVisible ? 'text' : 'password'"
                                    required
                                    v-model="form.password"
                                    class="w-full pr-10"
                                />

                                <button
                                    type="button"
                                    @click="togglePasswordVisibility"
                                    class="absolute top-2 right-2 text-sm"
                                    :aria-label="isPasswordVisible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                >
                                    <EyeOff class="size-5" v-if="isPasswordVisible" />
                                    <Eye class="size-5" v-else />
                                </button>
                            </div>

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

        <QrScanner v-model:open="scanModal" @scanned="onQrScanned" />

        <QrScanner v-model:open="scanItemModal" @scanned="onBarcodeScanned" />

        <Dialog :open="successModal" @update:open="(val) => (successModal = val)">
            <DialogContent class="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Transaksi Berhasil Diperbarui</DialogTitle>
                </DialogHeader>

                <div v-if="sale" class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Invoice</span>
                        <span class="font-semibold">{{ sale.invoice_no }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Total Berat</span>
                        <span class="font-semibold">{{ totalWeight }} gr</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Total Harga</span>
                        <span class="font-semibold">{{ formatRupiah(Number(totalPrice)) }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Dibayar</span>
                        <span class="font-semibold">{{ formatRupiah(Number(form.paid_amount)) }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Kembalian</span>
                        <span class="font-semibold">{{ formatRupiah(Number(change)) }}</span>
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
