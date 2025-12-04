<template>
    <AlertDialog>
        <AlertDialogTrigger as-child>
            <Button class="p-3" variant="destructive" :disabled="disabled">
                <Trash />
            </Button>
        </AlertDialogTrigger>

        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Apakah Anda yakin?</AlertDialogTitle>
                <AlertDialogDescription>
                    Tindakan ini tidak dapat dibatalkan. Item ini akan dihapus secara permanen dari sistem.
                </AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <AlertDialogCancel>Batal</AlertDialogCancel>

                <AlertDialogAction @click="$emit('confirm')">
                    Lanjutkan
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

<script lang="ts" setup>
import { Button } from '@/components/ui/button'
import { Trash } from 'lucide-vue-next'
import {
    AlertDialog,
    AlertDialogTrigger,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogCancel,
    AlertDialogAction
} from '@/components/ui/alert-dialog'

withDefaults(
    defineProps<{
        disabled?: boolean
    }>(),
    {
        disabled: false
    }
)
</script>
