<?php

namespace Database\Seeders;

use App\Models\PublicService;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class PublicServiceSeeder extends Seeder
{
    public function run(): void
    {
        $suratCat = ServiceCategory::where('slug', 'surat-pengantar')->first();
        $adminCat = ServiceCategory::where('slug', 'administrasi-kependudukan')->first();
        $fasilitasCat = ServiceCategory::where('slug', 'fasilitas-desa')->first();
        $sopCat = ServiceCategory::where('slug', 'sop-loket')->first();

        $services = [
            [
                'category_id' => $suratCat?->category_id,
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
                'category_id' => $suratCat?->category_id,
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
                'category_id' => $adminCat?->category_id,
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
                'category_id' => $suratCat?->category_id,
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
                'category_id' => $fasilitasCat?->category_id,
                'title' => 'Pelayanan Peminjaman Sarana Aula & Tenda Serbaguna Pekon',
                'slug' => 'fasilitas',
                'category_badge' => 'FASILITAS',
                'sub_category_badge' => 'Sarana Publik Warga',
                'description' => 'Peminjaman fasilitas Balai Kemasyarakatan Pekon Margodadi, tenda hajatan desa, sound system publik, serta kursi lipat untuk kegiatan hajatan, musyawarah warga, atau acara sosial keagamaan.',
                'legal_basis' => 'Peraturan Pengelolaan Aset Desa Pekon Margodadi No. 02 Tahun 2022 tentang Pemanfaatan Sarana & Prasarana Umum.',
                'requirements' => [
                    'Surat Permohonan ditujukan ke Kaur Umum Pekon Margodadi',
                    'Fotokopi KTP Penanggung Jawab Kegiatan',
                    'Surat pernyataan kesediaan menjaga kebersihan, ketertiban & keutuhan inventaris pekon',
                ],
                'steps' => [
                    ['step' => 'Langkah 1', 'name' => 'Cek Ketersediaan', 'desc' => 'Petugas memeriksa kalender agenda aula dan inventaris alat pada tanggal yang diajukan.', 'time' => '± 5 Menit', 'icon' => 'calendar_month'],
                    ['step' => 'Langkah 2', 'name' => 'Penerbitan Izin Peminjaman', 'desc' => 'Kaur Umum menerbitkan berita acara persetujuan peminjaman fasilitas.', 'time' => 'H-3 Kegiatan', 'icon' => 'assignment_turned_in'],
                    ['step' => 'Langkah 3', 'name' => 'Penyerahan / Pemakaian', 'desc' => 'Penyerahan kunci aula atau inventaris sarpras kepada penanggung jawab.', 'time' => 'Sesuai Jadwal', 'icon' => 'handshake'],
                ],
                'sla_duration' => 'Ajukan H-3',
                'cost_info' => 'Bebas Biaya Sewa',
                'officer_in_charge' => 'Kaur Umum & Aset Pekon',
                'download_url' => 'https://margodadi.desa.id/downloads/formulir_peminjaman_sarpras.pdf',
                'tags' => ['fasilitas', 'sarana prasarana', 'aula', 'balai desa', 'tenda', 'kursi', 'sound system', 'peminjaman'],
                'is_active' => true,
            ],
            [
                'category_id' => $sopCat?->category_id,
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
        ];

        foreach ($services as $srv) {
            PublicService::updateOrCreate(
                ['slug' => $srv['slug']],
                $srv
            );
        }
    }
}
