<template>
    <button
        class="inline-flex items-center rounded-md border border-transparent bg-gray-600 p-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gray-500 active:bg-gray-700"
        @click="toggle"
    >
        <icon name="ChevronRight" :class="{'rotate-90': isOpen}" class="w-5 h-5 transition-transform duration-300"/>
    </button>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';

defineProps({
    isOpen: Boolean,
});

const emit = defineEmits(["toggle"]);

const toggle = () => {
    emit("toggle");
};
</script>
