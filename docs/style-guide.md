# Style Guide: Sini Laundry

Panduan tampilan agar semua halaman dan mockup konsisten. Kesan yang dituju: **bersih, segar, tepercaya, ringan** (seperti pakaian baru dicuci).

## 1. Prinsip Desain

1. **Mobile-first.** Pelanggan dan admin paling sering memakai HP.
2. **Satu layar, satu tujuan.** Jangan menumpuk banyak aksi di satu halaman.
3. **Status selalu terlihat.** Status cucian adalah informasi terpenting, tampilkan dengan warna dan ikon, bukan teks saja.
4. **Cepat untuk kasir.** Form input pesanan harus bisa diisi hanya dengan sedikit ketukan.
5. **Ruang kosong itu fitur.** Beri jarak lega, jangan padat.

## 2. Warna

Gunakan token berikut (Tailwind CSS). Nilai hex untuk Figma.

### Warna utama

| Token | Hex | Penggunaan |
|---|---|---|
| `primary-50` | `#EFF8FF` | Latar halus, hover baris |
| `primary-100` | `#DBEEFE` | Badge latar, highlight |
| `primary-500` | `#2B9BF4` | Tombol sekunder, link |
| `primary-600` | `#1479D6` | **Warna merek**, tombol utama |
| `primary-700` | `#1260AD` | Hover tombol utama |
| `primary-900` | `#0F3E73` | Teks judul gelap, sidebar |

### Warna aksen

| Token | Hex | Penggunaan |
|---|---|---|
| `aqua-400` | `#2DD4BF` | Aksen segar, grafik, ilustrasi |
| `ink-900` | `#0F172A` | Teks utama |
| `ink-600` | `#475569` | Teks sekunder |
| `ink-300` | `#CBD5E1` | Border |
| `surface` | `#F8FAFC` | Latar halaman |
| `white` | `#FFFFFF` | Kartu, form |

### Warna status cucian

| Status | Latar | Teks | Ikon (saran) |
|---|---|---|---|
| Menunggu Konfirmasi | `#F1F5F9` | `#475569` | jam |
| Diterima | `#DBEEFE` | `#1260AD` | kotak masuk |
| Dicuci | `#E0F2FE` | `#0369A1` | gelembung/mesin cuci |
| Disetrika | `#FEF3C7` | `#92400E` | setrika |
| Selesai | `#DCFCE7` | `#166534` | centang |
| Diambil | `#E2E8F0` | `#334155` | tas/paket |
| Dibatalkan | `#FEE2E2` | `#991B1B` | silang |

### Warna sistem

| Fungsi | Hex |
|---|---|
| Sukses | `#16A34A` |
| Peringatan | `#F59E0B` |
| Bahaya/error | `#DC2626` |
| Info | `#2B9BF4` |

Aturan: jangan menyampaikan arti hanya lewat warna. Selalu sertakan teks atau ikon. Rasio kontras teks minimal 4.5:1.

## 3. Tipografi

| Peran | Font | Fallback |
|---|---|---|
| Judul dan heading | **Poppins** (SemiBold 600) | `ui-sans-serif, system-ui` |
| Isi dan form | **Inter** (Regular 400, Medium 500) | `ui-sans-serif, system-ui` |
| Angka/kode pesanan | **JetBrains Mono** atau Inter tabular | `ui-monospace` |

| Level | Ukuran | Berat | Tinggi baris |
|---|---|---|---|
| H1 | 28px (mobile 24px) | 600 | 1.2 |
| H2 | 22px | 600 | 1.3 |
| H3 | 18px | 600 | 1.4 |
| Body | 16px | 400 | 1.6 |
| Caption/label | 13px | 500 | 1.4 |

## 4. Jarak, Radius, dan Bayangan

| Elemen | Nilai |
|---|---|
| Skala jarak | kelipatan 4px (4, 8, 12, 16, 24, 32, 48) |
| Padding kartu | 16px (mobile), 24px (desktop) |
| Radius tombol dan input | `rounded-xl` (12px) |
| Radius kartu | `rounded-2xl` (16px) |
| Radius badge status | `rounded-full` |
| Bayangan kartu | `shadow-sm`, hover `shadow-md` |
| Lebar konten maksimum | 1200px (admin), 480px (halaman cek status publik) |

## 5. Komponen

### Tombol

| Jenis | Gaya |
|---|---|
| Primary | Latar `primary-600`, teks putih, hover `primary-700`, tinggi 44px |
| Secondary | Latar putih, border `primary-600`, teks `primary-600` |
| Ghost | Tanpa border, teks `primary-600`, hover latar `primary-50` |
| Danger | Latar `#DC2626`, teks putih (hanya untuk hapus/batal) |

Semua tombol: radius 12px, tinggi minimal 44px, status disabled dengan opasitas 50%, fokus dengan ring `primary-500`.

### Input form

- Label di atas input, ukuran 13px medium.
- Tinggi 44px, border `ink-300`, radius 12px, fokus ring `primary-500`.
- Pesan error di bawah input, warna `#DC2626`, 13px.
- Input berat (kg) dan harga memakai keypad numerik di HP.

### Kartu pesanan

Isi: kode pesanan (mono), nama pelanggan, layanan, berat, total (Rp), badge status, waktu update. Seluruh kartu bisa diketuk.

### Badge status

Pil kecil dengan ikon + teks, warna sesuai tabel status di atas.

### Timeline status

Garis vertikal dengan titik per tahap. Tahap selesai berwarna `primary-600`, tahap berikutnya abu-abu, tahap aktif berdenyut halus.

### Tabel (admin dan laporan)

Header latar `primary-50`, baris selang-seling tipis, di HP berubah menjadi daftar kartu.

### Notifikasi dan toast

Muncul di kanan atas (desktop) atau atas layar (HP), hilang otomatis dalam 4 detik, sertakan ikon sesuai jenis.

## 6. Navigasi dan Tata Letak

| Peran | Pola navigasi |
|---|---|
| Pelanggan | Bottom navigation (HP): Beranda, Pesanan, Cek Status, Akun |
| Admin | Sidebar (desktop) / menu geser (HP): Pesanan Baru, Daftar Pesanan, Pelanggan, Pembayaran |
| Owner | Sidebar: Dashboard, Laporan, Layanan & Harga, Pengguna |

## 7. Ikon dan Ilustrasi

- Set ikon: **Lucide** atau Heroicons (outline, tebal garis 1.5-2px), satu set saja.
- Ilustrasi: gaya datar, warna biru-aqua, tema gelembung sabun, keranjang, mesin cuci. Hanya untuk halaman kosong dan landing.

## 8. Gerak (Animasi)

- Durasi 150-250ms, easing ease-out.
- Transisi halus pada hover, perubahan status, dan toast.
- Hormati `prefers-reduced-motion`: matikan animasi bila pengguna memintanya.

## 9. Mode Gelap (opsional)

Latar `#0B1220`, kartu `#111B2E`, teks `#E2E8F0`, warna merek dinaikkan satu tingkat (`primary-500`). Jadikan Fase 2.

## 10. Konten dan Nada Bahasa

- Bahasa Indonesia yang ramah dan lugas, gunakan "kamu" untuk pelanggan.
- Tombol berupa kata kerja: "Buat Pesanan", "Simpan", "Cek Status".
- Pesan error menjelaskan solusi: "Berat harus lebih dari 0 kg", bukan "Input tidak valid".
- Format: `Rp 25.000`, tanggal `14 Okt 2026, 15:30 WIB`.

## 11. Daftar Layar untuk Mockup (Figma)

| # | Layar | Peran |
|---|---|---|
| 1 | Login / Registrasi | Semua |
| 2 | Beranda pelanggan | Pelanggan |
| 3 | Buat pesanan online | Pelanggan |
| 4 | Detail pesanan + timeline status | Pelanggan |
| 5 | Cek status publik | Publik |
| 6 | Pesanan baru (kasir) | Admin |
| 7 | Daftar pesanan + filter status | Admin |
| 8 | Detail pesanan + ubah status + pembayaran | Admin |
| 9 | Nota/struk | Admin |
| 10 | Dashboard owner | Owner |
| 11 | Laporan pendapatan | Owner |
| 12 | Kelola layanan dan harga | Owner |

## 12. Konfigurasi Tailwind (acuan implementasi)

Tambahkan token warna pada konfigurasi tema Tailwind proyek, mengikuti tabel warna di atas (`primary`, `aqua`, `ink`). Cek dokumentasi Tailwind versi yang dipakai starter kit karena cara konfigurasi tema dapat berbeda antarversi.
