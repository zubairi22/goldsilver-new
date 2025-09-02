<template>
    <Input
        ref="inputRef"
    />
</template>

<script setup lang="ts">
import { useCurrencyInput } from 'vue-currency-input'
import { Input } from '@/components/ui/input';
import { watch } from 'vue';

const { modelValue = 0 } = defineProps({
    modelValue: Number,
});

const { inputRef, setValue } = useCurrencyInput({
    locale: 'id-ID',
    currency: 'IDR',
    valueRange: {
        "min": 0
    },
})

watch(
    () => modelValue,
    (value) => {
        setValue(value)
    }
)
</script>
