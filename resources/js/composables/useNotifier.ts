import { toast, type ExternalToast } from "vue-sonner"

export function useNotifier() {
    const success = (message: string, description?: string, options?: ExternalToast) => {
        toast.success(message, {
            description,
            ...options,
        })
    }

    const error = (message: string, description?: string, options?: ExternalToast) => {
        toast.error(message, {
            description,
            ...options,
        })
    }

    const warning = (message: string, description?: string, options?: ExternalToast) => {
        toast.warning(message, {
            description,
            ...options,
        })
    }

    const info = (message: string, description?: string, options?: ExternalToast) => {
        toast(message, {
            description,
            ...options,
        })
    }

    return { success, error, warning, info }
}
