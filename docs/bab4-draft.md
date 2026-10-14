# Bab 4 Draft: Bahan Screenshot untuk Laporan SIM

Dokumen ini berisi daftar screenshot yang perlu diambil dari aplikasi **Sini Laundry**
(Sistem Informasi Laundry) untuk Bab 4 (Pembahasan/Implementasi) laporan SIM.

**Persiapan:**
1. Pastikan data demo sudah terisi: `php artisan db:seed --class=DemoDataSeeder`
2. Buka aplikasi di `http://localhost:8000` (jalankan `php artisan serve` di Laragon).
3. Akun login (password: `password`):
   - **Pemilik:** `owner@sinilaundry.test` (Bu Sari)
   - **Admin:** `admin@sinilaundry.test` (Pak Budi)
   - **Pelanggan:** `pelanggan@sinilaundry.test` (Rina)
4. Untuk screenshot yang membutuhkan kode pesanan, salin salah satu kode dari
   halaman Daftar Pesanan (format `SL-20261014-001`).

---

## Daftar Screenshot

### Gambar 4.1 — Halaman Login

| Item | Isi |
|---|---|
| URL | `http://localhost:8000/login` |
| Akun | — (buka sebagai tamu) |
| Langkah | Buka URL login. |
| Yang terlihat | Logo dan nama "Sini Laundry", formulir email + kata sandi, tautan "Lupa kata sandi?", tombol Masuk, tautan Daftar. |

**Pembahasan:** Halaman login merupakan gerbang masuk seluruh pengguna sistem—pelanggan, admin, dan pemilik—sebagaimana pengguna yang diidentifikasi pada Bab 2. Setelah pengguna memasukkan kredensial, sistem mengalihkan pengguna ke halaman utama sesuai perannya masing-masing: pelanggan ke Beranda Pesanan, admin ke Daftar Pesanan, dan pemilik ke Dashboard Pemilik. Dengan begitu, setiap pengguna langsung melihat informasi yang relevan dengan tugasnya tanpa harus memilih menu tambahan.

### Gambar 4.2 — Halaman Registrasi Pelanggan

| Item | Isi |
|---|---|
| URL | `http://localhost:8000/register` |
| Akun | — (buka sebagai tamu) |
| Langkah | Buka URL register. |
| Yang terlihat | Formulir nama lengkap, email, nomor HP, kata sandi, konfirmasi kata sandi, tombol Buat Akun. |

**Pembahasan:** Halaman registrasi digunakan pelanggan untuk membuat akun sebelum memesan laundry secara online, sesuai kebutuhan pelanggan pada Bab 2 yang ingin memantau status cucian tanpa harus datang atau bertanya. Nomor HP wajib diisi karena dipakai sebagai identitas untuk cek status publik dan penautan dengan data pelanggan walk-in. Semua pendaftar melalui halaman ini otomatis memiliki peran pelanggan sehingga tidak dapat mengakses area admin maupun pemilik.

### Gambar 4.3 — Beranda Pelanggan

| Item | Isi |
|---|---|
| URL | `http://localhost:8000/pesanan` |
| Akun | `pelanggan@sinilaundry.test` |
| Langkah | Login sebagai pelanggan. Pastikan pelanggan memiliki pesanan aktif (buat pesanan online terlebih dahulu bila perlu). |
| Yang terlihat | Sapaan nama, kartu "Pesanan Aktif" dengan kode pesanan, badge status, estimasi selesai, tombol besar "Pesan Laundry", daftar riwayat pesanan. |

**Pembahasan:** Beranda pelanggan menampilkan ringkasan pesanan aktif dan riwayat pesanan sebelumnya. Kartu pesanan aktif memuat badge status dan perkiraan waktu selesai sehingga pelanggan dapat memantau cucian secara mandiri—yang menjawab masalah pada Bab 2 berupa kebutuhan transparansi status. Pada perangkat HP, bagian bawah layar menampilkan navigasi Beranda, Pesanan, Cek Status, dan Akun yang selalu terjangkau dengan satu jari.

### Gambar 4.4 — Form Pesan Online

| Item | Isi |
|---|---|
| URL | `http://localhost:8000/pesanan/baru` |
| Akun | `pelanggan@sinilaundry.test` |
| Langkah | Login sebagai pelanggan, klik "Pesan Laundry", pilih satu layanan, isi estimasi berat, pilih tanggal antar, isi catatan. |
| Yang terlihat | Daftar layanan yang bisa dipilih, kolom perkiraan harga yang berubah otomatis, catatan bahwa harga final ditentukan setelah penimbangan, tombol Buat Pesanan. |

**Pembahasan:** Form pesan online memungkinkan pelanggan memesan layanan dari rumah sesuai alur pemesanan online pada Bab 2. Sistem menampilkan perkiraan harga yang dihitung otomatis dari estimasi berat dan layanan, dengan keterangan tertulis bahwa harga final ditentukan oleh hasil timbangan di toko agar pelanggan memahami tahap konfirmasi oleh admin. Formulir juga menyediakan kolom catatan dan jadwal antar yang dibutuhkan admin dalam memproses pesanan.

### Gambar 4.5 — Detail Pesanan (Pelanggan) dengan Timeline Status

| Item | Isi |
|---|---|
| URL | `http://localhost:8000/pesanan/{kode_pesanan}` |
| Akun | `pelanggan@sinilaundry.test` |
| Langkah | Dari Beranda, klik kartu "Pesanan Aktif" (atau salah satu riwayat). |
| Yang terlihat | Kode pesanan, badge status, timeline status berjenjang, rincian item dan harga, total, status bayar, estimasi selesai. |

**Pembahasan:** Halaman detail pesanan menampilkan seluruh riwayat status dalam bentuk timeline visual, dari Diterima hingga Diambil. Setiap tahap menandai waktu dan petugas yang mengubah status, sehingga pelanggan memahami kapan dan oleh siapa cucian diproses—mengatasi masalah kurangnya transparansi pada sistem manual. Karena pesanan milik orang lain tidak dapat dibuka, halaman ini juga menerapkan otorisasi sehingga data pelanggan tetap terlindungi.

### Gambar 4.6 — Cek Status Publik

| Item | Isi |
|---|---|
| URL | `http://localhost:8000/cek-status` |
| Akun | — (tanpa login) |
| Langkah | Buka URL cek status, masukkan kode pesanan dan 4 digit terakhir nomor HP, klik Cek Status. |
| Yang terlihat | Formulir kode pesanan + 4 digit HP, hasil berupa badge status besar, timeline status, ringkasan layanan dan total, pesan ramah sesuai status (mis. "Cucianmu sudah selesai, silakan diambil!"). |

**Pembahasan:** Halaman cek status publik menjawab kebutuhan pelanggan pada Bab 2 untuk mengetahui status cucian tanpa harus bertanya ke admin. Pengguna hanya perlu kode pesanan dan empat digit terakhir nomor HP agar data pesanan tidak dapat ditebak oleh sembarang orang. Sistem juga membatasi lima percobaan per menit per alamat IP untuk mencegah penyalahgunaan, serta hanya menampilkan nama depan pelanggan agar data pribadi tetap aman.

### Gambar 4.7 — Pesanan Baru (Kasir)

| Item | Isi |
|---|---|
| URL | `http://localhost:8000/admin/pesanan/baru` |
| Akun | `admin@sinilaundry.test` |
| Langkah | Login sebagai admin, buka sidebar "Pesanan Baru", pilih pelanggan (atau tambah pelanggan baru), tambah satu atau lebih item layanan, isi berat. |
| Yang terlihat | Hasil pencarian pelanggan langsung, tombol Pelanggan Baru, baris item dinamis dengan ringkasan harga, kolom total, catatan, estimasi selesai, pembayaran awal opsional, tombol Simpan Pesanan. |

**Pembahasan:** Form pesanan baru dirancang agar kasir dapat mencatat pesanan walk-in dalam waktu kurang dari satu menit sesuai target pada Bab 2. Pencarian pelanggan langsung menampilkan hasil tanpa pindah halaman, dan tombol "Pelanggan Baru" mendaftarkan pelanggan walk-in tanpa akun. Seluruh harga dihitung otomatis oleh sistem dari berat dan layanan sehingga risiko salah hitung seperti pada pencatatan manual dapat diminimalkan.

### Gambar 4.8 — Daftar Pesanan dengan Filter Status

| Item | Isi |
|---|---|
| URL | `http://localhost:8000/admin/pesanan` |
| Akun | `admin@sinilaundry.test` |
| Langkah | Login sebagai admin, buka sidebar "Daftar Pesanan", klik tab status (mis. Dicuci) atau gunakan kolom pencarian. |
| Yang terlihat | Tab status dengan lencana jumlah pesanan, kolom pencarian kode/nama/HP, filter status bayar dan rentang tanggal, tabel/kartu pesanan (kode, pelanggan, layanan, berat, total, badge status, status bayar). |

**Pembahasan:** Daftar pesanan membantu admin menemukan pesanan dengan cepat pada saat toko sedang ramie, sebagaimana persona Pak Budi pada Bab 2. Tab status menampilkan lencana jumlah pesanan pada tiap tahap, dan tab "Menunggu Konfirmasi" diberi penanda visual agar pesanan online tidak terlewat. Tabel pada desktop dan kartu pada HP memastikan informasi tetap terbaca pada layar kecil, sementara pencarian dan filter memungkinkan admin melacak satu di antara puluhan pesanan.

### Gambar 4.9 — Detail Pesanan (Admin): Ubah Status dan Pembayaran

| Item | Isi |
|---|---|
| URL | `http://localhost:8000/admin/pesanan/{kode_pesanan}` |
| Akun | `admin@sinilaundry.test` |
| Langkah | Dari Daftar Pesanan, klik kode pesanan berstatus Dicuci. |
| Yang terlihat | Kode pesanan, badge status dan status bayar, info pelanggan dan pesanan, rincian item, timeline status, tombol "Lanjut ke Disetrika", riwayat pembayaran, formulir catat pembayaran dengan sisa tagihan, tombol Cetak Nota. |

**Pembahasan:** Halaman detail pesanan adalah pusat kendali admin dalam memproses cucian sesuai alur status pada Bab 2. Setiap perubahan status dicatat otomatis dengan waktu dan nama petugas sehingga riwayat dapat dilacak, dan tombol "Diambil" hanya aktif setelah pembayaran lunas untuk mencegah cucian hilang. Bagian pembayaran mencatat metode (tunai, transfer, QRIS), jumlah, dan sisa tagihan sehingga admin dapat memproses DP dan pelunasan dalam satu tempat tanpa menghitung manual.

### Gambar 4.10 — Nota/Struk Cetak

| Item | Isi |
|---|---|
| URL | `http://localhost:8000/admin/pesanan/{kode_pesanan}/nota` |
| Akun | `admin@sinilaundry.test` |
| Langkah | Dari detail pesanan, klik tombol "Cetak Nota". |
| Yang terlihat | Struk 80mm berisi nama usaha, alamat, telepon, kode pesanan, tanggal masuk, estimasi selesai, pelanggan, rincian item, total, pembayaran, sisa tagihan, status bayar, tautan cek status, tombol Cetak dan Kembali. |

**Pembahasan:** Nota digital menggantikan nota kertas yang mudah hilang atau tertukar sesuai masalah pada Bab 2. Struk dicetak dengan tata letak khusus kertas 80mm dan tetap rapi saat dicetak di A4, serta menampilkan tautan cek status agar pelanggan dapat memantau pesanan sendiri melalui HP. Dengan nota digital, seluruh data pesanan tetap tersimpan di sistem dan dapat dibuka kembali kapan saja tanpa bergantung pada kertas fisik.

### Gambar 4.11 — Dashboard Pemilik

| Item | Isi |
|---|---|
| URL | `http://localhost:8000/owner/dashboard` |
| Akun | `owner@sinilaundry.test` |
| Langkah | Login sebagai pemilik, buka sidebar "Dashboard". |
| Yang terlihat | Kartu statistik (pendapatan hari ini, pesanan aktif, selesai belum diambil, tagihan belum lunas), grafik pendapatan 7 hari terakhir, daftar "Perlu Perhatian", daftar 5 pesanan terbaru. |

**Pembahasan:** Dashboard pemilik menjawab kebutuhan pemilik usaha pada Bab 2 untuk melihat kinerja usaha tanpa menghitung nota satu per satu. Kartu statistik menyajikan angka utama secara langsung, grafik pendapatan tujuh hari terakhir menunjukkan tren penjualan, dan daftar "Perlu Perhatian" menandai pesanan yang memerlukan tindakan segera seperti cucian yang belum diambil lebih dari tiga hari. Seluruh angka diambil dari data pembayaran dan pesanan yang dicatat oleh admin sehingga selalu sesuai dengan kondisi toko.

### Gambar 4.12 — Laporan Pendapatan

| Item | Isi |
|---|---|
| URL | `http://localhost:8000/owner/laporan` |
| Akun | `owner@sinilaundry.test` |
| Langkah | Login sebagai pemilik, buka sidebar "Laporan", atur rentang tanggal (default bulan ini). |
| Yang terlihat | Ringkasan (total pendapatan, jumlah pesanan, rata-rata nilai, belum lunas), tabel pendapatan per hari, tabel layanan terlaris, rincian per metode pembayaran, tombol Cetak Laporan. |

**Pembahasan:** Laporan pendapatan menghasilkan rekap otomatis yang sebelumnya harus dihitung manual dari tumpukan nota, sesuai masalah keempat pada Bab 2. Pemilik dapat memilih rentang tanggal dan melihat total pendapatan, jumlah pesanan, dan layanan terlaris dalam satu halaman. Data diambil langsung dari pembayaran yang tercatat sehingga laporan dapat dipercaya sebagai dasar pengambilan keputusan usaha.

### Gambar 4.13 — Kelola Layanan dan Harga (Pemilik)

| Item | Isi |
|---|---|
| URL | `http://localhost:8000/owner/layanan` |
| Akun | `owner@sinilaundry.test` |
| Langkah | Login sebagai pemilik, buka sidebar "Layanan & Harga". |
| Yang terlihat | Tabel layanan (nama, satuan, harga, estimasi, status aktif), tombol Tambah Layanan, tombol Ubah, tombol Aktifkan/Nonaktifkan. |

**Pembahasan:** Halaman kelola layanan memungkinkan pemilik menyesuaikan harga dan layanan sesuai kebutuhan usaha, termasuk pengaturan layanan express dengan tambahan biaya. Harga yang diubah di sini langsung dipakai oleh sistem saat kasir menghitung total pesanan sehingga kesalahan harga dapat dihindari. Layanan yang dinonaktifkan tidak dihapus sehingga data historis pesanan lama tetap valid.

---

## Urutan Pengambilan Screenshot

1. Gambar 4.1 — Login (tamu)
2. Gambar 4.2 — Registrasi (tamu)
3. Gambar 4.3 — Beranda pelanggan (`pelanggan@sinilaundry.test`)
4. Gambar 4.4 — Pesan online (`pelanggan@sinilaundry.test`)
5. Gambar 4.5 — Detail pesanan pelanggan (`pelanggan@sinilaundry.test`)
6. Gambar 4.6 — Cek status publik (tamu)
7. Gambar 4.7 — Pesanan baru kasir (`admin@sinilaundry.test`)
8. Gambar 4.8 — Daftar pesanan (`admin@sinilaundry.test`)
9. Gambar 4.9 — Detail pesanan admin (`admin@sinilaundry.test`)
10. Gambar 4.10 — Nota (`admin@sinilaundry.test`)
11. Gambar 4.11 — Dashboard pemilik (`owner@sinilaundry.test`)
12. Gambar 4.12 — Laporan (`owner@sinilaundry.test`)
13. Gambar 4.13 — Kelola layanan (`owner@sinilaundry.test`)

> Catatan: untuk Gambar 4.5 dan 4.6, gunakan kode pesanan milik `pelanggan@sinilaundry.test`
> (lihat di Beranda). Untuk Gambar 4.9 dan 4.10, gunakan kode pesanan berstatus Dicuci
> dari Daftar Pesanan.
