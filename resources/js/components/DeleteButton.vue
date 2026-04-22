<template>
    <AlertDialog>
        <AlertDialogTrigger as-child>
            <Button class="p-3" variant="destructive" :disabled="disabled">
                <Trash />
                {{ text }}
            </Button>
        </AlertDialogTrigger>

        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Apakah Anda yakin?</AlertDialogTitle>
                <AlertDialogDescription> Tindakan ini tidak dapat dibatalkan. Item ini akan dihapus dari sistem. </AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <AlertDialogCancel>Batal</AlertDialogCancel>

                <AlertDialogAction @click="$emit('confirm')"> Lanjutkan </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

<script lang="ts" setup>
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Trash } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        disabled?: boolean;
        text?: string;
    }>(),
    {
        disabled: false,
    },
);
</script>
