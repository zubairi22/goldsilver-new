<script lang="ts" setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { LoaderCircle } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import DeleteButton from '@/components/DeleteButton.vue';
import { useFormat } from '@/composables/useFormat';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useSearch } from '@/composables/useSearch';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger
} from '@/components/ui/alert-dialog';
import Multiselect from '@vueform/multiselect';
import PageNav from '@/components/PageNav.vue';
import { Alert, AlertTitle } from '@/components/ui/alert';
import { useBluetoothPrinter } from '@/composables/useBluetoothPrinter';
import axios from 'axios';
import CurrencyInput from '@/components/CurrencyInput.vue';
import Icon from '@/components/Icon.vue';
import { useTime } from '@/composables/useTime';

const { products, customers } = defineProps(['products', 'customers', 'paymentMethods']);

const { formatRupiah } = useFormat();
const { search } = useSearch('cashier.index', '', ['products']);
const { connectPrinter, printText, isConnected } = useBluetoothPrinter();
const { formatNow } = useTime()

const form = useForm({
    items: [] as any[],
    paid_amount: 0,
    payment_method_id: 1,
    customer_id: '',
    discount_amount: 0,
    redeemed_points: 0,
});

const addModal = ref(false);
const selectedProduct = ref<any>(null);
const selectedUnitId = ref<number | null>(null);
const quantity = ref(1);

const editCartItemIndex = ref<number|null>(null)

const successModal = ref(false);
const lastTransaction = ref<{ total: number; paid: number; change: number } | null>(null);

const addToCart = (product: any) => {
    selectedProduct.value = product;
    selectedUnitId.value = product.units[0].id;
    quantity.value = 1;
    addModal.value = true;
};

const editItemCart = (item: any, index: number) => {
    editCartItemIndex.value = index

    selectedProduct.value = item.product;
    selectedUnitId.value = item.unit_id;
    quantity.value = item.quantity;
    addModal.value = true;
};

const confirmAddToCart = () => {
    if (!selectedProduct.value || !selectedUnitId.value) return;
    const unit = selectedProduct.value.units.find((u: any) => u.id === selectedUnitId.value);
    if (!unit) return;

    const qty = Math.max(1, Number(quantity.value) || 1);

    const item = {
        product_id: selectedProduct.value.id,
        unit_id: unit.id,
        name: selectedProduct.value.name,
        unit_name: unit.name,
        purchase_price: unit.pivot.purchase_price,
        selling_price: unit.pivot.selling_price,
        quantity: qty,
        product: selectedProduct.value,
    }

    if (editCartItemIndex.value !== null) {
        const existsIndex = form.items.findIndex((it: any) => it.product_id === item.product_id && it.unit_id === item.unit_id);
        if (existsIndex === editCartItemIndex.value) {
            form.items.splice(existsIndex, 1, item);
        } else if (existsIndex !== -1) {
            form.items[existsIndex].quantity = form.items[existsIndex].quantity + qty;
            form.items.splice(editCartItemIndex.value, 1);
        } else {
            form.items.splice(editCartItemIndex.value, 1, item);
        }
    } else {
        const existsIndex = form.items.findIndex((it: any) => it.product_id === item.product_id && it.unit_id === item.unit_id);
        if (existsIndex !== -1) {
            form.items[existsIndex].quantity = Number(form.items[existsIndex].quantity || 0) + qty;
        } else {
            form.items.push(item);
        }
    }

    editCartItemIndex.value = null
    addModal.value = false;
};

const removeItem = (index: number) => {
    form.items.splice(index, 1);
};

const paymentModal = ref(false);
const customerId = ref('');
const redeemPoints = ref(0);
const maxRedeemPoints = ref(0);

const totalPrice = computed(() => {
    return form.items.reduce((sum, item) => sum + item.quantity * item.selling_price, 0);
});

const pointDiscount = computed(() => redeemPoints.value * 1000);

const totalAfterDiscount = computed(() => {
    return Math.max(0, totalPrice.value - pointDiscount.value);
});

const changeAmount = computed(() => {
    return (form.paid_amount || 0) - totalAfterDiscount.value;
});

function handlePaymentToggle(val: boolean) {
    paymentModal.value = val;

    if (!val) {
        form.payment_method_id = 1;
        form.paid_amount = 0;
        customerId.value = '';
        redeemPoints.value = 0;
    }
}


const showQrModal = ref(false)
const selectedQr = ref<string | null>(null)

const openQr = (url: string) => {
    selectedQr.value = url
    showQrModal.value = true
}

const submitTransaction = () => {
    form.customer_id = customerId.value;
    form.discount_amount = pointDiscount.value;
    form.redeemed_points = redeemPoints.value;

    form.post(route('cashier.store'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            lastTransaction.value = {
                total: totalPrice.value,
                paid: form.paid_amount,
                change: changeAmount.value,
            };

            form.reset();
            customerId.value = '';
            redeemPoints.value = 0;
            paymentModal.value = false;
            successModal.value = true;

            if (draftId.value) {
                deleteDraft(draftId.value);
            }
        },
    });
};

const draftModal = ref(false);
const draftId = ref<number | null>(null);
const draftList = ref<any[]>([]);

const saveDraft = () => {
    if (form.items.length === 0) return alert('Tidak ada item untuk disimpan.');

    const drafts: any[] = JSON.parse(localStorage.getItem('order_drafts') || '[]');
    const now = Date.now();

    if (draftId.value) {
        const idx = drafts.findIndex((d: any) => d.id === draftId.value);
        if (idx !== -1) {
            drafts[idx] = {
                ...drafts[idx],
                items: JSON.parse(JSON.stringify(form.items)),
                note: drafts[idx].note || 'Order sementara ' + formatNow,
                updated_at: now,
            };
            localStorage.setItem('order_drafts', JSON.stringify(drafts));
            alert('Draft diperbarui!');
            form.items = [];
            draftId.value = null;
            return;
        }
    }

    drafts.push({
        id: now,
        items: JSON.parse(JSON.stringify(form.items)),
        note: 'Order sementara ' + formatNow(),
        created_at: now,
        updated_at: now,
    });
    localStorage.setItem('order_drafts', JSON.stringify(drafts));
    alert('Order berhasil disimpan sementara!');
    form.items = [];
};

const loadDrafts = () => {
    draftList.value = JSON.parse(localStorage.getItem('order_drafts') || '[]');
}

const loadDraftItems = (draft: any) => {
    draftId.value = draft.id;
    form.items = draft.items;
    draftModal.value = false;
}

const deleteDraft = (id: number) => {
    const drafts = JSON.parse(localStorage.getItem('order_drafts') || '[]');
    const updated = drafts.filter((d: any) => d.id !== id);
    localStorage.setItem('order_drafts', JSON.stringify(updated));
    loadDrafts();
    if (draftId.value === id) {
        draftId.value = null;
    }
}

const isPrinting = ref(false);

const handlePrintDraft = async (draft: any) => {
    try {
        isPrinting.value = true;

        const response = await axios.post('/api/receipt/draft', {
            items: draft.items,
            note: draft.note,
            cashier: usePage().props.auth.user.name
        });

        const receipt = response.data.receipt;
        const connected = isConnected.value || (await connectPrinter());
        if (!connected) return alert('Gagal konek ke printer');

        await printText(receipt);
    } catch (err) {
        console.error('Error saat cetak draft:', err);
        alert('Gagal cetak order sementara');
    } finally {
        isPrinting.value = false;
    }
};

const handlePrintReceipt = async () => {
    try {
        isPrinting.value = true;

        const response = await axios.get(`/api/receipt/${usePage().props.flash.transaction_number}`);
        const receipt = response.data.receipt;
        const connected = isConnected.value || (await connectPrinter());
        if (!connected) return alert('Gagal konek ke printer');

        await printText(receipt);
    } catch (err) {
        console.error('Error saat cetak:', err);
        alert('Gagal ambil atau cetak struk');
    } finally {
        isPrinting.value = false;
    }
};

const barcodeBuffer = ref('');
let scanTimeout: ReturnType<typeof setTimeout> | null = null;

const handleScannerBuffer = (e: KeyboardEvent) => {
    if (addModal.value || paymentModal.value || draftModal.value || successModal.value) return;

    const char = e.key;

    if (scanTimeout) clearTimeout(scanTimeout);

    if (char.length === 1 && /[a-zA-Z0-9]/.test(char)) {
        barcodeBuffer.value += char;
    }

    if (char === 'Enter') {
        const scanned = barcodeBuffer.value;
        barcodeBuffer.value = '';

        fetch(`/api/products/search?sku=${scanned}`)
            .then((response) => response.json())
            .then((data) => {
                if (data.success && data.product) {
                    const product = data.product;
                    const unit = product.units.find((u: any) => u.pivot.sku === scanned);

                    const exists = form.items.find((item) => item.product_id === product.id && item.unit_id === unit.id);

                    if (exists) {
                        exists.quantity += 1;
                    } else {
                        form.items.push({
                            product_id: product.id,
                            unit_id: unit.id,
                            name: product.name,
                            unit_name: unit.name,
                            purchase_price: unit.pivot.purchase_price,
                            selling_price: unit.pivot.selling_price,
                            quantity: 1,
                            product: product,
                        });
                    }
                } else {
                    alert(`Produk dengan SKU "${scanned}" tidak ditemukan.`);
                }
            })
            .catch((error) => {
                alert('Terjadi kesalahan saat mengambil data produk.');
                console.error(error);
            });
    }

    scanTimeout = setTimeout(() => {
        barcodeBuffer.value = '';
    }, 300);
};

onMounted(() => {
    window.addEventListener('keydown', handleScannerBuffer);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleScannerBuffer);
    if (scanTimeout) clearTimeout(scanTimeout);
});

watch(customerId, (val) => {
    const customer = customers.find((c: any) => c.id === val);
    maxRedeemPoints.value = customer?.current_year_point?.points || 0;
    redeemPoints.value = 0;
});
</script>

<template>
    <Head title="Kasir" />

    <AppLayout>
        <div class="px-4 py-6">
            <div class="flex items-center justify-between">
                <Heading title="Transaksi Kasir" description="Lakukan transaksi penjualan produk" />
                <Button
                    variant="outline"
                    size="lg"
                    @click="
                        draftModal = true;
                        loadDrafts();
                    "
                >
                    Daftar Order
                </Button>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                <div class="md:col-span-3">
                    <Card>
                        <CardHeader>
                            <div class="flex gap-2">
                                <Input v-model="search" placeholder="Cari produk berdasarkan nama dan sku" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                                <div
                                    v-for="product in products.data"
                                    :key="product.id"
                                    class="cursor-pointer rounded-lg border p-4 shadow-sm transition hover:shadow-md"
                                    @click="addToCart(product)"
                                >
                                    <div class="line-clamp-2 min-h-[3rem] text-base font-medium" :title="product.name">
                                        {{ product.name }}
                                    </div>

                                    <div class="flex justify-between items-center mt-2">
                                        <div class="text-md font-semibold text-green-700">
                                            {{ formatRupiah(product.units[0].pivot.selling_price) }}
                                        </div>
                                        <div
                                            class="text-sm font-medium"
                                            :class="product.stock < 50 ? 'text-red-600' : 'text-gray-800'"
                                        >
                                            ({{ product.stock }})
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <b v-if="!products.total">Produk tidak di Temukan</b>
                            <div class="mt-4 flex justify-center overflow-x-auto">
                                <PageNav :data="products" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="md:col-span-2">
                    <Card class="flex min-h-[28rem] h-[39rem] flex-col">
                        <CardHeader>
                            <CardTitle class="pb-0">Keranjang</CardTitle>
                        </CardHeader>
                        <CardContent class="flex-1">
                            <div class="max-h-[26rem] overflow-y-auto" v-if="form.items.length">
                                <Table class="w-full text-sm">
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead class="h-8 w-12" />
                                            <TableHead class="h-8 text-start">Produk</TableHead>
                                            <TableHead class="h-8 text-center">Subtotal</TableHead>
                                            <TableHead class="h-8 w-8" />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow
                                            v-for="(item, index) in form.items"
                                            :key="index"
                                            @click="editItemCart(item, index)"
                                        >
                                            <TableCell class="text-center px-0">
                                                {{ item.quantity }}
                                            </TableCell>
                                            <TableCell>
                                                <div class="flex flex-col leading-tight break-words whitespace-normal">
                                                    <span class="font-medium">{{ item.name }}</span>
                                                    <span class="text-xs text-muted-foreground">{{ item.unit_name }}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell class="text-center px-0">{{ formatRupiah(item.quantity * item.selling_price) }}</TableCell>
                                            <TableCell class="px-2" @click.stop>
                                                <DeleteButton @click="removeItem(index)">Hapus</DeleteButton>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                            <div v-else class="text-gray-500">Belum ada item.</div>
                        </CardContent>
                        <div class="-mb-6 flex flex-col gap-2 border-t p-4">
                            <div class="flex items-center justify-between gap-2">
                                <AlertDialog>
                                    <AlertDialogTrigger>
                                        <DeleteButton size="sm"/>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Hapus isi keranjang?</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                Keranjang akan dikosongkan
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Batal</AlertDialogCancel>
                                            <AlertDialogAction variant="destructive" @click="form.items = []">
                                                Hapus
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                                <Button class="flex-1" variant="outline" size="sm" @click="saveDraft"> Simpan Order </Button>
                            </div>

                            <Button
                                class="flex h-12 w-full items-center justify-between text-lg"
                                :disabled="form.items.length === 0"
                                @click="paymentModal = true"
                            >
                                <span>Bayar</span>
                                <span>{{ formatRupiah(totalPrice) }}</span>
                            </Button>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="addModal" @update:open="(val) => (addModal = val)">
        <DialogContent @interactOutside.prevent>
            <DialogHeader>
                <DialogTitle>Detail Produk</DialogTitle>
                <DialogDescription>{{ selectedProduct.name }}.</DialogDescription>
            </DialogHeader>
            <div class="flex gap-6">
                <div class="w-1/2">
                    <Label for="unit">Satuan</Label>
                    <Select v-model="selectedUnitId">
                        <SelectTrigger id="satuan" class="w-full">
                            <SelectValue placeholder="Pilih Satuan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem v-for="(unit, index) in selectedProduct.units" :key="unit.id" :value="unit.id">
                                    {{ unit.name }} - {{ formatRupiah(unit.pivot.selling_price) }}
                                    <span v-if="index > 0"> ({{ unit.pivot.conversion }} {{ selectedProduct.units[0].name }}) </span>
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>
                <div class="w-1/2">
                    <Label for="quantity">Kuantitas</Label>
                    <div class="inline-flex items-center justify-center gap-2" @click.stop>
                        <Button
                            size="icon"
                            class="h-10 w-10"
                            @click.stop="quantity = Math.max(1, Math.floor(Number(quantity || 1)) - 1)"
                        >
                            -
                        </Button>

                        <Input class="h-10 text-center" v-model="quantity" type="number" @blur="quantity = quantity < 1 ? 1 : quantity" />

                        <Button
                            size="icon"
                            class="h-10 w-10"
                            @click.stop="quantity = Math.floor(Number(quantity || 0)) + 1"
                        >
                            +
                        </Button>
                    </div>
                </div>
            </div>
            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="addModal = false">Batal</Button>
                <Button @click="confirmAddToCart">Simpan</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="paymentModal" @update:open="(val) => handlePaymentToggle(val)">
        <DialogContent @interactOutside.prevent>
            <DialogHeader>
                <DialogTitle>Pembayaran</DialogTitle>
                <DialogDescription>Lengkapi detail pembayaran transaksi.</DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div>
                    <div class="text-sm text-gray-500">Total</div>
                    <div class="text-xl font-bold">{{ formatRupiah(totalPrice) }}</div>
                </div>

                <div>
                    <Label for="method">Metode Pembayaran</Label>
                    <div class="flex items-center gap-2">
                        <Select v-model="form.payment_method_id">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih Metode Pembayaran" />
                            </SelectTrigger>
                            <SelectContent class="w-60">
                                <SelectGroup>
                                    <SelectItem v-for="pm in paymentMethods" :key="pm.id" :value="pm.id">
                                        {{ pm.name }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>

                        <div v-if="form.payment_method_id">
                            <Button
                                v-if="paymentMethods.find((pm: any) => pm.id === form.payment_method_id)?.image_path"
                                type="button"
                                @click="openQr(paymentMethods.find((pm: any) => pm.id === form.payment_method_id)?.image_path!)"
                            >
                                Lihat QR
                            </Button>
                        </div>
                    </div>
                    <InputError :message="form.errors.payment_method_id" class="mt-1" />
                </div>

                <div>
                    <Label for="paid_amount">Jumlah Bayar</Label>
                    <div class="flex items-center gap-2">
                        <CurrencyInput v-model="form.paid_amount" />
                        <Button type="button" @click="form.paid_amount = totalPrice"> Pas </Button>
                    </div>
                    <InputError :message="form.errors.paid_amount" class="mt-1" />
                </div>

                <div>
                    <Label for="customer">Pelanggan</Label>
                    <Multiselect v-model="customerId" value-prop="id" label="name" :options="customers" searchable />
                    <InputError :message="form.errors.customer_id" class="mt-1" />
                </div>

                <template v-if="customerId && maxRedeemPoints > 0">
                    <div>
                        <Label for="redeem_points">Gunakan Poin</Label>
                        <div class="flex items-center gap-2">
                            <Input
                                id="redeem_points"
                                type="number"
                                v-model.number="redeemPoints"
                                :max="maxRedeemPoints"
                                :min="0"
                                class="flex-1"
                                @input="redeemPoints = Math.min(redeemPoints, maxRedeemPoints)"
                            />
                            <span class="text-sm text-gray-500">/ {{ maxRedeemPoints }} poin</span>
                        </div>
                        <div class="mt-1 text-sm text-green-600">Potongan: {{ formatRupiah(pointDiscount) }}</div>
                    </div>
                </template>

                <div>
                    <div class="text-sm text-gray-500">
                        {{ changeAmount >= 0 ? 'Kembalian' : 'Sisa Tagihan' }}
                    </div>
                    <div :class="['text-lg font-semibold', changeAmount < 0 ? 'text-red-600' : '']">
                        {{ formatRupiah(Math.abs(changeAmount)) }}
                    </div>
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="paymentModal = false">Batal</Button>
                <Button :disabled="form.processing || totalPrice === 0" @click="submitTransaction">
                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Simpan Transaksi
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="successModal" @update:open="(val) => (successModal = val)">
        <DialogContent @interactOutside.prevent>
            <DialogHeader>
                <DialogTitle>Transaksi Berhasil</DialogTitle>
                <DialogDescription>Transaksi telah disimpan dengan sukses.</DialogDescription>
            </DialogHeader>

            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span>Total Tagihan:</span>
                    <span class="font-semibold">{{ formatRupiah(lastTransaction?.total ?? 0) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>Jumlah Bayar:</span>
                    <span class="font-semibold">{{ formatRupiah(lastTransaction?.paid ?? 0) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>{{ (lastTransaction?.change ?? 0) >= 0 ? 'Kembalian:' : 'Sisa Piutang:' }}</span>
                    <span :class="['font-semibold', (lastTransaction?.change ?? 0) < 0 ? 'text-red-600' : '']">
                        {{ formatRupiah(Math.abs(lastTransaction?.change ?? 0)) }}
                    </span>
                </div>
                <Alert class="bg-yellow-600 text-white" v-if="(lastTransaction?.change ?? 0) < 0">
                    <AlertTitle>Transaksi dengan Kondisi ini akan masuk kedalam menu Piutang</AlertTitle>
                </Alert>
            </div>

            <DialogFooter class="mt-4 gap-2">
                <Button variant="secondary" @click="successModal = false">Tutup</Button>
                <Button v-if="(lastTransaction?.change ?? 0) >= 0" :disabled="isPrinting" @click="handlePrintReceipt()">Cetak Struk</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="draftModal" @update:open="(val) => (draftModal = val)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Daftar Order Sementara</DialogTitle>
                <DialogDescription>Pilih order yang ingin dilanjutkan.</DialogDescription>
            </DialogHeader>

            <div v-if="draftList.length">
                <ul class="space-y-2">
                    <li v-for="draft in draftList" :key="draft.id" class="flex items-center justify-between rounded border p-2">
                        <div>
                            <div class="text-sm font-semibold">{{ draft.note }}</div>
                            <div class="text-xs text-gray-500">{{ draft.items.length }} item</div>
                            <div class="text-xs font-semibold text-gray-500">
                                {{ formatRupiah(draft.items.reduce((sum: any, item: any) => sum + item.quantity * item.selling_price, 0)) }}
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Button size="sm" variant="secondary" @click="handlePrintDraft(draft)">
                                <icon name="printer"/>
                            </Button>
                            <Button size="sm" @click="loadDraftItems(draft)">Gunakan</Button>
                            <DeleteButton variant="destructive" size="sm" @click="deleteDraft(draft.id)" />
                        </div>
                    </li>
                </ul>
            </div>
            <div v-else class="text-gray-500">Tidak ada order tersimpan.</div>
        </DialogContent>
    </Dialog>

    <Dialog :open="showQrModal" @update:open="v => showQrModal = v">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Scan QR</DialogTitle>
            </DialogHeader>
            <div class="flex justify-center p-4">
                <img v-if="selectedQr" :src="'/storage/' + selectedQr" alt="QR Code" class="max-w-full max-h-[400px] border rounded" />
            </div>
            <DialogFooter>
                <Button @click="showQrModal = false">Tutup</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<style src="@vueform/multiselect/themes/default.css" />
