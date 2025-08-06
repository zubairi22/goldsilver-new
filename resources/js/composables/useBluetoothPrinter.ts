import { ref } from 'vue';

export function useBluetoothPrinter() {
    const characteristic = ref<BluetoothRemoteGATTCharacteristic | null>(null);

    const connectPrinter = async (): Promise<boolean> => {
        try {
            const device = await navigator.bluetooth.requestDevice({
                acceptAllDevices: true,
                optionalServices: ['000018f0-0000-1000-8000-00805f9b34fb'],
            });

            const server = await device.gatt?.connect();
            if (!server) console.error('Bluetooth GATT server not available');

            const service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');
            characteristic.value = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');

            return true;
        } catch (err) {
            console.error('Printer connection failed:', err);
            return false;
        }
    };

    const printText = async (text: string): Promise<void> => {
        if (!characteristic.value) {
            console.error('Printer not connected');
            return;
        }

        const encoder = new TextEncoder();
        const lines = text.split('\n');

        for (const line of lines) {
            const data = encoder.encode(line + '\n');
            await characteristic.value.writeValue(data);
        }

        await characteristic.value.writeValue(encoder.encode('\n\n\n'));
    };

    return {
        connectPrinter,
        printText,
        isConnected: characteristic,
    };
}
