<script setup lang="ts">
import { Input } from '@/components/ui/input'
import { Search } from 'lucide-vue-next'

const search = defineModel<string>('search');

defineProps({
    placeholder: {
        type: String,
        default: 'Cari...'
    },
});
</script>

<template>
    <div class="relative w-full items-center">
        <Input
            id="search"
            type="search"
            v-model="search"
            :placeholder="placeholder"
            class="pl-8"
        />
        <span class="absolute start-0 inset-y-0 flex items-center justify-center px-2">
      <Search class="size-4 text-muted-foreground" />
    </span>
    </div>
</template>
