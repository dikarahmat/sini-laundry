# PRD: Sini Laundry

> Product Requirements Document. Dokumen ini adalah sumber kebenaran untuk **apa** yang dibangun dan **kenapa**.
> Untuk **bagaimana**, lihat `architecture.md`. Untuk urutan pengerjaan, lihat `tasks.md`.

| Item | Isi |
|---|---|
| Nama produk | Sini Laundry |
| Jenis | Sistem informasi laundry berbasis web (responsif, mobile-first) |
| Mata kuliah | Sistem Informasi Manajemen (SIM) |
| Tim | Kelompok 3 orang |
| Stack | Laravel 13, PHP 8.3+, Livewire 4, Tailwind CSS, MySQL |
| Status | Draft v1.0 |

---

## 1. Ringkasan

Sini Laundry adalah aplikasi web untuk mengelola usaha laundry kiloan dari pemesanan sampai pengambilan. Pelanggan bisa memesan dan memantau status cucian secara online, admin/kasir mencatat dan memperbarui pesanan dengan cepat, dan pemilik mendapat laporan pendapatan otomatis.

**Satu kalimat:** "Cucianmu sudah sampai mana? Cek langsung di Sini."

## 2. Masalah yang Diselesaikan

| # | Masalah (kondisi manual) | Dampak |
|---|---|---|
| 1 | Pesanan dicatat di nota kertas atau buku | Nota hilang, tulisan tidak terbaca, cucian tertukar |
| 2 | Harga dihitung manual | Salah hitung, pelanggan komplain |
| 3 | Pelanggan harus datang atau chat untuk tanya status | Admin terganggu, pelanggan bolak-balik |
| 4 | Rekap pendapatan dari tumpukan nota | Laporan lambat dan kurang akurat |
| 5 | Tidak ada riwayat pelanggan | Sulit memberi promo atau melacak komplain |

## 3. Tujuan Produk

1. Menggantikan nota kertas dengan pencatatan digital yang terstruktur.
2. Memberi pelanggan transparansi status cucian tanpa harus bertanya.
3. Menghitung harga otomatis berdasarkan berat dan jenis layanan.
4. Menghasilkan laporan pendapatan harian dan bulanan secara otomatis.
5. Menjadi bahan rancangan lengkap untuk tugas SIM (flowchart, UML, mockup).

## 4. Target Pengguna

| Peran | Siapa | Kebutuhan utama |
|---|---|---|
| **Pelanggan** | Mahasiswa, pekerja, keluarga | Pesan laundry, pantau status, lihat riwayat dan tagihan, dapat notifikasi saat selesai |
| **Admin/Kasir** | Karyawan laundry | Input pesanan cepat, hitung harga otomatis, update status, cetak nota, catat pembayaran |
| **Pemilik** | Pemilik usaha | Kelola layanan dan harga, lihat laporan pendapatan, pantau performa |

### Persona singkat

- **Rina, mahasiswi (21).** Tidak punya waktu bolak-balik ke laundry. Mau tahu kapan cucian selesai tanpa chat berkali-kali.
- **Pak Budi, admin laundry (35).** Pelanggan ramai di sore hari. Butuh input pesanan dalam hitungan detik, bukan menulis nota satu per satu.
- **Bu Sari, pemilik (45).** Mau tahu pendapatan hari ini tanpa menghitung nota di akhir hari.

## 5. Ruang Lingkup Fitur

### 5.1 MVP (wajib ada)

**Autentikasi dan peran**
- Login, registrasi pelanggan, logout, reset password.
- Tiga peran: `customer`, `admin`, `owner`, dengan hak akses terpisah.

**Master data layanan (owner)**
- CRUD layanan: Cuci Kiloan, Cuci + Setrika, Setrika Saja, Express (kilat), Cuci Satuan (selimut, jaket, dll).
- Atribut layanan: nama, satuan (kg/pcs), harga, estimasi durasi (jam).

**Pemesanan**
- Admin input pesanan untuk pelanggan walk-in (pelanggan boleh belum punya akun).
- Pelanggan membuat pesanan online (pilih layanan, estimasi berat, jadwal antar atau datang langsung).
- Harga dihitung otomatis dari berat dan layanan.
- Kode pesanan unik, contoh `SL-20261014-001`.

**Status cucian**
- Alur status: `Diterima` → `Dicuci` → `Disetrika` → `Selesai` → `Diambil`. Pesanan juga bisa `Dibatalkan`.
- Setiap perubahan status tercatat (siapa dan kapan).

**Cek status publik**
- Halaman tanpa login: masukkan kode pesanan + 4 digit terakhir nomor HP untuk melihat status.

**Pembayaran**
- Admin mencatat pembayaran: tunai, transfer, QRIS (dicatat manual di MVP).
- Status bayar: `Belum Bayar`, `DP`, `Lunas`.

**Nota dan struk**
- Nota digital yang bisa dicetak atau disimpan sebagai PDF, lengkap dengan kode pesanan dan QR untuk cek status.

**Laporan (owner)**
- Pendapatan harian dan bulanan, jumlah pesanan, layanan terlaris.

### 5.2 Fase 2 (nilai tambah, kerjakan jika waktu cukup)

| Fitur | Deskripsi |
|---|---|
| Notifikasi WhatsApp/email | Pesan otomatis saat status berubah menjadi Selesai |
| Pembayaran QRIS dinamis | Integrasi payment gateway, status bayar otomatis |
| Antar-jemput | Pelanggan minta jemput, admin atur jadwal kurir |
| Poin loyalti dan voucher | Poin per transaksi, tukar diskon |
| Dashboard analitik | Grafik tren pendapatan, jam sibuk, pelanggan setia |
| Ekspor laporan | Unduh Excel dan PDF |
| Pengingat cucian belum diambil | Notifikasi otomatis setelah X hari |
| Ringkasan AI | Ringkasan laporan mingguan dalam bahasa natural (opsional) |

### 5.3 Di luar ruang lingkup

- Aplikasi native Android/iOS (cukup web responsif).
- Manajemen karyawan dan penggajian.
- Multi-cabang.
- Akuntansi lengkap (buku besar, pajak).

## 6. Alur Utama

**Alur A: pesanan walk-in**
1. Pelanggan datang membawa cucian.
2. Admin login, pilih "Pesanan Baru", cari atau tambah pelanggan.
3. Admin menimbang cucian, memilih layanan, sistem menghitung total.
4. Admin simpan, sistem membuat kode pesanan dan nota.
5. Admin mengubah status sepanjang proses sampai Selesai.
6. Pelanggan mengambil cucian dan membayar, status menjadi Diambil dan Lunas.

**Alur B: pesanan online**
1. Pelanggan login, pilih layanan, isi estimasi berat dan jadwal.
2. Pesanan masuk dengan status `Menunggu Konfirmasi`.
3. Admin mengonfirmasi, menimbang ulang saat cucian diterima, harga final dihitung.
4. Lanjut seperti Alur A langkah 5-6.

**Alur C: cek status**
1. Pelanggan membuka halaman cek status atau scan QR di nota.
2. Memasukkan kode pesanan dan 4 digit terakhir HP.
3. Sistem menampilkan status terkini dan riwayat perubahannya.

## 7. Aturan Bisnis

1. Harga final = berat aktual (hasil timbang admin) x harga layanan, bukan estimasi pelanggan.
2. Berat dibulatkan ke atas per 0,5 kg. Minimal berat per pesanan: 1 kg.
3. Layanan Express dikenakan tambahan biaya (default +50%, bisa diatur owner).
4. Status hanya boleh maju sesuai urutan. Mundur hanya boleh oleh owner dengan alasan tercatat.
5. Pesanan yang sudah `Diambil` dan `Lunas` tidak boleh diubah, hanya dilihat.
6. Pesanan hanya bisa dibatalkan sebelum status `Dicuci`.
7. Mata uang Rupiah (IDR), zona waktu Asia/Jakarta.

## 8. Kebutuhan Non-Fungsional

| Aspek | Target |
|---|---|
| Performa | Halaman utama dimuat di bawah 2 detik pada koneksi 4G |
| Responsif | Nyaman dipakai di HP (lebar 360px) sampai desktop |
| Keamanan | Password di-hash, proteksi CSRF, validasi input, akses berbasis peran |
| Privasi | Cek status publik hanya menampilkan data minimum (tanpa alamat lengkap) |
| Aksesibilitas | Kontras warna memadai, label form jelas, ukuran tombol minimal 44px |
| Keandalan | Data tersimpan di database relasional dengan backup rutin |
| Bahasa | Antarmuka Bahasa Indonesia |

## 9. Metrik Keberhasilan

- Waktu input satu pesanan oleh admin di bawah 1 menit.
- Pertanyaan "cucian saya sudah selesai?" ke admin berkurang (diganti cek status mandiri).
- Laporan bulanan tersedia otomatis, tanpa rekap manual.
- Nol nota hilang atau tertukar.

## 10. Risiko dan Mitigasi

| Risiko | Mitigasi |
|---|---|
| Lingkup terlalu besar untuk waktu tugas | Kerjakan MVP dulu, Fase 2 hanya jika sempat |
| Anggota belum familiar Laravel | Pakai starter kit Livewire, ikuti `tasks.md` berurutan |
| Integrasi WhatsApp/QRIS rumit | Ditandai Fase 2, cukup dijelaskan sebagai rencana pengembangan |
| Diagram tidak konsisten dengan fitur | Selalu rujuk bagian 5 dan 6 dokumen ini saat membuat UML |

## 11. Asumsi

- Usaha berskala kecil, satu cabang, satu sampai tiga admin.
- Pelanggan memiliki nomor HP aktif.
- Aplikasi diakses lewat browser (HP atau komputer).

## 12. Pemetaan ke Laporan SIM

| Bagian laporan | Sumber di dokumen ini |
|---|---|
| Bab 1 Pendahuluan | Bagian 2, 3 |
| Bab 2 Analisis Sistem | Bagian 2, 4 |
| Bab 3 Desain Sistem (flowchart, UML) | Bagian 5, 6, 7 |
| Bab 4 Implementasi | Bagian 5.1 + `architecture.md` + `style-guide.md` |
| Bab 5 Penutup | Bagian 9, 5.2 (saran pengembangan) |
