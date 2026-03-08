import { onBeforeUnmount, onMounted, ref } from 'vue';

export function useBarcodeScanner(onScan: (code: string) => void) {
    const buffer = ref('');
    let scanTimeout: ReturnType<typeof setTimeout> | null = null;
    let lastKeyTime = 0;

    const handleKeydown = (e: KeyboardEvent) => {
        const target = e.target as HTMLElement;

        if (target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement || target.closest('.multiselect')) {
            return;
        }

        const char = e.key;
        const now = Date.now();
        const diff = now - lastKeyTime;
        lastKeyTime = now;

        if (diff > 100) buffer.value = '';

        if (char.length === 1 && /[a-zA-Z0-9]/.test(char)) {
            buffer.value += char;
        }

        if (char === 'Enter') {
            const code = buffer.value.trim();
            buffer.value = '';
            if (!code) return;

            onScan(code);
        }

        if (scanTimeout) clearTimeout(scanTimeout);
        scanTimeout = setTimeout(() => {
            buffer.value = '';
        }, 800);
    };

    onMounted(() => {
        window.addEventListener('keydown', handleKeydown);
    });

    onBeforeUnmount(() => {
        window.removeEventListener('keydown', handleKeydown);
        if (scanTimeout) clearTimeout(scanTimeout);
    });

    return {
        buffer,
    };
}
