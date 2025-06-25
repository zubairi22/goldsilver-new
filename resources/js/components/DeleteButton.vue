EditButton.vue<template>
    <Button class="p-3" variant="destructive" :disabled="disabled">
        <Trash/>
    </Button>
</template>

<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Trash } from 'lucide-vue-next'

withDefaults(defineProps<{
    disabled?: boolean
}>(), {
    disabled: false
})

</script>
