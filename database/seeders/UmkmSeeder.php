<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use App\Models\Umkm;
use App\Models\UmkmProduct;
use Illuminate\Database\Seeder;

class UmkmSeeder extends Seeder
{
    public function run(): void
    {
        $umkmCat = ServiceCategory::where('slug', 'potensi-umkm')->first();

        $umkms = [
            [
                'reg_number' => 'MKD-UMKM-001',
                'name' => 'Kopi Robusta Lereng Margodadi',
                'sub_title' => 'Usaha Pengolahan Kopi Rakyat Berkelanjutan Sejak 2017',
                'owner_name' => 'Bapak Supardi',
                'phone' => '0812-7890-1234',
                'wa_number' => '6281278901234',
                'address' => 'Dusun 02 RT 04, Pekon Margodadi',
                'description' => 'Biji kopi robusta petik merah asli lereng perkebunan Margodadi, diproses natural dan honey process dengan aroma cokelat karamel khas pegunungan Tanggamus.',
                'history' => 'Berawal dari tradisi turun-temurun mengelola kebun kopi seluas 2,5 hektar di lereng perbukitan Tanggamus, Bapak Supardi merintis Kopi Robusta Lereng Margodadi guna memutus ketergantungan tengkulak mentah. Dengan menerapkan sistem petik merah selektif 100%, biji kopi dijemur di atas raised bed berventilasi untuk menghasilkan rasa manis alami (honey note) yang tebal dan aftertaste cokelat murni tanpa cacat rasa.',
                'banner_image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDvgNR0og9Y-3RyovTKv9qZJqGfZ3iHvvTo4DycV3xkmNfOdqokFqco3tSlqfRtExPG2Lq30TEYCe0jy3AEE8HzMgw-E2z-Pso3Q2fn4Wy_WNurNc3r5CCpVfYa5v_ypkYE3oPq3gOaizZfVS8AVgabm3zoZyDDgfJX-xC2wI771ciBw3JBxhG4fz_s5uJgb0-FJjoKF1l_xDYdj2QfkBp1_arMGQYRZgROBMK0vpHh4AyL8ITsVLE',
                'gallery_urls' => [
                    'https://lh3.googleusercontent.com/aida-public/AB6AXuB9WqiAEU2zS254PJ3sD6IFlayETpaX1n7QRhEgYbK0-ZkYl_zZJPS3r30nXee8I7bogwxUpQwDmGb7Bi3M4K8vhR-wpkdnuph9BVCedZJmD7BfKRKDRQi_C5lj8V1FL9GAqIYtpwF3njqT9yMo2yygizaQYvMYjgbhSdalGQo42pNAAXUUbAFXOdErGUqL0VkQy6fNP4baL2Q7RwmG0MUpPLDYWXFPk_ErxFfFfcxEMdtZ-YvFoVk',
                    'https://lh3.googleusercontent.com/aida-public/AB6AXuDhdw3VbnsfX2tjyMXCpDPiUJOzz8-sMCp8Tlg_Lvu9f2J5giipVSdsln2mnejpj-VKZmOv-4tuXxpm0Hy-ToohNvNHHo0AmF8Xj9egnae1eBmYuC6-WmVdJaXTYrq5Uua3afIWO16sZzU21AK8NS7VIGBoQm6cUp9HSRJCDeVt2UIYlGX3WGhlcZL9TpK-WZG4U8FeteSYmLfXc2TH2kEnPYUC6Ywx85XnZn30KBwIqvgSrXAaNhs',
                ],
                'legal_certification' => 'P-IRT Dinas Kesehatan',
                'legal_number' => 'No. 2101806010042-26',
                'production_capacity' => '400 kg/bln',
                'capacity_note' => 'Petik Merah Optimal',
                'group_name' => 'Gapoktan Pekon',
                'group_location' => 'Sumberejo, Tanggamus',
                'map_title' => 'Rumah Produksi Kopi Pak Supardi',
                'map_address' => 'Dusun 02 RT 04, Margodadi',
                'map_url' => 'https://maps.google.com/?q=Margodadi+Sumberejo+Tanggamus',
                'last_verified_at' => now()->subDays(15),
                'is_active' => true,
                'products' => [
                    ['name' => 'Kopi Bubuk 250gr', 'price' => 35000, 'description' => 'Bubuk halus aroma cokelat'],
                    ['name' => 'Roasted Beans 500gr', 'price' => 65000, 'description' => 'Biji sangrai medium roast'],
                    ['name' => 'Green Bean Grade 1 1kg', 'price' => 85000, 'description' => 'Biji mentah pilihan'],
                ],
            ],
            [
                'reg_number' => 'MKD-UMKM-002',
                'name' => 'Anyaman Bambu Lestari',
                'sub_title' => 'Sentra Kerajinan Ramah Lingkungan & Pemberdayaan Perempuan',
                'owner_name' => 'Ibu Sri Utami',
                'phone' => '0853-2211-4321',
                'wa_number' => '6285322114321',
                'address' => 'Dusun 01 RT 02, Pekon Margodadi',
                'description' => 'Produk kerajinan tangan ramah lingkungan dari bambu apus lokal. Halus, tahan rayap, dan cocok untuk cinderamata hajatan serta suvenir khas desa.',
                'history' => 'Kelompok pengrajin anyaman bambu yang dibina untuk memberdayakan ibu-ibu rumah tangga Dusun 01 Pekon Margodadi. Memanfaatkan rumpun bambu apus lokal dengan proses pengawetan tradisional asap alami tanpa bahan kimia berbahaya sehingga ramah lingkungan dan tahan lama puluhan tahun.',
                'banner_image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuD68MzkIwpsjk6UuMIY8PZXKmHatTFVp0ws1f2qKgYeWTmmkQfIJFf1ZiPoBTlr0yW5cphvKztaMsTdz4md7CpMZW0znmCOLranNCnsjDe7VNyyD73sbjw72IPRb-_Qypu6H0Le3kVIJZzbnbb_AeUFkPoY4hs058jvfHQrehfQ7t4-g4cP_53VLF1qOzIPrcFugYvnwglZ4nLtv2Ukb5OPPXyHPV5Fsu83ISw0dKwv-MbhzAL97Xw',
                'gallery_urls' => [
                    'https://lh3.googleusercontent.com/aida-public/AB6AXuCc4ChZXPKMb8wcwRXMZYqTwUvOqunj4WKT87DmofCm8iluO7NtLme6d4UN4pIrm0C4ut25hfreJflFdO-BsQyrZjHsMgwInwqxkTzCt4nGVJKBB3WtV4IBPyXIF17rPAIp1Y_9W1teYjlr_Gw21WObIrxF-3na-R-OAX058ITD_6SprbUQL9swoqgp3WbczTVBiJrgEWKXOak5VCcuCItLAFJEtY3WgPyhS-5helc3SBewxC6dKiA',
                ],
                'legal_certification' => 'SKU & NIB Terdaftar',
                'legal_number' => 'No. NIB-0819230018821',
                'production_capacity' => '300 pcs/bln',
                'capacity_note' => 'Anyaman Halus Handmade',
                'group_name' => 'KWT Anyam Lestari',
                'group_location' => 'Dusun 01, Margodadi',
                'map_title' => 'Workshop Kriya Anyaman Bambu Lestari',
                'map_address' => 'Dusun 01 RT 02, Margodadi',
                'map_url' => 'https://maps.google.com/?q=Margodadi+Sumberejo+Tanggamus',
                'last_verified_at' => now()->subDays(12),
                'is_active' => true,
                'products' => [
                    ['name' => 'Besek Bambu', 'price' => 8000, 'description' => 'Wadah berkatan ramah lingkungan'],
                    ['name' => 'Tudung Saji Etnik', 'price' => 45000, 'description' => 'Penutup makanan tradisional'],
                    ['name' => 'Tas Anyam', 'price' => 30000, 'description' => 'Tas jinjing etnik'],
                ],
            ],
            [
                'reg_number' => 'MKD-UMKM-003',
                'name' => 'Keripik Pisang Tanduk "Barokah Rasa"',
                'sub_title' => 'Oleh-oleh Gurih & Manis Renyah Khas Perkebunan Margodadi',
                'owner_name' => 'Ibu Siti Rahayu',
                'phone' => '0821-9988-7766',
                'wa_number' => '6282199887766',
                'address' => 'Dusun 03 RT 06, Pekon Margodadi',
                'description' => 'Oleh-oleh khas Lampung dari pisang tanduk kebun petani binaan. Renyah tanpa pengawet dengan baluran cokelat pekat dan racikan bumbu gurih.',
                'history' => 'Mengolah komoditas pisang tanduk lokal yang melimpah di kebun pekon menjadi aneka camilan bernilai tambah tinggi. Melalui proses penggorengan minyak kelapa berkualitas dan pengeringan sentrifugal (spinner), keripik tetap renyah hingga 6 bulan tanpa bahan kimia pengawet.',
                'banner_image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDLin53tuR6zxbtcY2W4MKlK2qiI0WDTU5ZklX2fiDsWm6VwgSn4Lm1NVt3BGTmbO9fgt6vytiFvng4SM4v44fcH4Yw0sgXzcYoLXfDw0O-rzoaCYBCFFqLq2Y_hLklYUvbwLe43oG3anKbbiA7ganKdP4H_LugqUoxRyrsfrFGcBLkwTmiXt5tNXyNAzIVeurULL2z7ziHq08bw8ucURqZdRbRFRT6XKLmEyBU4xpFYMxuiQT_PMg',
                'gallery_urls' => [
                    'https://lh3.googleusercontent.com/aida-public/AB6AXuDTQhtOTsN8KebCjuFXt0TbyojwdrzZBMXeIZU0UXc6puQdNaPdXKfp0QQcc6uMJ6lQvpPqHWQyYk1VNn2vS2L7CPuDMwxj3EPj02bbXQuO4DwYqd6-qiuW5y89QmDSRdr1eI54w9bnsj0jR0MO9HKU_4OlfjO2rJ94Bg5O_0sNGrwtLx6fuFxMshshiP-febbV1MdnewvF4atJI3ebJE0aPFIJZL6ca6L-iMvtHsQfbGc_SWuQIok',
                ],
                'legal_certification' => 'P-IRT & Halal Kemenag',
                'legal_number' => 'ID18110002981023',
                'production_capacity' => '600 pouch/bln',
                'capacity_note' => 'Kemasan Zipper Stand Pouch',
                'group_name' => 'Koperasi Warga Margodadi',
                'group_location' => 'Dusun 03, Margodadi',
                'map_title' => 'Dapur Produksi Barokah Rasa',
                'map_address' => 'Dusun 03 RT 06, Margodadi',
                'map_url' => 'https://maps.google.com/?q=Margodadi+Sumberejo+Tanggamus',
                'last_verified_at' => now()->subDays(8),
                'is_active' => true,
                'products' => [
                    ['name' => 'Cokelat Lumer 200g', 'price' => 18000, 'description' => 'Baluran coklat pekat'],
                    ['name' => 'Manis Gurih 200g', 'price' => 15000, 'description' => 'Rasa klasik pisang tanduk'],
                    ['name' => 'Balado Pedas Manis 200g', 'price' => 16000, 'description' => 'Pedas manis gurih'],
                ],
            ],
            [
                'reg_number' => 'MKD-UMKM-004',
                'name' => 'Madu Alami Hutan "Sari Lebah"',
                'sub_title' => 'Madu Hutan Liar Murni & Klanceng Trigona Bebas Campuran',
                'owner_name' => 'Kang Asep Sunandar',
                'phone' => '0813-4455-8899',
                'wa_number' => '6281344558899',
                'address' => 'Dusun 04 RT 08, Pekon Margodadi',
                'description' => 'Madu murni lebah liar (Apis dorsata) dan klanceng dari vegetasi pohon perkebunan Margodadi. Alami tanpa pasteurisasi dan kaya enzim alami.',
                'history' => 'Budidaya lebah klanceng (Trigona sp.) dan pemanenan lestari madu lebah liar lereng pekon dengan memperhatikan siklus nektar bunga kopi dan randu. Menjamin kadar air alami di bawah 20% tanpa proses pemanasan kimiawi sehingga kandungan propolis dan antioksidan tetap utuh.',
                'banner_image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDTQhtOTsN8KebCjuFXt0TbyojwdrzZBMXeIZU0UXc6puQdNaPdXKfp0QQcc6uMJ6lQvpPqHWQyYk1VNn2vS2L7CPuDMwxj3EPj02bbXQuO4DwYqd6-qiuW5y89QmDSRdr1eI54w9bnsj0jR0MO9HKU_4OlfjO2rJ94Bg5O_0sNGrwtLx6fuFxMshshiP-febbV1MdnewvF4atJI3ebJE0aPFIJZL6ca6L-iMvtHsQfbGc_SWuQIok',
                'gallery_urls' => [
                    'https://lh3.googleusercontent.com/aida-public/AB6AXuB0XhG5qjdBzpJJ9CswTyzm_0oB7kgIS6ISuG58SQDZXdhqSJXwH3juSybJKg60yUnEyoPJb_P_E4zyxGyyZXFOHHiaC_TPSr3Q-u-xNYLwP0ed4qrvIjvF6l-PVqbs6hM3-GSeux1SmKRRekoc-VYAI_pGzwcFVuABbEIk6XARYnFDbSjIT6s7F3e9k6xTS3a6ezJqPiGUWeMAcwJW_N4Y1s04GOnZ4KcIbLGaZHD_4h9-JCq3dXg',
                ],
                'legal_certification' => 'Uji Lab & NIB Pertanian',
                'legal_number' => 'No. NIB-1102948192837',
                'production_capacity' => '150 botol/bln',
                'capacity_note' => 'Panen Alami Bersiklus',
                'group_name' => 'Komunitas Peternak Lebah Pekon',
                'group_location' => 'Dusun 04, Margodadi',
                'map_title' => 'Peternakan & Koloni Lebah Sari Lebah',
                'map_address' => 'Dusun 04 RT 08, Margodadi',
                'map_url' => 'https://maps.google.com/?q=Margodadi+Sumberejo+Tanggamus',
                'last_verified_at' => now()->subDays(6),
                'is_active' => true,
                'products' => [
                    ['name' => 'Madu Hutan 350ml', 'price' => 85000, 'description' => 'Madu liar murni'],
                    ['name' => 'Madu Klanceng 250ml', 'price' => 95000, 'description' => 'Madu asam manis trigona'],
                    ['name' => 'Sarang Madu Sisir 250g', 'price' => 70000, 'description' => 'Sarang madu segar'],
                ],
            ],
            [
                'reg_number' => 'MKD-UMKM-005',
                'name' => 'Batik Tulis Kopi & Lada',
                'sub_title' => 'Kain Etnik Kontemporer Pewarna Alami & Corak Agrikultur',
                'owner_name' => 'Paguyuban Putri Margodadi',
                'phone' => '0852-7311-6655',
                'wa_number' => '6285273116655',
                'address' => 'Balai Kreatif Dusun 01, Pekon Margodadi',
                'description' => 'Kain batik tulis kontemporer dengan motif khas kekayaan bumi Sumberejo seperti biji kopi dan tangkai lada hitam berpadu ornamen tapis tradisional.',
                'history' => 'Inisiatif pemberdayaan sanggar kreasi perempuan pekon Margodadi untuk mengangkat identitas agraris setempat melalui seni canting batik. Pewarnaan menggunakan ekstrak kulit pohon mahoni, daun mangga, dan serbuk limbah kopi pekon yang ramah alam.',
                'banner_image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCc4ChZXPKMb8wcwRXMZYqTwUvOqunj4WKT87DmofCm8iluO7NtLme6d4UN4pIrm0C4ut25hfreJflFdO-BsQyrZjHsMgwInwqxkTzCt4nGVJKBB3WtV4IBPyXIF17rPAIp1Y_9W1teYjlr_Gw21WObIrxF-3na-R-OAX058ITD_6SprbUQL9swoqgp3WbczTVBiJrgEWKXOak5VCcuCItLAFJEtY3WgPyhS-5helc3SBewxC6dKiA',
                'gallery_urls' => [
                    'https://lh3.googleusercontent.com/aida-public/AB6AXuD68MzkIwpsjk6UuMIY8PZXKmHatTFVp0ws1f2qKgYeWTmmkQfIJFf1ZiPoBTlr0yW5cphvKztaMsTdz4md7CpMZW0znmCOLranNCnsjDe7VNyyD73sbjw72IPRb-_Qypu6H0Le3kVIJZzbnbb_AeUFkPoY4hs058jvfHQrehfQ7t4-g4cP_53VLF1qOzIPrcFugYvnwglZ4nLtv2Ukb5OPPXyHPV5Fsu83ISw0dKwv-MbhzAL97Xw',
                ],
                'legal_certification' => 'Hak Cipta Motif Kemenkumham',
                'legal_number' => 'No. HKI-EC00202419082',
                'production_capacity' => '80 lembar/bln',
                'capacity_note' => 'Batik Tulis & Cap Eksklusif',
                'group_name' => 'Paguyuban Kreatif Putri Pekon',
                'group_location' => 'Dusun 01, Margodadi',
                'map_title' => 'Balai Sanggar Batik Paguyuban Putri',
                'map_address' => 'Balai Kreatif Dusun 01, Margodadi',
                'map_url' => 'https://maps.google.com/?q=Margodadi+Sumberejo+Tanggamus',
                'last_verified_at' => now()->subDays(4),
                'is_active' => true,
                'products' => [
                    ['name' => 'Kain Panjang 2.2m', 'price' => 175000, 'description' => 'Kain primissima halus'],
                    ['name' => 'Syal Batik Sutra', 'price' => 65000, 'description' => 'Aksesoris etnik sutra'],
                    ['name' => 'Kemeja Batik Pria Siap Pakai', 'price' => 220000, 'description' => 'Jahitan semi tailor'],
                ],
            ],
            [
                'reg_number' => 'MKD-UMKM-006',
                'name' => 'Bibit Buah Unggul "Tani Makmur"',
                'sub_title' => 'Nursery Okulasi Bibit Alpukat Aligator & Durian Unggulan',
                'owner_name' => 'Pak Joko Prayitno',
                'phone' => '0823-1122-3344',
                'wa_number' => '6282311223344',
                'address' => 'Jalur Kebun Induk RT 05, Pekon Margodadi',
                'description' => 'Pusat pembibitan vegetatif hasil okulasi bersertifikat. Menyediakan bibit alpukat aligator, durian bawor, dan mangga berbuah lebat cocok tanah lokal.',
                'history' => 'Pengembangan nursery tanaman buah tropis dengan teknik sambung pucuk dan okulasi mata tunas indukan unggul teruji. Memberikan garansi keaslian varietas dan konsultasi gratis pemupukan organik bagi para pekebun lokal maupun pehobi tanaman.',
                'banner_image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuB0XhG5qjdBzpJJ9CswTyzm_0oB7kgIS6ISuG58SQDZXdhqSJXwH3juSybJKg60yUnEyoPJb_P_E4zyxGyyZXFOHHiaC_TPSr3Q-u-xNYLwP0ed4qrvIjvF6l-PVqbs6hM3-GSeux1SmKRRekoc-VYAI_pGzwcFVuABbEIk6XARYnFDbSjIT6s7F3e9k6xTS3a6ezJqPiGUWeMAcwJW_N4Y1s04GOnZ4KcIbLGaZHD_4h9-JCq3dXg',
                'gallery_urls' => [
                    'https://lh3.googleusercontent.com/aida-public/AB6AXuDvgNR0og9Y-3RyovTKv9qZJqGfZ3iHvvTo4DycV3xkmNfOdqokFqco3tSlqfRtExPG2Lq30TEYCe0jy3AEE8HzMgw-E2z-Pso3Q2fn4Wy_WNurNc3r5CCpVfYa5v_ypkYE3oPq3gOaizZfVS8AVgabm3zoZyDDgfJX-xC2wI771ciBw3JBxhG4fz_s5uJgb0-FJjoKF1l_xDYdj2QfkBp1_arMGQYRZgROBMK0vpHh4AyL8ITsVLE',
                ],
                'legal_certification' => 'Sertifikasi BPSB Tanaman Pangan',
                'legal_number' => 'No. BPSB-TPH/18/2024',
                'production_capacity' => '2.500 bibit/bln',
                'capacity_note' => 'Polybag Siap Tanam',
                'group_name' => 'Kelompok Tani Makmur Margodadi',
                'group_location' => 'Kebun Induk RT 05, Margodadi',
                'map_title' => 'Nursery Kebun Bibit Tani Makmur',
                'map_address' => 'Jalur Kebun Induk RT 05, Margodadi',
                'map_url' => 'https://maps.google.com/?q=Margodadi+Sumberejo+Tanggamus',
                'last_verified_at' => now()->subDays(2),
                'is_active' => true,
                'products' => [
                    ['name' => 'Bibit Alpukat Aligator', 'price' => 35000, 'description' => 'Tinggi 60-80cm sambung pucuk'],
                    ['name' => 'Durian Bawor Okulasi', 'price' => 60000, 'description' => 'Cepat berbuah 3-4 tahun'],
                    ['name' => 'Bibit Mangga Kiojay', 'price' => 40000, 'description' => 'Mangga jumbo manis'],
                ],
            ],
            [
                'reg_number' => 'MKD-UMKM-007',
                'name' => 'Bengkel Las & Mesin Pertanian "Karya Mandiri"',
                'sub_title' => 'Jasa Fabrikasi Alat Perkebunan, Pompa & Konstruksi Besi',
                'owner_name' => 'Mas Budi Santoso',
                'phone' => '0812-6543-9876',
                'wa_number' => '6281265439876',
                'address' => 'Dusun 02 RT 03, Pekon Margodadi',
                'description' => 'Jasa perbaikan dan perakitan mesin pertanian (traktor, mesin perontok padi, pompa air) serta pengerjaan kanopi dan pagar besi berkualitas kokoh.',
                'history' => 'Menyediakan jasa teknik terpadu untuk mendukung produktivitas para petani Pekon Margodadi. Memiliki peralatan las argon dan suku cadang mesin pertanian lengkap dengan jaminan pengerjaan rapi dan cepat.',
                'banner_image_url' => 'https://images.unsplash.com/photo-1504917599217-d4dc5ebe6122?auto=format&fit=crop&w=800&q=80',
                'gallery_urls' => [
                    'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=800&q=80',
                ],
                'legal_certification' => 'Surat Izin Usaha Perdagangan & Jasa',
                'legal_number' => 'No. SIUP-1982039120',
                'production_capacity' => '30 proyek/bln',
                'capacity_note' => 'Panggilan Lapangan & Workshop',
                'group_name' => 'Asosiasi Usaha Bengkel Pekon',
                'group_location' => 'Dusun 02, Margodadi',
                'map_title' => 'Bengkel Teknik Karya Mandiri',
                'map_address' => 'Dusun 02 RT 03, Margodadi',
                'map_url' => 'https://maps.google.com/?q=Margodadi+Sumberejo+Tanggamus',
                'last_verified_at' => now()->subDay(),
                'is_active' => true,
                'products' => [
                    ['name' => 'Servis Mesin Pompa & Traktor', 'price' => 50000, 'description' => 'Tune up & perbaikan'],
                    ['name' => 'Kanopi & Pagar Minimalis', 'price' => 275000, 'description' => 'Harga per meter persegi'],
                    ['name' => 'Gerobak Angkut Sawit/Kopi', 'price' => 650000, 'description' => 'Besi galvanis kokoh'],
                ],
            ],
        ];

        foreach ($umkms as $item) {
            $products = $item['products'];
            unset($item['products']);

            $item['category_id'] = $umkmCat?->category_id;

            $umkm = Umkm::updateOrCreate(
                ['reg_number' => $item['reg_number']],
                $item
            );

            foreach ($products as $prod) {
                UmkmProduct::updateOrCreate(
                    [
                        'umkm_id' => $umkm->umkm_id,
                        'name' => $prod['name'],
                    ],
                    [
                        'price' => $prod['price'],
                        'description' => $prod['description'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
