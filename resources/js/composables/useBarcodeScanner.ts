import { ref } from "vue"
import axios from "axios"
import { toast } from "vue-sonner"

export function useBarcodeScanner() {
    const scannerInput = ref<HTMLInputElement | null>(null)
    const scannedCode = ref("")

    const refocusScanner = () => {
        setTimeout(() => {
            scannerInput.value?.focus()
        }, 100)
    }

    const processScan = async (
        onFound: (product: any, code: string) => void,
        onNotFound?: (code: string) => void
    ) => {
        if (!scannedCode.value) return
        const code = scannedCode.value

        try {
            const { data } = await axios.get(`/api/products/search?sku=${code}`)
            if (data.success && data.product) {
                onFound(data.product, code)
                toast.success(`Produk ${data.product.name} ditemukan`, { position: "top-center" })
            } else {
                onNotFound?.(code)
                toast.warning(`Produk dengan SKU: ${code} tidak ditemukan`, { position: "top-center" })
            }
        } catch (e) {
            console.error(e)
            toast.error("Gagal mengambil data produk", { position: "top-center" })
        }

        scannedCode.value = ""
        refocusScanner()
    }

    return {
        scannerInput,
        scannedCode,
        refocusScanner,
        processScan,
    }
}
