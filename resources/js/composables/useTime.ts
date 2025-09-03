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

    return { formatNow }
}
