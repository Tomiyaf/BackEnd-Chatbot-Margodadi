<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Menyiapkan Laravel dan Python RAG lokal

Jalankan dari root repository menggunakan PHP dan Composer yang tersedia di PATH.
Untuk Python, gunakan Python 3.12 pada Windows:

```powershell
composer install
py -3.12 -m venv .venv
.\.venv\Scripts\python.exe -m pip install -r requirements-local.txt
.\.venv\Scripts\python.exe -m pip check
```

`requirements.txt` berisi paket API. `requirements-local.txt` menyertakan paket
tersebut beserta Sentence Transformers, Transformers, dan PyTorch CUDA 12.4
untuk embedding BGE-M3 lokal. Paket Python dipasang di `.venv` repository ini,
sedangkan paket Laravel dipasang di `vendor` repository ini.

Siapkan `.env` untuk kedua layanan. Untuk BGE-M3, gunakan
`EMBEDDING_PROVIDER=local`, `EMBEDDING_DIMENSION=1024`, dan arahkan
`EMBEDDING_MODEL_PATH` ke folder model yang sudah tersedia. Atur koneksi
PostgreSQL dan endpoint Ollama sesuai lingkungan lokal, kemudian jalankan
`./start-api.ps1` untuk API Python. Pengaturan `.env`, bobot model, `.venv`,
dan `vendor` tidak disimpan ke Git.

### Data edukasi sampah yang sudah dipensiunkan

Domain aktif sekarang hanya `PUBLIC_SERVICE` dan `UMKM`. Dokumen dan kategori
`WASTE_EDUCATION` tetap tersimpan, tetapi tidak ditampilkan, diindeks ulang,
atau digunakan sebagai sumber jawaban. PDF/Excel lokal tanpa label domain
tetap dapat dicari. Dokumen/chunk Python yang berstatus metadata
`is_active=false` juga tidak digunakan dalam retrieval.

Sebelum menjalankan `php artisan migrate`, buat backup database dan uji pada
salinannya. Migrasi `2026_10_18_005900` menyimpan snapshot JSON data lama dari
tiga tabel edukasi, dokumen, chunk, dan kategori ke `retired_education_archive`,
lalu menonaktifkan dokumen/kategori edukasi. Migrasi penghapusan tabel dari
`main` berjalan sesudah pengarsipan ini. Arsip sengaja dipertahankan saat
rollback; rollback tidak otomatis memulihkan modul edukasi.

Jika migrasi penghapusan tabel sudah pernah dijalankan sebelum migrasi arsip
ini tersedia, data tabel yang terhapus hanya dapat dipulihkan dari backup
sebelumnya. Migrasi arsip tetap menyimpan dokumen/kategori yang masih tersedia.

### Daftar UMKM dalam percakapan

Laravel mengirim `session_id` dan `umkm_catalogue` (ID/nama UMKM aktif) ke
Python pada `/chat`. Sesi web memakai `web:<conversation_id>` dan WhatsApp
memakai `whatsapp:<conversation_id>`. Daftar bersumber dari database Laravel
terbaru sehingga dapat ditampilkan sebelum profilnya disinkronkan ke vektor.
Pertanyaan detail tetap menggunakan retrieval RAG seperti sebelumnya.

Pertanyaan seperti "Apa saja UMKM di Pekon Margodadi?" menampilkan 5 nama
pertama menurut abjad. Jika masih ada usaha lain, jawaban menyebut
"Selain itu, masih ada beberapa UMKM lainnya." Pertanyaan "apa saja itu?",
"yang lainnya?", atau "apa lagi?" menampilkan usaha yang belum disebutkan.
Jika semuanya sudah ditampilkan, bot tidak mengklaim ada usaha tambahan.
Permintaan daftar yang baru memulai ulang daftar; perpindahan ke topik lain
mengakhiri konteks daftar sebelumnya.

Ingatan daftar menyimpan ID yang sudah ditampilkan dan waktu aktivitas,
terpisah per percakapan, dengan masa berlaku 30 menit tidak aktif. SQLite
berada di `storage/app/private/chatbot-conversations.sqlite` dan tidak masuk
Git. Lokasi dapat diatur lewat `CHATBOT_SESSION_DB`; direktori tersebut harus
dapat ditulis proses Python. Ingatan tetap tersedia setelah API di-restart.
Permintaan API lama tanpa `umkm_catalogue` tetap memakai alur RAG sebelumnya.
Tidak ada migrasi tabel Laravel atau perubahan frontend yang diperlukan.

### Format jawaban prosedur dan persyaratan

Python mengenali pertanyaan langkah, tahapan, alur, prosedur, atau cara
pengurusan dan meminta model menuliskan setiap tindakan pada baris bernomor.
Pertanyaan syarat/dokumen/perlengkapan memakai poin `- `. Jika keduanya
ditanyakan, jawaban memisahkan bagian persyaratan dan langkah-langkah.
Pertanyaan fakta atau identitas tetap memakai kalimat natural.

Format tetap mengikuti informasi sumber. Jika sumber hanya memuat
persyaratan, bot menjelaskan bahwa urutan prosedur tidak tersedia dan tidak
mengubah persyaratan menjadi tahapan rekaan. Normalisasi jawaban hanya
merapikan penanda daftar dan baris baru; angka, tanggal, biaya, satuan,
kondisi, dan rujukan sumber tetap dipertahankan. Anggaran keluaran untuk
jawaban berformat daftar dinaikkan ke 768 token agar beberapa butir dapat
ditulis lengkap. Jawaban fakta tetap memakai anggaran sebelumnya.

Tampilan chat publik sudah memakai `whitespace-pre-wrap`, sehingga daftar
teks dapat ditampilkan tanpa perubahan frontend atau struktur database.

### Beberapa pertanyaan dalam satu pesan

Chatbot memisahkan permintaan yang jelas menjadi beberapa bagian sebelum
menjawab. Contohnya, "Apa saja UMKM di Pekon Margodadi dan bagaimana cara
mengurus surat nikah?" menghasilkan bagian daftar UMKM dan bagian prosedur
surat nikah. Pesan dapat memuat lebih dari dua pertanyaan. Pemisahan mengenali
kata tanya/perintah setelah penghubung, tanda tanya, titik koma, dan daftar
pertanyaan bernomor. Frasa seperti "KTP dan KK" tetap satu bagian.

Setiap bagian memperoleh hasil pencarian `top_k` sendiri. Daftar UMKM tetap
menggunakan katalog aktif dari Laravel. Rujukan singkat yang subjeknya jelas
dalam pesan, misalnya "bagaimana cara membuatnya" setelah pertanyaan surat
nikah, diselesaikan sebelum pencarian. Subjek yang ambigu tidak ditebak.
Kalimat yang tidak memiliki batas pertanyaan yang jelas tetap memakai alur
RAG biasa; pemisah ini tidak memanggil model tambahan untuk menafsirkan pesan.

Jawaban disusun menurut urutan pertanyaan dengan label `Pertanyaan 1:`,
`Pertanyaan 2:`, dan seterusnya. Syarat memakai poin, prosedur memakai nomor,
dan bagian yang datanya tidak ditemukan menyatakan keterbatasan itu tanpa
menutupi jawaban bagian lain. Gangguan layanan dibedakan dari ketiadaan data.
Sumber yang sama digabung tanpa mengubah metadata atau kontrak respons API.

Pesan yang memuat daftar UMKM bersama topik lain mempertahankan ingatan
daftarnya, sehingga "UMKM lainnya?" pada pesan berikutnya menampilkan nama
yang belum disebutkan. Pesan berikutnya yang sepenuhnya beralih topik tetap
mengakhiri konteks daftar. Retrieval dilakukan berurutan; maksimal tiga
generasi jawaban berjalan bersamaan untuk mengurangi waktu tunggu. Pesan
dengan banyak pertanyaan tetap dapat memerlukan waktu lebih lama.

Uji tanpa database aplikasi:

```powershell
.\.venv\Scripts\python.exe -B -m unittest discover -s tests/python -p test_multi_question.py -v
.\.venv\Scripts\python.exe -B -m unittest discover -s tests/python -p test_answer_format.py -v
.\.venv\Scripts\python.exe -B -m unittest discover -s tests/python -p test_umkm_conversation.py -v
```

`test_retired_retrieval.py` adalah uji integrasi terpisah yang memerlukan
`RAG_TEST_DATABASE_URL` menuju salinan database berawalan `step9_chatbot_`.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
