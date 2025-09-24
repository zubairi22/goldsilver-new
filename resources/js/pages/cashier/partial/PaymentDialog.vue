<script lang="ts" setup>
import { ref, computed } from 'vue';
import Multiselect from '@vueform/multiselect';
import CurrencyInput from '@/components/CurrencyInput.vue';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { LoaderCircle } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { useFormat } from '@/composables/useFormat';

const form = defineModel<any>('form')
const customerId = defineModel<any>('customerId')

const { redeemPoints, totalPrice } =
    defineProps([
        'modelValue',
        'redeemPoints',
        'maxRedeemPoints',
        'paymentMethods',
        'customers',
        'totalPrice'
    ])

const emits = defineEmits<{
    (e: 'update:modelValue', v: boolean): void;
    (e: 'update:redeemPoints', v: number): void;
    (e: 'submit'): void;
}>();

const { formatRupiah } = useFormat();

const showQr = ref(false);
const qrPath = ref<string | null>(null);
const openQr = (path: string | null) => {
    qrPath.value = path;
    showQr.value = true;
};
const closeQr = () => {
    showQr.value = false;
    qrPath.value = null;
};

const pointDiscount = computed(() => (redeemPoints || 0) * 1000);
const totalAfterDiscount = computed(() => Math.max(0, (totalPrice || 0) - pointDiscount.value));
const changeAmount = computed(() => (form?.value.paid_amount || 0) - totalAfterDiscount.value);

const close = () => emits('update:modelValue', false);
const onSubmit = () => emits('submit');
</script>

<template>
    <Dialog :open="modelValue" @update:open="v => emits('update:modelValue', v)">
        <DialogContent @interactOutside.prevent>
            <DialogHeader>
                <DialogTitle>Pembayaran</DialogTitle>
                <div class="text-sm text-muted-foreground">Lengkapi detail pembayaran transaksi.</div>
            </DialogHeader>

            <div class="space-y-4 mt-4">
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
                                @click="openQr(paymentMethods.find((pm: any) => pm.id === form.payment_method_id)?.image_path)"
                            >
                                Lihat QR
                            </Button>
                        </div>
                    </div>
                    <InputError :message="form.errors?.payment_method_id" class="mt-1" />
                </div>

                <div>
                    <Label for="paid_amount">Jumlah Bayar</Label>
                    <div class="flex items-center gap-2">
                        <CurrencyInput v-model="form.paid_amount" />
                        <Button type="button" @click="form.paid_amount = totalPrice">Pas</Button>
                    </div>
                    <InputError :message="form.errors?.paid_amount" class="mt-1" />
                </div>

                <div>
                    <Label for="customer">Pelanggan</Label>
                    <Multiselect
                        v-model="customerId"
                        :options="customers"
                        value-prop="id"
                        label="name"
                        searchable
                    />
                    <InputError :message="form.errors?.customer_id" class="mt-1" />
                </div>

                <template v-if="customerId && maxRedeemPoints > 0">
                    <div>
                        <Label for="redeem_points">Gunakan Poin</Label>
                        <div class="flex items-center gap-2">
                            <input
                                id="redeem_points"
                                type="number"
                                :max="maxRedeemPoints"
                                :min="0"
                                class="flex-1 input"
                                :value="redeemPoints"
                                @input="(e: Event) => {
                                    const target = e.target as HTMLInputElement | null
                                    if (target) {
                                        const val = Number(target.value || 0)
                                        emits('update:redeemPoints', Math.min(val, maxRedeemPoints))
                                    }
                                }"
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

            <DialogFooter class="gap-2 mt-4">
                <Button variant="secondary" @click="close">Batal</Button>
                <Button :disabled="form.processing || totalPrice === 0" @click="onSubmit">
                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Simpan Transaksi
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="showQr" @update:open="v => showQr = v">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Scan QR</DialogTitle>
            </DialogHeader>
            <div class="flex justify-center p-4">
                <img
                    v-if="qrPath"
                    :src="`/storage/${qrPath}`"
                    alt="QR Code"
                    class="max-w-full max-h-[400px] border rounded"
                />
                <div v-else class="text-gray-500">Tidak ada QR untuk ditampilkan.</div>
            </div>
            <DialogFooter>
                <Button @click="closeQr">Tutup</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
