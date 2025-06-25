<script setup lang="ts">
import { ref, watch } from 'vue';
import { Input } from '@/components/ui/input'
import { Search } from 'lucide-vue-next'

const model = ref('');

const emit = defineEmits<{
    (e: 'update:modelValue', payload: string | number): void
}>()

watch(model, (newValue) => {
    emit('update:modelValue', newValue);
});
</script>

<template>
    <div class="relative w-full items-center">
        <Input
            id="search"
            type="text"
            v-model="model"
            placeholder="Cari..."
            class="pl-8"
        />
        <span class="absolute start-0 inset-y-0 flex items-center justify-center px-2">
      <Search class="size-4 text-muted-foreground" />
    </span>
    </div>
</template>
