<script setup lang="ts">
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { ref, watch } from 'vue'

const props = defineProps<{
    src: string | null
    open?: boolean
    trigger?: boolean
    filename?: string
}>()

const emit = defineEmits(['update:open'])

const internalOpen = ref(false)

watch(() => props.open, (val) => {
    if (val !== undefined) internalOpen.value = val!
})

const close = () => {
    internalOpen.value = false
    emit('update:open', false)
}
</script>

<template>
    <div>
        <img
            v-if="trigger && src"
            :src="src"
            alt="image"
            class="h-15 w-15 cursor-pointer rounded object-cover"
            @click="internalOpen = true; emit('update:open', true)"
        />

        <Dialog :open="internalOpen" @update:open="(val) => { if (!val) close() }">
            <DialogContent class="max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Preview Gambar</DialogTitle>
                    <DialogDescription>
                        Klik download untuk menyimpan gambar.
                    </DialogDescription>
                </DialogHeader>

                <div class="flex justify-center items-center max-h-[60vh] overflow-auto p-4 rounded">
                    <img
                        :src="src || ''"
                        class="max-h-[60vh] max-w-full object-contain rounded"
                        alt="image"
                    />
                </div>

                <DialogFooter class="flex justify-between pt-4">
                    <Button variant="secondary" @click="close">Tutup</Button>

                    <Button
                        v-if="src && filename"
                        :href="src"
                        as="a"
                        :download="filename"
                    >
                        Download
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
