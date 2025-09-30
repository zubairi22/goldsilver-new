<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogTrigger,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogCancel,
    AlertDialogAction,
} from "@/components/ui/alert-dialog";

defineProps<{
    trigger?: string | null;
    title: string;
    description: string;
    confirmText?: string;
    cancelText?: string;
    confirmVariant?: "default" | "destructive" | "outline";
}>();

const emit = defineEmits<{
    (e: "confirm"): void;
}>();
</script>

<template>
    <AlertDialog>
        <AlertDialogTrigger>
            <slot name="trigger">
                <button class="text-red-600">{{ trigger || "Open" }}</button>
            </slot>
        </AlertDialogTrigger>

        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ title }}</AlertDialogTitle>
                <AlertDialogDescription>{{ description }}</AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <AlertDialogCancel>{{ cancelText || "Batal" }}</AlertDialogCancel>
                <AlertDialogAction
                    :variant="confirmVariant || 'destructive'"
                    @click="emit('confirm')"
                >
                    {{ confirmText || "Hapus" }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
