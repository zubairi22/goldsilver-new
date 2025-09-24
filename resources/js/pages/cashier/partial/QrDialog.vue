<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from '@/components/ui/dialog';

defineProps(['modelValue', 'imagePath']);

const emits = defineEmits(['update:modelValue']);

const close = () => emits('update:modelValue', false);
</script>

<template>
    <Dialog :open="modelValue" @update:open="v => emits('update:modelValue', v)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Scan QR</DialogTitle>
            </DialogHeader>

            <div class="flex justify-center p-4">
                <img
                    v-if="imagePath"
                    :src="`/storage/${imagePath}`"
                    alt="QR Code"
                    class="max-w-full max-h-[400px] border rounded"
                />
                <div v-else class="text-gray-500">Tidak ada QR untuk ditampilkan.</div>
            </div>

            <DialogFooter>
                <Button @click="close">Tutup</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
