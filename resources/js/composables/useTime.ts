export function useTime() {
    function formatNow(): string {
        const formatter = new Intl.DateTimeFormat('id-ID', {
            timeZone: 'Asia/Makassar',
            day: 'numeric',
            month: 'short',
            year: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        })

        return formatter.format(new Date())
    }

    function today(): string {
        const now = new Date();
        const offsetNow = new Date(
            now.toLocaleString("en-US", { timeZone: "Asia/Makassar" })
        );

        return offsetNow.toISOString().slice(0, 10); // → 2025-02-18
    }

    return { formatNow, today }
}
