<script setup lang="ts">
import { ref, watch, onBeforeUnmount } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { BrowserQRCodeReader } from '@zxing/browser';

const props = defineProps<{ open: boolean }>();
const emit = defineEmits(['update:open', 'scanned', 'error']);

const videoElem = ref<HTMLVideoElement | null>(null);
const isScanning = ref(false);
let controls: any = null;
let qrReader: BrowserQRCodeReader | null = null;

const startScan = async () => {
    if (isScanning.value) return;

    if (!qrReader) {
        qrReader = new BrowserQRCodeReader();
    }

    isScanning.value = true;

    try {
        controls = await qrReader.decodeFromVideoDevice(
            undefined,
            videoElem.value!,
            (result) => {
                if (result) {
                    emit('scanned', result.getText());
                    stopScan();
                    emit('update:open', false);
                }
            }
        );
    } catch (error: any) {
        emit('error', 'Tidak dapat mengakses kamera ' + error);
        isScanning.value = false;
    }
};

const stopScan = () => {
    if (controls) {
        controls.stop();
        controls = null;
    }
    isScanning.value = false;
};

watch(
    () => props.open,
    (open) => {
        if (!open) stopScan();
    }
);

onBeforeUnmount(() => stopScan());
</script>

<template>
    <Dialog :open="props.open" @update:open="val => emit('update:open', val)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Scan QR Code</DialogTitle>
                <DialogDescription>Arahkan kamera ke QR code kasir.</DialogDescription>
            </DialogHeader>

            <div class="space-y-4 text-center">
                <video ref="videoElem" class="rounded border w-full h-60" autoplay></video>

                <Button @click="startScan" :disabled="isScanning">
                    {{ isScanning ? 'Memindai...' : 'Mulai Scan' }}
                </Button>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)">Tutup</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
