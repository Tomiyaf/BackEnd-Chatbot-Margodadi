<?php

namespace Database\Seeders;

use App\Enums\ServiceDomain;
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
        // 1. Kategori Layanan Administrasi & Kependudukan
        $catKependudukan = ServiceCategory::firstOrCreate(
            ['slug' => 'administrasi-kependudukan'],
            [
                'name' => 'Administrasi & Kependudukan',
                'domain' => ServiceDomain::PUBLIC_SERVICE,
                'icon' => 'badge',
                'description' => 'Layanan pembuatan surat pengantar KTP, KK, Akta, dan Keterangan Domisili.',
                'is_active' => true,
            ]
        );

        // 2. Kategori Layanan Usaha & Perekonomian
        $catUsaha = ServiceCategory::firstOrCreate(
            ['slug' => 'usaha-perekonomian'],
            [
                'name' => 'Usaha & Perekonomian',
                'domain' => ServiceDomain::PUBLIC_SERVICE,
                'icon' => 'storefront',
                'description' => 'Layanan surat perizinan usaha, rekomendasi KUR, dan legalitas UMKM pekon.',
                'is_active' => true,
            ]
        );

        // 3. Kategori Layanan Sosial & Bantuan
        $catSosial = ServiceCategory::firstOrCreate(
            ['slug' => 'sosial-kesejahteraan'],
            [
                'name' => 'Sosial & Kesejahteraan',
                'domain' => ServiceDomain::PUBLIC_SERVICE,
                'icon' => 'groups',
                'description' => 'Layanan Surat Keterangan Tidak Mampu (SKTM), beasiswa, dan bantuan sosial.',
                'is_active' => true,
            ]
        );

        $suratCat = ServiceCategory::firstOrCreate(
            ['slug' => 'surat-pengantar'],
            [
                'name' => 'Surat Pengantar & Keterangan',
                'domain' => ServiceDomain::PUBLIC_SERVICE,
                'icon' => 'description',
                'description' => 'Layanan pengurusan surat pengantar dan keterangan pekon.',
                'is_active' => true,
            ]
        );

        $fasilitasCat = ServiceCategory::firstOrCreate(
            ['slug' => 'fasilitas-desa'],
            [
                'name' => 'Fasilitas & Sarana Desa',
                'domain' => ServiceDomain::PUBLIC_SERVICE,
                'icon' => 'apartment',
                'description' => 'Peminjaman aula, gedung serbaguna, dan peralatan desa.',
                'is_active' => true,
            ]
        );

        $sopCat = ServiceCategory::firstOrCreate(
            ['slug' => 'sop-loket'],
            [
                'name' => 'SOP & Tata Tertib Loket',
                'domain' => ServiceDomain::PUBLIC_SERVICE,
                'icon' => 'rule',
                'description' => 'Informasi standar operasional prosedur dan jam layanan loket pekon.',
                'is_active' => true,
            ]
        );

        $services = [
            [
                'category_id' => $suratCat->category_id,
                'title' => 'Surat Keterangan Usaha (SKU)',
                'slug' => 'sku',
                'category_badge' => 'SURAT_PENGANTAR',
                'sub_category_badge' => 'UMKM & Perbankan',
                'description' => 'Surat resmi untuk legalitas usaha mikro/kecil warga Pekon Margodadi bagi keperluan pengajuan permodalan, perbankan (KUR), izin Nomor Induk Berusaha (NIB), atau sertifikasi produk UMKM desa.',
                'legal_basis' => 'Peraturan Pekon Margodadi No. 04 Tahun 2023 tentang Penyelenggaraan Pelayanan Publik Terpadu Berbasis Digital Desa.',
                'requirements' => [
                    'Fotokopi e-KTP Pemohon yang masih berlaku (1 lembar)',
                    'Fotokopi Kartu Keluarga (KK) Pekon Margodadi (1 lembar)',
                    'Surat Pengantar dari Ketua RT domisili usaha setempat',
                    'Foto dokumentasi fisik/tempat/kegiatan produksi usaha',
                    'Surat pernyataan usaha bermaterai Rp 10.000 (formulir disediakan gratis di loket pelayanan Pekon)',
                ],
                'steps' => [
                    ['step' => 'Langkah 1', 'name' => 'Registrasi', 'desc' => 'Ambil nomor antrean & verifikasi kelengkapan berkas fisik di Front Office.', 'time' => '± 5 Menit', 'icon' => 'timer'],
                    ['step' => 'Langkah 2', 'name' => 'Validasi Kasi', 'desc' => 'Pemeriksaan lapangan ringkas & pencatatan buku register oleh Kasi Pelayanan.', 'time' => '± 15 Menit', 'icon' => 'timer'],
                    ['step' => 'Langkah 3', 'name' => 'Tanda Tangan', 'desc' => 'Otorisasi berkas digital / fisik oleh Kepala Pekon atau Sekretaris Pekon.', 'time' => 'Sesuai Agenda', 'icon' => 'draw'],
                    ['step' => 'Langkah 4', 'name' => 'Penyerahan', 'desc' => 'Pembubuhan stempel dinas & penerbitan surat SKU resmi kepada pemohon.', 'time' => 'Selesai (Rp 0)', 'icon' => 'task_alt'],
                ],
                'sla_duration' => '1 Hari Kerja',
                'cost_info' => 'Gratis (Rp 0)',
                'officer_in_charge' => 'Loket Kasi Pelayanan',
                'download_url' => 'https://margodadi.desa.id/downloads/blangko_sku.pdf',
                'tags' => ['sku', 'surat keterangan usaha', 'modal', 'umkm', 'pinjaman', 'bank', 'kur', 'nib'],
                'is_active' => true,
            ],
            [
                'category_id' => $suratCat->category_id,
                'title' => 'Surat Keterangan Tidak Mampu (SKTM) Pendidikan & Kesehatan',
                'slug' => 'sktm',
                'category_badge' => 'SURAT_PENGANTAR',
                'sub_category_badge' => 'Sosial & Pendidikan',
                'description' => 'Surat pendukung permohonan beasiswa kuliah/sekolah (KIP/PIP), keringanan biaya rumah sakit, jaminan kesehatan daerah, dan pendataan Data Terpadu Kesejahteraan Sosial (DTKS).',
                'legal_basis' => 'Permensos RI No. 3 Tahun 2021 & Petunjuk Teknis Pelayanan Kesejahteraan Sosial Warga Pekon Margodadi.',
                'requirements' => [
                    'Salinan KTP & KK Pekon Margodadi (1 lembar)',
                    'Surat Pengantar dari RT/RW setempat',
                    'Surat Pernyataan Tidak Mampu ditandatangani 2 saksi tetangga',
                    'Foto kondisi rumah tampak depan & ruang utama (jika diperlukan verifikasi lapangan)',
                ],
                'steps' => [
                    ['step' => 'Langkah 1', 'name' => 'Pemberkasan', 'desc' => 'Menyerahkan pengantar RT dan dokumen pendukung ke Front Office.', 'time' => '± 5 Menit', 'icon' => 'timer'],
                    ['step' => 'Langkah 2', 'name' => 'Verifikasi DTKS', 'desc' => 'Kasi Kesejahteraan memverifikasi kesesuaian data warga dengan basis DTKS.', 'time' => '± 10 Menit', 'icon' => 'timer'],
                    ['step' => 'Langkah 3', 'name' => 'Otorisasi', 'desc' => 'Penandatanganan surat keterangan oleh Kepala Pekon atau Sekdes.', 'time' => '1 Hari Kerja', 'icon' => 'draw'],
                    ['step' => 'Langkah 4', 'name' => 'Penerbitan', 'desc' => 'Penerbitan SKTM berstempel resmi di loket pekon.', 'time' => 'Selesai (Rp 0)', 'icon' => 'task_alt'],
                ],
                'sla_duration' => '1 Hari Kerja',
                'cost_info' => 'Gratis',
                'officer_in_charge' => 'Loket Kasi Kesejahteraan',
                'download_url' => 'https://margodadi.desa.id/downloads/blangko_sktm.pdf',
                'tags' => ['sktm', 'surat keterangan tidak mampu', 'pendidikan', 'kip', 'beasiswa', 'bansos', 'bpjs', 'kesehatan'],
                'is_active' => true,
            ],
            [
                'category_id' => $catKependudukan->category_id,
                'title' => 'Surat Pengantar Perekaman & Penggantian e-KTP',
                'slug' => 'ktp',
                'category_badge' => 'ADMINISTRASI',
                'sub_category_badge' => 'Kependudukan',
                'description' => 'Penerbitan surat pengantar resmi pekon untuk warga usia 17 tahun ke atas guna melakukan perekaman biometrik di Kantor Camat Ambarawa, Kabupaten Pringsewu atau penggantian e-KTP rusak/hilang.',
                'legal_basis' => 'UU No. 24 Tahun 2013 tentang Perubahan atas UU No. 23 Tahun 2006 tentang Administrasi Kependudukan.',
                'requirements' => [
                    'Surat Pengantar RT domisili pemohon',
                    'Salinan Kartu Keluarga (KK) terbaru (1 lembar)',
                    'e-KTP fisik lama (khusus permohonan penggantian fisik yang rusak)',
                    'Surat Keterangan Kehilangan dari Polsek setempat (khusus e-KTP yang hilang)',
                ],
                'steps' => [
                    ['step' => 'Langkah 1', 'name' => 'Pemeriksaan Berkas', 'desc' => 'Petugas loket memeriksa kelengkapan identitas NIK & KK pemohon.', 'time' => '± 5 Menit', 'icon' => 'timer'],
                    ['step' => 'Langkah 2', 'name' => 'Cetak Surat Pengantar', 'desc' => 'Pencetakan surat pengantar perekaman/penggantian e-KTP ke Disdukcapil.', 'time' => '± 5 Menit', 'icon' => 'print'],
                    ['step' => 'Langkah 3', 'name' => 'Legalisir & Stempel', 'desc' => 'Penandatanganan & stempel resmi pekon untuk dibawa ke Kantor Camat/Disdukcapil.', 'time' => 'Langsung Jadi', 'icon' => 'task_alt'],
                ],
                'sla_duration' => 'Langsung Terbit (Seketika)',
                'cost_info' => 'Gratis',
                'officer_in_charge' => 'Loket Kasi Pemerintahan',
                'download_url' => 'https://margodadi.desa.id/downloads/pengantar_ktp.pdf',
                'tags' => ['ktp', 'e-ktp', 'perekaman e-ktp', 'kartu tanda penduduk', 'rusak', 'hilang', 'disdukcapil'],
                'is_active' => true,
            ],
            [
                'category_id' => $suratCat->category_id,
                'title' => 'Surat Keterangan Domisili Warga / Lembaga',
                'slug' => 'domisili',
                'category_badge' => 'SURAT_PENGANTAR',
                'sub_category_badge' => 'Kependudukan & Badan Hukum',
                'description' => 'Surat pernyataan pengesahan alamat tempat tinggal menetap sementara bagi penduduk pendatang maupun alamat sekretariat yayasan/organisasi berbadan hukum di wilayah Margodadi.',
                'legal_basis' => 'Peraturan Menteri Dalam Negeri No. 108 Tahun 2019 tentang Pelaksanaan Perpres No. 96 Tahun 2018.',
                'requirements' => [
                    'Fotokopi KTP Pemohon / Penanggung Jawab Lembaga',
                    'Surat Pengantar RT/RW setempat',
                    'Surat Perjanjian Sewa / Izin Pemilik Rumah (jika mengontrak/tinggal sementara)',
                    'Akta Pendirian / SK Kemenkumham (khusus organisasi/lembaga/yayasan)',
                ],
                'steps' => [
                    ['step' => 'Langkah 1', 'name' => 'Verifikasi Alamat', 'desc' => 'Pemeriksaan keabsahan domisili berdasarkan pengantar RT.', 'time' => '± 5 Menit', 'icon' => 'timer'],
                    ['step' => 'Langkah 2', 'name' => 'Pencatatan Buku Register', 'desc' => 'Registrasi data domisili warga/lembaga ke buku induk kependudukan pekon.', 'time' => '± 10 Menit', 'icon' => 'timer'],
                    ['step' => 'Langkah 3', 'name' => 'Penerbitan Surat', 'desc' => 'Penandatanganan dan penyerahan surat domisili definitif berstempel.', 'time' => '1 Hari Kerja', 'icon' => 'task_alt'],
                ],
                'sla_duration' => '1 Hari Kerja',
                'cost_info' => 'Gratis',
                'officer_in_charge' => 'Loket Kasi Pemerintahan',
                'download_url' => 'https://margodadi.desa.id/downloads/blangko_domisili.pdf',
                'tags' => ['domisili', 'surat keterangan domisili', 'warga', 'lembaga', 'yayasan', 'tempat tinggal', 'usaha'],
                'is_active' => true,
            ],
            [
                'category_id' => $fasilitasCat->category_id,
                'title' => 'Pelayanan Peminjaman Sarana Aula & Tenda Serbaguna Pekon',
                'slug' => 'fasilitas',
                'category_badge' => 'FASILITAS',
                'sub_category_badge' => 'Sarana Publik Warga',
                'description' => 'Peminjaman fasilitas Balai Kemasyarakatan Pekon Margodadi, tenda hajatan desa, sound system publik, serta kursi lipat untuk kegiatan hajatan, musyawarah warga, atau acara sosial keagamaan.',
                'legal_basis' => 'Peraturan Pengelolaan Aset Desa Pekon Margodadi No. 02 Tahun 2022 tentang Pemanfaatan Sarana & Prasarana Umum.',
                'requirements' => [
                    'Surat Permohonan ditujukan ke Kaur Umum Pekon Margodadi',
                    'Identitas Penanggung Jawab Kegiatan (KTP Margodadi)',
                    'Jadwal & rincian susunan acara (minimal diajukan H-7)',
                    'Surat Kesediaan Menjaga Kebersihan & Keutuhan Sarana Prasarana',
                ],
                'steps' => [
                    ['step' => 'Langkah 1', 'name' => 'Cek Ketersediaan', 'desc' => 'Koordinasi jadwal booking aula/sarana dengan Kaur Umum Balai Pekon.', 'time' => '± 10 Menit', 'icon' => 'calendar_month'],
                    ['step' => 'Langkah 2', 'name' => 'Formulir Peminjaman', 'desc' => 'Pengisian blangko inventaris dan penyerahan surat permohonan izin acara.', 'time' => '± 10 Menit', 'icon' => 'assignment'],
                    ['step' => 'Langkah 3', 'name' => 'Penerbitan Izin', 'desc' => 'Surat rekomendasi pemakaian sarana dikeluarkan resmi oleh Kaur Umum.', 'time' => '1 Hari Kerja', 'icon' => 'verified'],
                ],
                'sla_duration' => '1 Hari Kerja',
                'cost_info' => 'Bebas Biaya Sewa',
                'officer_in_charge' => 'Kaur Umum & Aset Pekon',
                'download_url' => 'https://margodadi.desa.id/downloads/formulir_peminjaman_sarpras.pdf',
                'tags' => ['fasilitas', 'sarana prasarana', 'aula', 'balai desa', 'tenda', 'kursi', 'sound system', 'peminjaman'],
                'is_active' => true,
            ],
            [
                'category_id' => $sopCat->category_id,
                'title' => 'Standar Operasional Prosedur (SOP) Pelayanan Loket Terpadu',
                'slug' => 'sop',
                'category_badge' => 'SOP_LOKET',
                'sub_category_badge' => 'Standar Operasional',
                'description' => 'Panduan tata cara dan etika pelayanan loket Balai Pekon Margodadi bagi masyarakat yang datang langsung untuk pengurusan berbagai dokumen administratif.',
                'legal_basis' => 'Permenpan-RB No. 35 Tahun 2012 tentang Pedoman Penyusunan Standar Operasional Prosedur Administrasi Pemerintahan.',
                'requirements' => [
                    'Membawa dokumen identitas asli (e-KTP & KK) untuk verifikasi berkas',
                    'Berpakaian sopan dan rapi saat mengunjungi Balai Pekon Margodadi',
                    'Mengambil nomor antrean loket di ruang tunggu pelayanan',
                ],
                'steps' => [
                    ['step' => 'Langkah 1', 'name' => 'Kedatangan', 'desc' => 'Warga tiba di Balai Pekon dan mengambil nomor antrean loket.', 'time' => '08.00 - 15.00 WIB', 'icon' => 'door_front'],
                    ['step' => 'Langkah 2', 'name' => 'Verifikasi Berkas', 'desc' => 'Petugas front-office memeriksa kelengkapan persyaratan fisik.', 'time' => '± 5 - 10 Menit', 'icon' => 'fact_check'],
                    ['step' => 'Langkah 3', 'name' => 'Pemrosesan Dokumen', 'desc' => 'Kasi terkait memproses pencetakan dan penandatanganan surat.', 'time' => 'Maks. 1 Hari Kerja', 'icon' => 'history_edu'],
                    ['step' => 'Langkah 4', 'name' => 'Pengambilan Dokumen', 'desc' => 'Warga menerima dokumen resmi yang telah distempel dinas pekon.', 'time' => 'Selesai', 'icon' => 'verified'],
                ],
                'sla_duration' => 'Transparan & Terbuka',
                'cost_info' => '100% Bebas Retribusi',
                'officer_in_charge' => 'Sekretariat Pekon Margodadi',
                'download_url' => 'https://margodadi.desa.id/downloads/sop_pelayanan_loket.pdf',
                'tags' => ['sop', 'standar operasional', 'prosedur', 'alur', 'loket', 'pelayanan terpadu', 'jam kerja'],
                'is_active' => true,
            ],
            // Layanan Front Office routes support
            [
                'category_id' => $catUsaha->category_id,
                'title' => 'Surat Keterangan Usaha (SKU)',
                'slug' => 'surat-keterangan-usaha-sku',
                'category_badge' => 'SURAT_PENGANTAR',
                'sub_category_badge' => 'Perekonomian & UMKM',
                'description' => 'Surat keterangan resmi dari Pekon Margodadi yang menyatakan kepemilikan dan keberadaan usaha aktif warga untuk pengajuan pinjaman bank/KUR atau legalitas usaha.',
                'legal_basis' => 'Peraturan Desa Margodadi No. 03 Tahun 2023 tentang Pelayanan Administrasi Usaha Desa',
                'requirements' => [
                    'Fotokopi KTP Pemohon (wajib berdomisili Margodadi)',
                    'Fotokopi Kartu Keluarga (KK)',
                    'Surat Pengantar dari Ketua RT setempat',
                    'Foto dokumentasi tempat usaha / foto produk',
                ],
                'steps' => [
                    ['step' => 1, 'name' => 'Pengantar RT', 'desc' => 'Meminta surat pengantar dari Ketua RT tempat tinggal', 'time' => '15 Menit', 'icon' => 'user-check'],
                    ['step' => 2, 'name' => 'Verifikasi Loket', 'desc' => 'Menyerahkan berkas persyaratan ke Loket Pelayanan Balai Pekon', 'time' => '10 Menit', 'icon' => 'file-text'],
                    ['step' => 3, 'name' => 'Pemeriksaan & Cetak', 'desc' => 'Petugas memvalidasi data dan mencetak draf SKU', 'time' => '15 Menit', 'icon' => 'printer'],
                    ['step' => 4, 'name' => 'Penandatanganan', 'desc' => 'Penandatanganan oleh Kepala Pekon/Sekretaris Pekon', 'time' => '30 Menit', 'icon' => 'pen-tool'],
                    ['step' => 5, 'name' => 'Pengambilan Surat', 'desc' => 'Surat siap diambil tanpa dipungut biaya', 'time' => '5 Menit', 'icon' => 'check-circle'],
                ],
                'sla_duration' => '1 Hari Kerja (Maksimal 24 Jam)',
                'cost_info' => 'Gratis (Rp 0)',
                'officer_in_charge' => 'Loket Kasi Pelayanan Pekon Margodadi',
                'download_url' => 'https://margodadi.desa.id/formulir/sku-template.pdf',
                'tags' => ['sku', 'usaha', 'umkm', 'kur', 'bank', 'surat usaha', 'modal'],
                'is_active' => true,
            ],
            [
                'category_id' => $catSosial->category_id,
                'title' => 'Surat Keterangan Tidak Mampu (SKTM)',
                'slug' => 'surat-keterangan-tidak-mampu-sktm',
                'category_badge' => 'SURAT_PENGANTAR',
                'sub_category_badge' => 'Sosial & Pendidikan',
                'description' => 'Surat pengantar untuk keperluan permohonan beasiswa sekolah/kuliah, keringanan biaya pengobatan rumah sakit (BPJS PBI), atau program bantuan sosial.',
                'legal_basis' => 'Keputusan Kepala Pekon Margodadi tentang Kriteria Keluarga Pra-Sejahtera',
                'requirements' => [
                    'Fotokopi KTP Pemohon / Orang Tua',
                    'Fotokopi Kartu Keluarga (KK)',
                    'Surat Pengantar RT yang diketahui RW',
                    'Surat Keterangan Penghasilan / Keterangan Sekolah/Kampus (bila untuk pendidikan)',
                ],
                'steps' => [
                    ['step' => 1, 'name' => 'Pengantar RT/RW', 'desc' => 'Meminta surat pengantar dari RT/RW setempat', 'time' => '15 Menit', 'icon' => 'user-check'],
                    ['step' => 2, 'name' => 'Pendaftaran di Balai Pekon', 'desc' => 'Menyerahkan berkas ke Kasi Kesejahteraan', 'time' => '10 Menit', 'icon' => 'file-text'],
                    ['step' => 3, 'name' => 'Verifikasi Data DTKS', 'desc' => 'Pengecekan kesesuaian data warga dengan basis data sosial', 'time' => '20 Menit', 'icon' => 'database'],
                    ['step' => 4, 'name' => 'Penerbitan SKTM', 'desc' => 'Penerbitan dan legalisasi surat oleh Kepala Pekon', 'time' => '30 Menit', 'icon' => 'pen-tool'],
                ],
                'sla_duration' => '1 Hari Kerja',
                'cost_info' => 'Gratis (Rp 0)',
                'officer_in_charge' => 'Loket Kasi Kesejahteraan Pekon',
                'download_url' => 'https://margodadi.desa.id/formulir/sktm-template.pdf',
                'tags' => ['sktm', 'tidak mampu', 'beasiswa', 'kip', 'bpjs', 'bantuan sosial', 'kesehatan'],
                'is_active' => true,
            ],
            [
                'category_id' => $catKependudukan->category_id,
                'title' => 'Surat Keterangan Domisili Warga',
                'slug' => 'surat-keterangan-domisili-warga',
                'category_badge' => 'ADMINISTRASI',
                'sub_category_badge' => 'Kependudukan',
                'description' => 'Surat keterangan domisili atau tempat tinggal resmi warga perseorangan di wilayah Pekon Margodadi untuk urusan perbankan, pekerjaan, atau instansi resmi.',
                'legal_basis' => 'Undang-Undang Administrasi Kependudukan',
                'requirements' => [
                    'Fotokopi KTP',
                    'Fotokopi Kartu Keluarga (KK)',
                    'Surat Pengantar RT setempat',
                    'Pas foto ukuran 3x4 (1 lembar jika diperlukan)',
                ],
                'steps' => [
                    ['step' => 1, 'name' => 'Pengantar RT', 'desc' => 'Meminta pengantar RT', 'time' => '10 Menit', 'icon' => 'user-check'],
                    ['step' => 2, 'name' => 'Pelayanan Balai Pekon', 'desc' => 'Verifikasi dan cetak surat di loket pelayanan', 'time' => '15 Menit', 'icon' => 'file-text'],
                ],
                'sla_duration' => '30 Menit (Langsung Jadi)',
                'cost_info' => 'Gratis (Rp 0)',
                'officer_in_charge' => 'Loket Kasi Pemerintahan Pekon',
                'download_url' => null,
                'tags' => ['domisili', 'tempat tinggal', 'kependudukan', 'ktp', 'alamat'],
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
