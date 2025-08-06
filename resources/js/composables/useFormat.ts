import { format } from 'date-fns'
import { id } from 'date-fns/locale'

export function useFormat() {
    const formatRupiah = (value: number | string): string => {
        const number = typeof value === 'string' ? parseFloat(value) : value;

        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(number);
    };

    function formatDate(dateStr: string) {
        return format(new Date(dateStr), 'dd MMMM yyyy HH:mm', { locale: id });
    }

    return { formatRupiah, formatDate };
}
