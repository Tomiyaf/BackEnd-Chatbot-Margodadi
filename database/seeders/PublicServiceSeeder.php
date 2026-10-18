<?php

namespace Database\Seeders;

use App\Models\PublicService;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class PublicServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catAdminduk = ServiceCategory::where('slug', 'administrasi-kependudukan')->first();
        $catBansos = ServiceCategory::where('slug', 'bantuan-sosial')->first();
        $catUsaha = ServiceCategory::where('slug', 'perizinan-usaha')->first();
        $catPertanahan = ServiceCategory::where('slug', 'pertanahan-tata-praja')->first();
        $catFasilitas = ServiceCategory::where('slug', 'fasilitas-sarana-pekon')->first();

        $services = [
            // 1. Surat Keterangan Usaha (SKU)
            [
                'category_id' => $catUsaha?->category_id,
                'title' => 'Surat Keterangan Usaha (SKU)',
                'slug' => 'surat-keterangan-usaha',
                'category_badge' => 'Perizinan Usaha',
                'sub_category_badge' => 'Legalitas Usaha',
                'description' => 'Layanan penerbitan Surat Keterangan Usaha bagi warga atau pelaku UMKM Pekon Margodadi untuk keperluan perbankan, pengajuan KUR, izin usaha, atau administrasi legalitas.',
                'legal_basis' => 'Peraturan Desa Margodadi tentang Pemberdayaan Ekonomi & UU Desa No. 6 Tahun 2014',
                'requirements' => [
                    'Surat Pengantar dari Ketua RT/Kadus setempat',
                    'Fotokopi KTP Elektronik pemohon yang masih berlaku',
                    'Fotokopi Kartu Keluarga (KK)',
                    'Foto tempat / aktivitas usaha yang sedang berjalan',
                ],
                'steps' => [
                    '1. Pemohon meminta surat pengantar dari Ketua RT di lingkungan dusun tempat tinggal.',
                    '2. Datang ke Kantor Balai Pekon Margodadi membawa seluruh berkas persyaratan.',
                    '3. Menyerahkan berkas ke Kasi Pemerintahan (Bpk. Didi Gunari) atau petugas loket pelayanan.',
                    '4. Petugas memverifikasi kelengkapan dan mencetak draft SKU.',
                    '5. Penandatanganan SKU oleh Kepala Pekon (Bpk. Didi Handoko) atau Sekretaris Pekon.',
                    '6. Surat diserahkan kepada pemohon (tanpa dipungut biaya apapun).',
                ],
                'cost_info' => 'Gratis (Rp 0)',
                'sla_duration' => '15 - 30 Menit (Bisa selesai hari yang sama)',
                'officer_in_charge' => 'Kasi Pemerintahan Pekon Margodadi (Didi Gunari)',
                'tags' => ['sku', 'usaha', 'umkm', 'bank', 'kur'],
                'is_active' => true,
            ],

            // 2. Surat Keterangan Tidak Mampu (SKTM)
            [
                'category_id' => $catBansos?->category_id,
                'title' => 'Surat Keterangan Tidak Mampu (SKTM)',
                'slug' => 'surat-keterangan-tidak-mampu',
                'category_badge' => 'Bantuan Sosial',
                'sub_category_badge' => 'Kesejahteraan Sosial',
                'description' => 'Layanan penerbitan SKTM untuk keperluan keringanan biaya pendidikan/sekolah, beasiswa kuliah, permohonan bantuan kesehatan/BPJS PBI, atau pengajuan bantuan sosial.',
                'legal_basis' => 'Permensos RI tentang DTKS & Standar Pelayanan Minimal Sosial',
                'requirements' => [
                    'Surat Pengantar dari Ketua RT/RW setempat',
                    'Fotokopi KTP Elektronik kepala keluarga & pemohon',
                    'Fotokopi Kartu Keluarga (KK)',
                    'Surat pernyataan tidak mampu bermaterai (jika belum masuk basis data DTKS)',
                    'Foto kondisi rumah tampak depan dan ruang dalam',
                ],
                'steps' => [
                    '1. Mengurus surat pengantar RT/RW dengan menyatakan keperluan pembuatan SKTM.',
                    '2. Membawa berkas ke Kantor Pekon Margodadi menemui Kasi Kesra (Ibu Asih Lestari) atau Poskesos Madani.',
                    '3. Petugas mencocokkan data pemohon dengan basis data DTKS / P3KE Pekon Margodadi.',
                    '4. Petugas memproses pencetakan SKTM resmi.',
                    '5. Pengesahan dan penandatanganan oleh Kepala Pekon Margodadi.',
                    '6. SKTM diserahkan kepada pemohon secara cuma-cuma.',
                ],
                'cost_info' => 'Gratis (Rp 0)',
                'sla_duration' => '20 - 30 Menit',
                'officer_in_charge' => 'Kasi Kesejahteraan (Asih Lestari) & Poskesos Madani',
                'tags' => ['sktm', 'bansos', 'sekolah', 'beasiswa', 'bpjs'],
                'is_active' => true,
            ],

            // 3. Surat Pengantar KTP-el dan Kartu Keluarga (KK)
            [
                'category_id' => $catAdminduk?->category_id,
                'title' => 'Surat Pengantar KTP-el & Kartu Keluarga',
                'slug' => 'surat-pengantar-ktp-kk',
                'category_badge' => 'Administrasi Kependudukan',
                'sub_category_badge' => 'Identitas Kependudukan',
                'description' => 'Layanan pengantar untuk perekaman KTP Elektronik baru (pemula usia 17 tahun), penggantian KTP rusak/hilang, penambahan anggota keluarga, atau pembaruan elemen data KK ke Disdukcapil Pringsewu.',
                'legal_basis' => 'UU No. 24 Tahun 2013 tentang Administrasi Kependudukan',
                'requirements' => [
                    'Surat Pengantar dari Ketua RT setempat',
                    'Fotokopi Akta Kelahiran dan Ijazah terakhir (untuk KTP baru)',
                    'KTP lama (jika rusak) atau Surat Kehilangan dari Kepolisian (jika hilang)',
                    'Kartu Keluarga asli lama (untuk pembaruan KK)',
                    'Surat Nikah / Akta Cerai (jika ada perubahan status)',
                ],
                'steps' => [
                    '1. Pemohon mendapatkan surat pengantar dari RT.',
                    '2. Menyerahkan berkas ke Seksi Pemerintahan Pekon Margodadi.',
                    '3. Petugas memeriksa kesesuaian NIK dan menerbitkan Formulir F-1.01/F-1.02 atau surat pengantar resmi.',
                    '4. Berkas ditandatangani oleh pejabat pekon.',
                    '5. Pemohon membawa berkas ke Kantor Camat Ambarawa atau Kantor Disdukcapil Pringsewu.',
                ],
                'cost_info' => 'Gratis (Rp 0)',
                'sla_duration' => '15 Menit di Balai Pekon',
                'officer_in_charge' => 'Kasi Pemerintahan Pekon Margodadi (Didi Gunari)',
                'tags' => ['ktp', 'kk', 'adminduk', 'disdukcapil'],
                'is_active' => true,
            ],

            // 4. Surat Keterangan Domisili
            [
                'category_id' => $catAdminduk?->category_id,
                'title' => 'Surat Keterangan Domisili Tempat Tinggal / Usaha',
                'slug' => 'surat-keterangan-domisili',
                'category_badge' => 'Administrasi Kependudukan',
                'sub_category_badge' => 'Domisili',
                'description' => 'Layanan penerbitan surat keterangan domisili bagi warga pendatang yang tinggal di Pekon Margodadi atau warga lokal untuk melengkapi berkas administrasi kerja, sekolah, maupun perbankan.',
                'legal_basis' => 'Peraturan Administrasi Kependudukan Pekon Margodadi',
                'requirements' => [
                    'Surat Pengantar RT/Kadus setempat',
                    'Fotokopi KTP & KK asal pemohon',
                    'Surat pernyataan tinggal/sewa dari pemilik rumah/kontrakan (jika berstatus penyewa)',
                ],
                'steps' => [
                    '1. Melapor ke Ketua RT dan Kepala Dusun di wilayah dusun tempat tinggal.',
                    '2. Datang ke Kantor Balai Pekon membawa berkas pengantar.',
                    '3. Petugas menginput data domisili pemohon.',
                    '4. Penandatanganan surat domisili oleh Kepala Pekon/Sekdes.',
                    '5. Penyerahan dokumen jadi.',
                ],
                'cost_info' => 'Gratis (Rp 0)',
                'sla_duration' => '15 Menit',
                'officer_in_charge' => 'Kasi Pelayanan (Sugiono)',
                'tags' => ['domisili', 'tempat tinggal', 'usaha', 'surat keterangan'],
                'is_active' => true,
            ],

            // 5. Surat Keterangan Kematian
            [
                'category_id' => $catAdminduk?->category_id,
                'title' => 'Surat Keterangan Kematian & Akta Kematian',
                'slug' => 'surat-keterangan-kematian',
                'category_badge' => 'Administrasi Kependudukan',
                'sub_category_badge' => 'Pencatatan Sipil',
                'description' => 'Penerbitan surat keterangan kematian bagi warga yang meninggal dunia untuk pengurusan ahli waris, penutupan rekening/asuransi, serta penerbitan akta kematian resmi dari Disdukcapil.',
                'legal_basis' => 'Permendagri No. 108 Tahun 2019 tentang Pelaksanaan Perpres No. 96 Tahun 2018',
                'requirements' => [
                    'Surat Pengantar RT setempat',
                    'Surat keterangan kematian dari Rumah Sakit / Bidan / Puskesmas (jika ada)',
                    'KTP Elektronik asli yang meninggal dunia',
                    'Kartu Keluarga asli keluarga yang meninggal',
                    'Fotokopi KTP pelapor (ahli waris) dan 2 orang saksi',
                ],
                'steps' => [
                    '1. Ahli waris melapor ke RT setempat.',
                    '2. Membawa berkas ke Seksi Pelayanan Balai Pekon Margodadi.',
                    '3. Petugas mencatat kematian di Buku Registrasi Kematian Desa dan menerbitkan Formulir Surat Kematian.',
                    '4. Pengesahan oleh Kepala Pekon.',
                ],
                'cost_info' => 'Gratis (Rp 0)',
                'sla_duration' => '15 Menit',
                'officer_in_charge' => 'Kasi Pelayanan (Sugiono)',
                'tags' => ['kematian', 'akta kematian', 'ahli waris'],
                'is_active' => true,
            ],

            // 6. Surat Pengantar Nikah (Model N1 - N4)
            [
                'category_id' => $catAdminduk?->category_id,
                'title' => 'Surat Pengantar Nikah (Model N1 - N4)',
                'slug' => 'surat-pengantar-nikah',
                'category_badge' => 'Administrasi Kependudukan',
                'sub_category_badge' => 'Perkawinan',
                'description' => 'Layanan penerbitan berkas formulir pengantar perkawinan bagi warga Margodadi yang akan melangsungkan pernikahan di Kantor Urusan Agama (KUA) Ambarawa.',
                'legal_basis' => 'PMA No. 20 Tahun 2019 tentang Pencatatan Pernikahan',
                'requirements' => [
                    'Surat Pengantar RT/Kadus setempat',
                    'Fotokopi KTP & KK calon pengantin serta orang tua calon pengantin',
                    'Fotokopi Akta Kelahiran dan Ijazah calon pengantin',
                    'Surat pernyataan belum pernah menikah (bermaterai) atau Akta Cerai/Kematian bagi duda/janda',
                    'Pas foto ukuran 2x3 (4 lembar) dan 4x6 (2 lembar) berlatar belakang biru',
                    'Surat imunisasi TT dari Puskesmas bagi calon pengantin wanita',
                ],
                'steps' => [
                    '1. Menghubungi Kasi Pelayanan Pekon Margodadi paling lambat 15 hari sebelum tanggal akad.',
                    '2. Membawa seluruh berkas persyaratan ke Balai Pekon.',
                    '3. Petugas menyusun formulir resmi N1 (Pengantar Nikah), N2 (Asal Usul), dan N4 (Persetujuan Calon Mempelai).',
                    '4. Verifikasi dan penandatanganan oleh Kepala Pekon Didi Handoko.',
                    '5. Berkas dibawa pemohon untuk didaftarkan ke KUA Kecamatan Ambarawa.',
                ],
                'cost_info' => 'Gratis (Rp 0)',
                'sla_duration' => '1 Hari Kerja',
                'officer_in_charge' => 'Kasi Pelayanan Pekon Margodadi (Sugiono)',
                'tags' => ['nikah', 'kua', 'n1', 'perkawinan'],
                'is_active' => true,
            ],

            // 7. Fasilitasi Pendaftaran & Verifikasi DTKS Bansos
            [
                'category_id' => $catBansos?->category_id,
                'title' => 'Fasilitasi Pendaftaran & Verifikasi DTKS Bansos',
                'slug' => 'fasilitasi-bansos-dtks',
                'category_badge' => 'Bantuan Sosial',
                'sub_category_badge' => 'DTKS & Bansos',
                'description' => 'Layanan pendaftaran keluarga prasejahtera ke Data Terpadu Kesejahteraan Sosial (DTKS), usulan program bansos (PKH, BPNT, BLT-DD, PBI-JK/BPJS), serta perbaikan anomali data NIK.',
                'legal_basis' => 'Permensos RI No. 3 Tahun 2021 tentang Pengelolaan DTKS',
                'requirements' => [
                    'Fotokopi Kartu Keluarga dan KTP seluruh anggota keluarga',
                    'Foto kondisi fisik tempat tinggal (tampak depan, samping, dan dalam)',
                    'Surat keterangan pengantar dari RT/Kadus setempat',
                    'Terdaftar dalam kriteria desil kemiskinan pada musyawarah desa',
                ],
                'steps' => [
                    '1. Warga mendaftar kepada Kepala Dusun atau langsung ke sekretariat Poskesos Madani di Balai Pekon.',
                    '2. Tim Poskesos Madani (Ketua: Heri Purnomo) melakukan verifikasi faktual lapangan.',
                    '3. Hasil verifikasi dibahas dalam Musyawarah Desa (Musdes) Verifikasi DTKS bersama BHP dan Tokoh Masyarakat.',
                    '4. Data yang disetujui diunggah oleh operator ke sistem SIKS-NG Kementerian Sosial RI.',
                ],
                'cost_info' => 'Gratis (Rp 0)',
                'sla_duration' => 'Sesuai Siklus Pemutakhiran Bulanan Kemensos',
                'officer_in_charge' => 'Poskesos Madani (Heri Purnomo) & Kasi Kesra (Asih Lestari)',
                'tags' => ['dtks', 'bansos', 'pkh', 'bpnt', 'blt', 'poskesos'],
                'is_active' => true,
            ],

            // 8. Peminjaman Gedung Serba Guna (GSG) & Sarana Pekon
            [
                'category_id' => $catFasilitas?->category_id,
                'title' => 'Peminjaman Gedung Serba Guna (GSG) & Balai Pekon',
                'slug' => 'peminjaman-gsg-fasilitas-pekon',
                'category_badge' => 'Fasilitas & Sarana Pekon',
                'sub_category_badge' => 'Gedung Serba Guna',
                'description' => 'Layanan permohonan izin penggunaan Gedung Serba Guna (GSG) Pekon Margodadi untuk acara kemasyarakatan, resepsi pernikahan warga, rapat warga, olahraga, atau penyaluran bantuan sosial.',
                'legal_basis' => 'Peraturan Desa Margodadi tentang Pemanfaatan Aset Desa',
                'requirements' => [
                    'Surat permohonan izin penggunaan GSG ditujukan kepada Kepala Pekon Margodadi',
                    'Fotokopi KTP penanggung jawab acara',
                    'Surat kesanggupan menjaga kebersihan dan ketertiban fasilitas umum pekon',
                ],
                'steps' => [
                    '1. Pemohon mengajukan surat permohonan minimal 7 hari sebelum jadwal kegiatan.',
                    '2. Menemui Kaur Tata Usaha dan Umum (Fatmawati) untuk mengecek ketersediaan jadwal gedung.',
                    '3. Persetujuan penggunaan gedung oleh Kepala Pekon.',
                    '4. Koordinasi teknis dengan pengelola sarana dan Satlinmas Margodadi.',
                ],
                'cost_info' => 'Gratis / Sesuai Kesepakatan Kas Pemeliharaan Kebersihan',
                'sla_duration' => '1 - 2 Hari Kerja',
                'officer_in_charge' => 'Kaur Tata Usaha dan Umum (Fatmawati)',
                'tags' => ['gsg', 'gedung', 'fasilitas', 'acara', 'rapat'],
                'is_active' => true,
            ],
        ];

        foreach ($services as $srv) {
            PublicService::updateOrCreate(
                ['slug' => $srv['slug']],
                $srv
            );
        }
    }
}
