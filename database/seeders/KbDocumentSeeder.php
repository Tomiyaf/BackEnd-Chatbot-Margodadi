<?php

namespace Database\Seeders;

use App\Enums\ServiceDomain;
use App\Models\KbChunk;
use App\Models\KbDocument;
use Illuminate\Database\Seeder;

class KbDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documents = [
            [
                'title' => 'SOP Pelayanan Kependudukan dan Administrasi Pekon Margodadi',
                'domain' => ServiceDomain::PUBLIC_SERVICE,
                'source' => 'Peraturan Desa Margodadi No. 04/2024 tentang Tata Kelola Pelayanan Terpadu',
                'validator' => 'Kasi Pemerintahan',
                'version' => 'v2.1',
                'chunks' => [
                    'Pengurusan Surat Keterangan Usaha (SKU) memerlukan: Surat Pengantar RT/RW, Fotokopi KTP & Kartu Keluarga, dan Foto tempat/aktivitas usaha.',
                    'Pengurusan Surat Keterangan Tidak Mampu (SKTM) memerlukan: Surat Pengantar RT/RW, Fotokopi KTP & KK, terdaftar pada basis data DTKS/P3KE, serta foto kondisi rumah pemohon.',
                    'Layanan perekaman dan perbaikan data KTP Elektronik difasilitasi setiap hari kerja Senin-Jumat pukul 08.00-15.00 WIB di Kantor Pekon Margodadi.',
                ],
            ],
            [
                'title' => 'Panduan Direktori dan Kurasi Produk Unggulan UMKM Pekon Margodadi',
                'domain' => ServiceDomain::UMKM,
                'source' => 'Buku Profil Ekonomi Kreatif dan Potensi Dusun Pekon Margodadi 2026',
                'validator' => 'Kaur Perencanaan',
                'version' => 'v1.4',
                'chunks' => [
                    'Keripik Pisang Mahuli Barokah Dusun 2: Produk olahan pisang lokal dengan rasa original, cokelat, dan balado. Hubungi kontak Bpk. Bambang Sutrisno di 0812-3456-7801.',
                    'Kopi Robusta Margodadi Dusun 3: Kopi petik merah organik proses honey dan fullwash kemasan 250gr. Hubungi Bpk. Sugeng di 0813-9876-5432.',
                    'Madu Klanceng Trigona Dusun 4: Madu murni lebah tanpa sengat kaya antioksidan dan enzim alami. Hubungi Ibu Sumarni di 0811-2233-4455.',
                ],
            ],
            [
                'title' => 'Buku Panduan Edukasi 3R dan Tata Kelola Bank Sampah Berkah Margodadi',
                'domain' => ServiceDomain::WASTE_EDUCATION,
                'source' => 'Buku Pedoman TPS3R dan Bank Sampah Berkah Desa Margodadi 2026',
                'validator' => 'Tim Pengelola Sampah',
                'version' => 'v2.1',
                'chunks' => [
                    'Jadwal operasional penimbangan Bank Sampah Berkah diadakan setiap hari Minggu ke-2 dan ke-4 setiap bulan di Balai Dusun 1 Margodadi.',
                    'Pilah sampah organik (sisa sayur/buah/daun) untuk pupuk kompos/magot BSF, sampah anorganik (kardus, botol plastik PET, kaleng, kaca), dan sampah B3 (baterai, botol pestisida).',
                    'Biopori dan komposter komunal: Warga dapat mengambil bibit aktivator EM4 dan meminjam bor biopori di kantor TPS3R pekon secara gratis.',
                ],
            ],
        ];

        foreach ($documents as $docData) {
            $chunks = $docData['chunks'];
            unset($docData['chunks']);

            $doc = KbDocument::updateOrCreate(
                ['title' => $docData['title']],
                $docData
            );

            foreach ($chunks as $index => $chunkText) {
                KbChunk::firstOrCreate(
                    [
                        'document_id' => $doc->document_id,
                        'chunk_index' => $index,
                    ],
                    [
                        'content' => $chunkText,
                        'metadata' => [
                            'domain' => $doc->domain?->value,
                            'title' => $doc->title,
                            'validator' => $doc->validator,
                        ],
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
