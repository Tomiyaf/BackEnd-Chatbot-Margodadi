<?php

namespace Database\Seeders;

use App\Enums\ServiceDomain;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Administrasi & Kependudukan',
                'slug' => 'administrasi-kependudukan',
                'domain' => ServiceDomain::PUBLIC_SERVICE,
                'icon' => 'badge',
                'description' => 'Layanan pembuatan surat pengantar KTP, KK, Akta Kelahiran/Kematian, dan Surat Keterangan Domisili.',
                'is_active' => true,
            ],
            [
                'name' => 'Bantuan Sosial & Kesejahteraan',
                'slug' => 'bantuan-sosial',
                'domain' => ServiceDomain::PUBLIC_SERVICE,
                'icon' => 'volunteer_activism',
                'description' => 'Layanan fasilitasi SKTM, bansos PKH, BPNT, BLT Dana Desa, Rutilahu, BPJS PBI, dan pendataan DTKS Poskesos.',
                'is_active' => true,
            ],
            [
                'name' => 'Perizinan Usaha & Ekonomi',
                'slug' => 'perizinan-usaha',
                'domain' => ServiceDomain::PUBLIC_SERVICE,
                'icon' => 'storefront',
                'description' => 'Layanan pembuatan Surat Keterangan Usaha (SKU), rekomendasi legalitas usaha mikro, dan permodalan tani/desa.',
                'is_active' => true,
            ],
            [
                'name' => 'Pertanahan & Tata Praja',
                'slug' => 'pertanahan-tata-praja',
                'domain' => ServiceDomain::PUBLIC_SERVICE,
                'icon' => 'gavel',
                'description' => 'Layanan surat pengantar tanah/sporadik, keterangan ahli waris, izin keramaian, dan administrasi kewilayahan.',
                'is_active' => true,
            ],
            [
                'name' => 'Pemberdayaan & Potensi UMKM',
                'slug' => 'potensi-umkm',
                'domain' => ServiceDomain::UMKM,
                'icon' => 'storefront',
                'description' => 'Katalog direktori 71 UMKM di 4 Dusun (Margorejo, Sumberdadi, Margodadi, Bulurejo) Pekon Margodadi.',
                'is_active' => true,
            ],
            [
                'name' => 'Fasilitas & Sarana Pekon',
                'slug' => 'fasilitas-sarana-pekon',
                'domain' => ServiceDomain::PUBLIC_SERVICE,
                'icon' => 'apartment',
                'description' => 'Layanan peminjaman Gedung Serba Guna (GSG), Balai Pekon, dan sarana umum masyarakat.',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $data) {
            ServiceCategory::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
