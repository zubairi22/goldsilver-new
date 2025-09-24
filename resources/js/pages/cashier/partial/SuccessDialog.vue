<script lang="ts" setup>
import { computed } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Alert, AlertTitle } from '@/components/ui/alert';
import { useFormat } from '@/composables/useFormat';

const { lastTransaction } = defineProps(['modelValue', 'lastTransaction', 'isPrinting'])

const emits = defineEmits<{
    (e: 'update:modelValue', v: boolean): void;
    (e: 'print'): void;
}>();

const { formatRupiah } = useFormat();

const totalFormatted = computed(() => formatRupiah(lastTransaction?.total ?? 0));
const paidFormatted = computed(() => formatRupiah(lastTransaction?.paid ?? 0));
const changeValue = computed(() => lastTransaction?.change ?? 0);
const changeFormatted = computed(() => formatRupiah(Math.abs(changeValue.value)));

const close = () => emits('update:modelValue', false);
const onPrint = () => emits('print');
</script>

<template>
    <Dialog :open="modelValue" @update:open="v => emits('update:modelValue', v)">
        <DialogContent @interactOutside.prevent>
            <DialogHeader>
                <DialogTitle>Transaksi Berhasil</DialogTitle>
                <DialogDescription>Transaksi telah disimpan dengan sukses.</DialogDescription>
            </DialogHeader>

            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span>Total Tagihan:</span>
                    <span class="font-semibold">{{ totalFormatted }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>Jumlah Bayar:</span>
                    <span class="font-semibold">{{ paidFormatted }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>{{ changeValue >= 0 ? 'Kembalian:' : 'Sisa Piutang:' }}</span>
                    <span :class="['font-semibold', changeValue < 0 ? 'text-red-600' : '']">
                        {{ changeFormatted }}
                    </span>
                </div>

                <Alert class="bg-yellow-600 text-white" v-if="changeValue < 0">
                    <AlertTitle>Transaksi dengan Kondisi ini akan masuk kedalam menu Piutang</AlertTitle>
                </Alert>
            </div>

            <DialogFooter class="mt-4 gap-2">
                <Button variant="secondary" @click="close">Tutup</Button>
                <Button v-if="changeValue >= 0" :disabled="isPrinting" @click="onPrint">Cetak Struk</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
