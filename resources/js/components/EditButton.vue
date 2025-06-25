<template>
    <Button
        :disabled="disabled"
        :class="{
            'hover:bg-yellow-500 active:bg-yellow-700': !disabled,
            'opacity-50 cursor-not-allowed': disabled
        }"
        class="inline-flex items-center rounded-md border border-transparent bg-yellow-600 p-3 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out"
    >
        <Pencil/>
    </Button>
</template>
<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Pencil } from 'lucide-vue-next'

defineProps<{
    disabled?: {
        type: boolean,
        default: false
    }
}>()
</script>
