<script lang="ts" setup>
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import DeleteButton from '@/components/DeleteButton.vue';
import Icon from '@/components/Icon.vue';
import { useFormat } from '@/composables/useFormat';

defineProps(['modelValue', 'drafts', 'isPrinting']);

const emits = defineEmits<{
    (e: 'update:modelValue', v: boolean): void;
    (e: 'use', draft: any): void;
    (e: 'delete', id: number): void;
    (e: 'print', draft: any): void;
}>();

const { formatRupiah } = useFormat();

const close = () => emits('update:modelValue', false);
</script>

<template>
    <Dialog :open="modelValue" @update:open="v => emits('update:modelValue', v)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Daftar Order Sementara</DialogTitle>
                <DialogDescription>Pilih order yang ingin dilanjutkan.</DialogDescription>
            </DialogHeader>

            <div v-if="drafts.length" class="mt-2">
                <ul class="space-y-2">
                    <li
                        v-for="draft in drafts"
                        :key="draft.id"
                        class="flex items-center justify-between rounded border p-2"
                    >
                        <div>
                            <div class="text-sm font-semibold">{{ draft.note }}</div>
                            <div class="text-xs text-gray-500">{{ draft.items.length }} item</div>
                            <div class="text-xs font-semibold text-gray-500">
                                {{ formatRupiah(draft.items.reduce((sum: any, item: any) => sum + item.quantity * item.selling_price, 0)) }}
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <Button size="sm" variant="secondary" @click="$emit('print', draft)" :disabled="isPrinting">
                                <Icon name="printer" />
                            </Button>

                            <Button size="sm" @click="$emit('use', draft)">Gunakan</Button>

                            <DeleteButton variant="destructive" size="sm" @click="$emit('delete', draft.id)" />
                        </div>
                    </li>
                </ul>
            </div>

            <div v-else class="text-gray-500 mt-2">Tidak ada order tersimpan.</div>

            <DialogFooter class="mt-4">
                <Button @click="close">Tutup</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
