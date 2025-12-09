<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StoreSetting;

class StoreSettingSeeder extends Seeder
{
    public function run(): void
    {
        StoreSetting::updateOrCreate(
            ['id' => 1],
            [
                'store_name'   => 'Karina Gold Silver',
                'phone'        => '08115166622 / 0811506323',
                'instagram'    => '@karina_goldsilverofficial',
                'address'      => 'Pusat Perbelanjaan Sentra Antasari No.1A/K 305 Banjarmasin',

                'gold_invoice_color'   => '#FFCE1B',
                'silver_invoice_color' => '#F8D0FF',

                // FOOTER GOLD WHOLESALE (kosong sesuai database)
                'footer_gold_wholesale' => '',

                // FOOTER GOLD RETAIL (teks biasa, multiline)
                'footer_gold_retail' => <<<TXT
                    1. Barang - barang mas kami telah ditimbang dan disaksikan pembeli, harga dan keadaan barang sudah disetujui oleh kedua pihak
                    2. Perhiasan rusak diterima dengan harga lain
                    3. Menjual kembali harus disertai surat tersebut. Berat dan keadaan barang mas sudah diperiksa kembali

                    Kalau barang rusak kami potong ongkos bikin Rp 50.000 – 100.000 / gram, potong mata (Tidak Dihitung)
                 TXT,

                // FOOTER SILVER WHOLESALE (satu baris saja)
                'footer_silver_wholesale' => 'Dijual seadanya minta halal minta rella , semoga laris manis jualan peraknya.',

                // FOOTER SILVER RETAIL (multiline)
                'footer_silver_retail' => <<<TXT
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
        );
    }
}
