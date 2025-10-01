import { ref, onMounted, onBeforeUnmount } from "vue"
import axios from "axios"
import { toast } from "vue-sonner"

type ScanHandler = (product: any, code: string) => void

export function useBarcodeScanner() {
    const buffer = ref("")
    let scanTimeout: ReturnType<typeof setTimeout> | null = null
    let lastKeyTime = 0

    const initScan = (
        onFound: ScanHandler,
        onNotFound?: (code: string) => void
    ) => {
        const fetchProduct = async (code: string) => {
            try {
                const { data } = await axios.get(`/api/products/search?sku=${code}`)
                if (data.success && data.product) {
                    onFound(data.product, code)
                    toast.success(`Produk ${data.product.name} ditemukan`, { position: "top-center" })
                } else {
                    onNotFound?.(code)
                    toast.warning(`Produk dengan SKU: ${code} tidak ditemukan`, { position: "top-center" })
                }
            } catch (err) {
                console.error(err)
                toast.error("Gagal mengambil data produk", { position: "top-center" })
            }
        }

        const handleKeydown = (e: KeyboardEvent) => {
            const target = e.target as HTMLElement

            if (
                target instanceof HTMLInputElement ||
                target instanceof HTMLTextAreaElement ||
                target.closest(".multiselect")
            ) {
                return
            }

            const char = e.key
            const now = Date.now()
            const diff = now - lastKeyTime
            lastKeyTime = now

            if (diff > 100) buffer.value = ""

            if (char.length === 1 && /[a-zA-Z0-9]/.test(char)) {
                buffer.value += char
            }

            if (char === "Enter") {
                const code = buffer.value.trim()
                buffer.value = ""
                if (!code) return
                void fetchProduct(code)
            }

            if (scanTimeout) clearTimeout(scanTimeout)
            scanTimeout = setTimeout(() => {
                buffer.value = ""
            }, 800)
        }

        onMounted(() => {
            window.addEventListener("keydown", handleKeydown)
        })

        onBeforeUnmount(() => {
            window.removeEventListener("keydown", handleKeydown)
            if (scanTimeout) clearTimeout(scanTimeout)
        })
    }

    return {
        initScan,
        buffer,
    }
}
