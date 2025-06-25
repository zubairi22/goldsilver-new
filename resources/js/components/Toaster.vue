<script lang="ts" setup>
import {ref, watchEffect} from 'vue';

const {flash} = defineProps<{
    flash: any
}>()

const show = ref(false);
const status = ref('success');
const message = ref('');

watchEffect(() => {
    status.value = flash?.status ?? 'success';
    message.value = flash?.message;
    show.value = !!message.value;

    if (message.value) {
        setTimeout(() => {
            show.value = false;
        }, 3000);
    }
});
</script>

<template>
    <transition
        enter-active-class="duration-300 ease-out"
        enter-from-class="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
        enter-to-class="translate-y-0 opacity-100 sm:scale-100"
        leave-active-class="duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100 sm:scale-100"
        leave-to-class="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
    >
        <div v-if="show && message" :class="{ 'bg-emerald-600': status === 'success', 'bg-red-700': status === 'error' }"
             class="fixed top-8 right-5 z-50 w-[42svh] sm:w-full max-w-md rounded-lg p-4 shadow" role="alert">
            <div class="flex items-center">
                <span :class="{ 'bg-emerald-700': status === 'success', 'bg-red-600': status === 'error' }"
                      class="flex rounded-lg p-2">
                  <svg v-if="status === 'success'" class="h-5 w-5 text-white" fill="none" stroke="currentColor"
                         stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round"
                          stroke-linejoin="round"/>
                  </svg>
                  <svg v-if="status === 'error'" class="h-5 w-5 text-white" fill="none" stroke="currentColor"
                       stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                        stroke-linecap="round"
                        stroke-linejoin="round"/>
                  </svg>
                </span>
                <p class="ml-3 whitespace-normal text-sm font-medium text-white">
                    {{ message }}
                </p>
                <button
                    :class="{ 'hover:bg-emerald-700 focus:bg-emerald-700': status === 'success', 'hover:bg-red-600 focus:bg-red-600': status === 'error' }"
                    aria-label="Dismiss"
                    class="ml-auto flex rounded-md p-2 transition focus:outline-none"
                    type="button"
                    @click.prevent="show = false">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="1.5"
                         viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round"
                              stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </div>
    </transition>
</template>

