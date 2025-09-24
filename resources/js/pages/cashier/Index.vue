<script lang="ts" setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { LoaderCircle } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { useFormat } from '@/composables/useFormat';
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
import Multiselect from '@vueform/multiselect';
import { useBluetoothPrinter } from '@/composables/useBluetoothPrinter';
import axios from 'axios';
import CurrencyInput from '@/components/CurrencyInput.vue';
import { useTime } from '@/composables/useTime';
import QrDialog from '@/pages/cashier/partial/QrDialog.vue';
import DraftsDialog from '@/pages/cashier/partial/DraftsDialog.vue';
import SuccessDialog from '@/pages/cashier/partial/SuccessDialog.vue';
import ProductCard from '@/pages/cashier/partial/ProductCard.vue';
import CartCard from '@/pages/cashier/partial/CartCard.vue';

const { products, customers } = defineProps(['products', 'customers', 'paymentMethods']);

const { formatRupiah } = useFormat();
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
                    <ProductCard
                        :products="products"
                        @add-to-cart="addToCart"
                    />
                </div>

                <div class="md:col-span-2">
                    <CartCard
                        :items="form.items"
                        :totalPrice="totalPrice"
                        @edit-item="editItemCart"
                        @remove-item="removeItem"
                        @clear-cart="form.items = []"
                        @save-draft="saveDraft"
                        @pay="paymentModal = true"
                    />
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

    <SuccessDialog
        v-model="successModal"
        :lastTransaction="lastTransaction"
        :isPrinting="isPrinting"
        @print="handlePrintReceipt"
    />

    <DraftsDialog
        v-model="draftModal"
        :drafts="draftList"
        :isPrinting="isPrinting"
        @use="loadDraftItems"
        @delete="deleteDraft"
        @print="handlePrintDraft"
    />

    <QrDialog v-model="showQrModal" :imagePath="selectedQr" />
</template>

<style src="@vueform/multiselect/themes/default.css" />
