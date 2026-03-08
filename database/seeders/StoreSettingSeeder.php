<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StoreSetting;

class StoreSettingSeeder extends Seeder
{
    public function run(): void
    {
        StoreSetting::insert([
            [
                'category' => 'gold',
                'store_name' => 'TOKO EMAS "KARINA"',
                'phone' => '08115166622 / 0811506323',
                'instagram' => '@karina_goldsilverofficial',
                'address' => 'Pusat Perbelanjaan Sentra Antasari No.1A/K 305 Banjarmasin',
                'invoice_color' => '#FFCE1B',
                'header' => 'JUAL BELI RUPA-RUPA PERHIASAN EMAS, TERIMA PESANAN',
                'footer_wholesale' => '',
                'footer_retail' => <<<TXT
                    1. Barang - barang mas kami telah ditimbang dan disaksikan pembeli, harga dan keadaan barang sudah disetujui oleh kedua pihak
                    2. Perhiasan rusak diterima dengan harga lain
                    3. Menjual kembali harus disertai surat tersebut. Berat dan keadaan barang mas sudah diperiksa kembali

                    Kalau barang rusak kami potong ongkos bikin Rp 50.000 – 100.000 / gram, potong mata (Tidak Dihitung)
                 TXT,
            ],
            [
                'category' => 'silver',
                'store_name' => 'TOKO PERAK "KARINA"',
                'phone' => '08115166622 / 0811506323',
                'instagram' => '@karina_goldsilverofficial',
                'address' => 'Pusat Perbelanjaan Sentra Antasari No.1A/K 305 Banjarmasin',
                'invoice_color' => '#F8D0FF',
                'header' => 'JUAL BELI RUPA-RUPA PERHIASAN PERAK, TERIMA PESANAN',
                'footer_wholesale' => 'Dijual seadanya minta halal minta rella , semoga laris manis jualan peraknya.',
                'footer_retail' => <<<TXT
                    1. Jika mau jual kembali nota ini harus dibawa (tidak menerima jual, jika tanpa nota atau nota hilang)
                    2. Apabila merubah tulisan nota ini dianggap tidak berlaku lagi
                    3. Timbangan sudah disaksikan oleh pembeli dan penjual
                    4. Apabila dikembalikan barang rusak/putus, maka harga lebur dibeli Rp 18.000/gr
                    5. Apabila kalung rusak parah/putus maka dibeli harga lebur
                    6. Pemakaian satu tahun bila jual/tukar tambah potong 11.000/gr
                    7. Jual rugi 9.000 tukar tambah potong 7.000, rugi per pcs 20.000
                    8. Pembelian online rugi 12.000/gr
                TXT,
            ]
        ]);
    }
}
