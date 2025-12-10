<script setup lang="ts">
import { ref, watch } from "vue";
import ImageModal from '@/components/ImageModal.vue';
import { Input } from '@/components/ui/input';

const props = defineProps({
    modelValue: File || null,
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue"]);

const preview = ref<string | null>(null);

// Update preview jika file berubah
watch(
    () => props.modelValue,
    (file) => {
        if (!file) {
            preview.value = null;
            return;
        }
        preview.value = URL.createObjectURL(file);
    },
    { immediate: true }
);

const handleFile = (e: Event) => {
    const input = e.target as HTMLInputElement;
    if (!input.files || !input.files[0]) return;

    const file = input.files[0];

    preview.value = URL.createObjectURL(file);
    emit("update:modelValue", file);
};
</script>

<template>
    <div class="space-y-2">
        <Input
            :disabled="disabled"
            type="file"
            accept="image/*"
            capture="environment"
            @change="handleFile"
            class="file:pr-3 file:pl-1"
        />

        <div v-if="preview" class="flex justify-center">
            <ImageModal :src="preview" trigger/>
        </div>
    </div>
</template>
