<script setup lang="ts">
import ImageModal from '@/components/ImageModal.vue';
import { Input } from '@/components/ui/input';
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: File || null,
    compress: { type: Boolean, default: true },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const preview = ref<string | null>(null);

watch(
    () => props.modelValue,
    (file) => {
        
        if (!file) {
            preview.value = null;
            return;
        }
        if (file instanceof File) {
            preview.value = URL.createObjectURL(file);
            return;
        }

        if (typeof file === 'string') {
            preview.value = file;
            return;
        }

        preview.value = null;
    },
    { immediate: true },
);

const compressImage = (file: File): Promise<File> => {
    return new Promise((resolve) => {
        const img = new Image();
        const reader = new FileReader();

        reader.onload = (e) => {
            img.src = e.target?.result as string;
        };

        img.onload = () => {
            const canvas = document.createElement('canvas');

            const maxWidth = 1280;
            let width = img.width;
            let height = img.height;

            if (width > maxWidth) {
                const scale = maxWidth / width;
                width = maxWidth;
                height = height * scale;
            }

            canvas.width = width;
            canvas.height = height;

            const ctx = canvas.getContext('2d');
            ctx?.drawImage(img, 0, 0, width, height);

            canvas.toBlob(
                (blob) => {
                    if (!blob) return;

                    const compressedFile = new File([blob], file.name.replace(/\.[^/.]+$/, '') + '.jpg', { type: 'image/jpeg' });

                    resolve(compressedFile);
                },
                'image/jpeg',
                0.7,
            );
        };

        reader.readAsDataURL(file);
    });
};

const handleFile = async (e: Event) => {
    const input = e.target as HTMLInputElement;
    if (!input.files || !input.files[0]) return;

    const file = input.files[0];

    try {
        const compressed = props.compress ? await compressImage(file) : file;

        preview.value = URL.createObjectURL(compressed);
        emit('update:modelValue', compressed);
    } catch (err) {
        console.error('Gagal compress gambar:', err);

        preview.value = URL.createObjectURL(file);
        emit('update:modelValue', file);
    }
};
</script>

<template>
    <div class="space-y-2">
        <Input :disabled="disabled" type="file" accept="image/*" capture="environment" @change="handleFile" class="file:pr-3 file:pl-1" />

        <div v-if="preview" class="flex justify-center">
            <ImageModal :src="preview" trigger />
        </div>
    </div>
</template>
