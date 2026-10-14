# Tasks: Sini Laundry

Daftar pekerjaan berurutan. Centang `[x]` kalau sudah selesai. Kerjakan dari atas ke bawah, jangan lompat ke Fase 2 sebelum MVP beres.

**Legenda:** `P0` wajib, `P1` penting, `P2` nilai tambah. Pembagian anggota: **A** = backend dan database, **B** = frontend dan mockup, **C** = laporan dan diagram.

---

## Tahap 0: Persiapan (P0)

- [x] **T0.1** Install PHP 8.3+, Composer, Node.js, MySQL, Git (lihat `INSTALL.md`) `A`
- [x] **T0.2** Buat project Laravel 13 dengan starter kit Livewire `A`
- [x] **T0.3** Buat repository Git dan push ke GitHub, undang semua anggota `A`
- [x] **T0.4** Atur `.env` (database, `APP_NAME`, locale `id`) dan timezone `Asia/Jakarta` di `config/app.php` `A`
- [x] **T0.5** Pasang token warna dan font sesuai `style-guide.md` `B`
- [ ] **T0.6** Buat file Figma bersama, siapkan halaman per peran `B`

## Tahap 1: Fondasi (P0)

- [ ] **T1.1** Migrasi dan model: `users` (+ kolom `role`, `phone`), `customers`, `services` `A`
  - Catatan: kolom `users` selesai (P2); tabel `customers` dan `services` dikerjakan di T2.3 dan T2.1.
- [ ] **T1.2** Enum `Role` dan `OrderStatus` `A`
  - Catatan: Enum `Role` selesai (P2); `OrderStatus` dibuat di T3.1.
- [x] **T1.3** Autentikasi: login, registrasi pelanggan, logout, reset password `A`
- [x] **T1.4** Middleware/otorisasi berbasis peran (customer, admin, owner) `A`
- [x] **T1.5** Layout dasar per peran (pelanggan, admin, owner) `B`
- [x] **T1.6** Seeder: akun contoh tiap peran + data layanan awal `A`
  - Catatan: akun contoh selesai; data layanan awal menyusul di T2.1.

## Tahap 2: Master Data (P0)

- [x] **T2.1** CRUD layanan (nama, satuan, harga, estimasi jam, aktif/nonaktif) `A`
- [x] **T2.2** Halaman kelola layanan untuk owner `B`
- [x] **T2.3** CRUD pelanggan untuk admin (termasuk pelanggan walk-in tanpa akun) `A`
  - Catatan: hapus tidak disediakan; pelanggan yang sudah punya pesanan dilindungi lewat relasi orders.
- [x] **T2.4** Pengaturan harga Express (persentase tambahan) `A`
  - Catatan: `config/laundry.php` (express_surcharge_percent, min_weight_kg); antarmuka pengaturan Fase 2.

## Tahap 3: Pesanan (P0)

- [x] **T3.1** Migrasi dan model: `orders`, `order_items`, `order_status_logs`, `payments` `A`
- [x] **T3.2** Generator kode pesanan unik (`SL-YYYYMMDD-NNN`) `A`
- [x] **T3.3** Service class `OrderPricingService`: hitung harga, pembulatan 0,5 kg, biaya Express `A`
- [x] **T3.4** Form pesanan baru untuk admin (cari pelanggan, pilih layanan, berat, total otomatis) `B`
  - Catatan: simpan pesanan dialihkan ke daftar pesanan; pengalihan ke detail menyusul di T3.6.
- [x] **T3.5** Daftar pesanan: filter status, pencarian kode/nama, paginasi `B`
- [x] **T3.6** Detail pesanan: item, total, riwayat status `B`
- [x] **T3.7** Ubah status sesuai urutan dan catat ke `order_status_logs` `A` (logika + UI)
- [x] **T3.8** Pembatalan pesanan (hanya sebelum `Dicuci`) `A` (logika + UI)

## Tahap 4: Pembayaran dan Nota (P0)

- [x] **T4.1** Catat pembayaran (tunai, transfer, QRIS), hitung status bayar (Belum Bayar, DP, Lunas) `A`
- [x] **T4.2** Nota/struk yang bisa dicetak atau disimpan PDF `B`
  - Catatan: nota HTML 80mm siap cetak via window.print; QR diganti tautan teks + kode pesanan besar (tanpa paket eksternal).
- [ ] **T4.3** QR pada nota yang mengarah ke halaman cek status `A` (alternatif tanpa paket sudah ada; QR gambar menunggu paket berizin)

## Tahap 5: Sisi Pelanggan (P1)

- [x] **T5.1** Beranda pelanggan: pesanan aktif dan riwayat `B`
- [x] **T5.2** Form pesan online (layanan, estimasi berat, jadwal) `B`
- [x] **T5.3** Alur konfirmasi pesanan online oleh admin (status `Menunggu Konfirmasi`) `A`
  - Catatan: sisi pelanggan (P13) + panel "Terima Cucian dan Timbang" dan "Tolak" di kasir (P14).
- [x] **T5.4** Halaman cek status publik (kode pesanan + 4 digit terakhir HP) `B`
- [x] **T5.5** Pembatasan percobaan cek status (rate limiting) `A`
  - Catatan: maksimal 5 percobaan per menit per IP (RateLimiter Laravel), pesan Bahasa Indonesia.

## Tahap 6: Laporan Owner (P1)

- [x] **T6.1** Dashboard owner: pendapatan hari ini, pesanan aktif, pesanan selesai `B`
- [x] **T6.2** Laporan pendapatan harian dan bulanan (filter tanggal) `A`
- [x] **T6.3** Layanan terlaris dan jumlah pesanan per periode `A`

## Tahap 7: Kualitas (P1)

- [x] **T7.1** Tes otomatis untuk perhitungan harga dan alur status `A`
- [x] **T7.2** Validasi form dan pesan error berbahasa Indonesia `B`
- [x] **T7.3** Cek tampilan di HP (360px) dan desktop `B`
- [x] **T7.4** Cek otorisasi: pelanggan tidak bisa membuka halaman admin/owner `A`
  - Catatan: AuthorizationTest menutupi seluruh route di architecture.md §7.
- [ ] **T7.4** Cek otorisasi: pelanggan tidak bisa membuka halaman admin/owner `A`

## Kelola Pengguna (owner)

- [x] **P17** Halaman `/owner/pengguna`: daftar pengguna (filter peran + cari), buat akun admin,
  reset password admin, aktif/nonaktif admin; kolom `users.is_active`; akun nonaktif tidak bisa login;
  owner tidak bisa menonaktifkan/mengubah dirinya sendiri.

## Tahap 8: Fase 2 (P2, jika waktu cukup)

- [ ] **T8.1** Notifikasi WhatsApp/email saat status `Selesai` `A`
- [ ] **T8.2** Pembayaran QRIS dinamis lewat payment gateway `A`
- [ ] **T8.3** Antar-jemput dan jadwal kurir `A`
- [ ] **T8.4** Poin loyalti dan voucher `A`
- [ ] **T8.5** Grafik analitik dan ekspor Excel/PDF `B`
- [ ] **T8.6** Pengingat cucian belum diambil `A`
- [ ] **T8.7** Mode gelap `B`

## Tahap 9: Dokumen Laporan SIM (kerjakan paralel, P0)

- [ ] **T9.1** Cover dan Bab 1 Pendahuluan (draf sudah ada) `C`
- [ ] **T9.2** Bab 2 Analisis Permasalahan dan Pengguna Sistem `C`
- [ ] **T9.3** Flowchart alur sistem dari awal sampai akhir `C`
- [ ] **T9.4** Use Case Diagram `C`
- [ ] **T9.5** Activity Diagram (pesanan walk-in, pesanan online, cek status) `C`
- [ ] **T9.6** Sequence Diagram (buat pesanan, ubah status, cek status) `C`
- [ ] **T9.7** Mockup UI 12 layar (lihat `style-guide.md` bagian 11) `B`
- [ ] **T9.8** Bab 4 Pembahasan/Implementasi: tampilkan mockup atau screenshot dan jelaskan `B` `C`
- [ ] **T9.9** Bab 5 Kesimpulan dan Saran (saran = fitur Fase 2) `C`
- [ ] **T9.10** Daftar Pustaka `C`
- [ ] **T9.11** Final: samakan istilah, nomor gambar, dan format dengan ketentuan dosen `C`

---

## Definisi Selesai (Definition of Done)

Sebuah task dianggap selesai jika:
1. Berfungsi sesuai `PRD.md`.
2. Tampilan sesuai `style-guide.md`.
3. Tidak ada error di browser maupun log Laravel.
4. Sudah di-commit dengan pesan yang jelas.
5. Dicek minimal satu anggota lain.

## Catatan Prioritas

Jika waktu mepet, **laporan SIM (Tahap 9) lebih penting daripada kode**. Tugas dinilai dari rancangan, diagram, dan mockup. Aplikasi yang berjalan adalah bonus.
