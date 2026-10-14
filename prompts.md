# Prompts: Membangun Sini Laundry (Urut dari P0 sampai P22)

Kumpulan prompt siap tempel untuk AI coding assistant di VS Code (Copilot Chat, Claude Code, Cursor, dll). Tiap prompt dibuat mandiri dan mengacu ke dokumen di `docs/`.

## Cara Pakai

1. Taruh file ini di `docs/prompts.md` dan `CLAUDE.md` di root proyek.
2. Tempel prompt **satu per satu, berurutan**. Jangan loncat.
3. Setelah tiap prompt selesai:
   - jalankan migrasi bila ada (`php artisan migrate`) di **terminal Laragon**,
   - buka `http://localhost:8000` dan cek hasilnya,
   - jalankan `php artisan test`,
   - baru commit: `git add .` lalu `git commit -m "..."` (pesan commit disarankan di tiap prompt).
4. Kalau hasil meleset, jangan lanjut. Tempel pesan error atau screenshot ke AI dan minta perbaiki dulu.
5. Perintah `php`, `composer`, `npm` selalu di **terminal Laragon** (PHP 8.3). Terminal VS Code masih PHP 8.2.
6. Prompt yang menyentuh skema database sudah memuat izin eksplisit. Kalau AI minta izin untuk hal lain (hapus file, tambah dependency), baca dulu lalu putuskan sendiri.

## Urutan Ringkas

| Prompt | Isi | Tahap di tasks.md |
|---|---|---|
| P0 | Perkenalan dan survei proyek | 0 |
| P1 | Tema, font, warna, bahasa | 0 |
| P2 | Peran (role) dan akses | 1 |
| P3 | Layout dan navigasi per peran | 1 |
| P4 | Landing page | 1 |
| P5 | Layanan dan harga (owner) | 2 |
| P6 | Pelanggan (admin) | 2 |
| P7 | Skema pesanan, status, harga + tes | 3 |
| P8 | Form pesanan baru (kasir) | 3 |
| P9 | Daftar pesanan | 3 |
| P10 | Detail pesanan, status, pembayaran | 3, 4 |
| P11 | Nota/struk cetak | 4 |
| P12 | Cek status publik | 5 |
| P13 | Sisi pelanggan | 5 |
| P14 | Konfirmasi pesanan online (kasir) | 5 |
| P15 | Dashboard owner | 6 |
| P16 | Laporan pendapatan | 6 |
| P17 | Kelola pengguna (owner) | 6 |
| P18 | Data demo | 9 |
| P19 | Poles tampilan dan Bahasa Indonesia | 7 |
| P20 | Tes dan Pint | 7 |
| P21 | Audit keamanan | 7 |
| P22 | Bahan screenshot untuk laporan | 9 |

---

## P0: Perkenalan dan survei (tanpa kode)

```
Kamu membantu membangun aplikasi web "Sini Laundry" di proyek Laravel 13 ini
(PHP 8.3, starter kit Livewire 4 dengan Flux UI, Tailwind CSS, MySQL).

Baca dulu, secara berurutan:
CLAUDE.md, docs/PRD.md, docs/architecture.md, docs/style-guide.md,
docs/tasks.md, docs/workflow.md.

Lalu pelajari kode yang sudah ada:
routes/web.php, routes/settings.php, resources/views/layouts,
resources/views/pages (contoh: auth/login.blade.php dan settings/profile.blade.php),
resources/views/components, app/Models/User.php, app/Actions/Fortify,
config/fortify.php, vite.config.js, resources/css/app.css.

JANGAN menulis atau mengubah kode apa pun di langkah ini.

Jawab dengan ringkasan:
1. Konvensi komponen Livewire yang dipakai proyek ini (lokasi file, format single-file,
   cara mendaftarkan route).
2. Komponen Flux UI yang sudah dipakai dan tersedia.
3. Cara login/register dan redirect setelah login bekerja sekarang.
4. Fitur bawaan starter kit yang tidak dibutuhkan MVP (2FA, passkey, dll).
5. Rencana kerja singkat untuk Tahap 0 dan 1 di docs/tasks.md.
6. Pertanyaan atau risiko yang perlu saya putuskan.

Tunggu persetujuan saya sebelum menulis kode.
```

**Selesai jika:** AI memberi ringkasan yang masuk akal. Commit tidak perlu.

---

## P1: Tema, font, warna, bahasa

```
Kerjakan T0.4 dan T0.5 dari docs/tasks.md, sesuai docs/style-guide.md.

1. Identitas aplikasi:
   - APP_NAME="Sini Laundry" dan APP_LOCALE=id di .env dan .env.example
     (jangan memasukkan rahasia ke .env.example).
   - Timezone Asia/Jakarta di config/app.php.
   - Ganti semua teks "Laravel" yang tampil ke pengguna (judul tab, logo, header)
     menjadi "Sini Laundry". Buat logo teks sederhana dengan ikon gelembung/tetesan.

2. Warna: daftarkan token warna dari style-guide.md bagian 2 pada tema Tailwind
   (primary 50-900, aqua, ink, surface, plus warna status cucian).
   Cek dulu cara konfigurasi tema di versi Tailwind yang terpasang
   (lihat resources/css/app.css), ikuti caranya, jangan menebak.
   Sesuaikan warna aksen Flux UI agar memakai primary-600.

3. Font: ganti Instrument Sans bawaan menjadi Poppins (judul) dan Inter (isi).
   Lihat dulu bagaimana font dikonfigurasi di vite.config.js dan resources/views/partials/head.blade.php.
   Catatan: jaringan saya kadang timeout saat plugin font mengunduh dari internet.
   Jika font gagal diunduh, sediakan fallback system-ui dan JANGAN membuat build gagal.

4. Tampilan dasar: radius tombol/input 12px, radius kartu 16px, tinggi tombol minimal 44px,
   latar halaman warna surface.

Aturan: jangan menghapus file bawaan, jangan menambah dependency tanpa izin,
jangan mengubah migrasi. Setelah selesai, jawab dengan format di CLAUDE.md bagian 8
dan sebutkan perintah yang harus saya jalankan (misal npm run build).
```

**Selesai jika:** halaman login/register berwarna biru, font berubah, judul tab "Sini Laundry".
**Commit:** `feat(T0.5): apply Sini Laundry theme and fonts`

---

## P2: Peran (role) dan akses

```
Kerjakan T1.1 (sebagian), T1.2, T1.3, T1.4, dan T1.6 dari docs/tasks.md,
mengacu ke docs/architecture.md bagian 4 dan 5.

Saya memberi IZIN untuk membuat migrasi baru yang MENAMBAH kolom ke tabel users.
Jangan mengubah atau menghapus migrasi lama.

1. Enum App\Enums\Role: Customer, Admin, Owner (nilai: customer, admin, owner),
   dengan method label() Bahasa Indonesia.
2. Migrasi baru: tambah kolom `role` (default customer) dan `phone` (nullable, string 20)
   ke tabel users. Perbarui model User (cast enum, $fillable) dan helper:
   isCustomer(), isAdmin(), isOwner().
3. Registrasi: tambah field Nomor HP (wajib, format Indonesia sederhana) pada halaman register
   dan action CreateNewUser. Semua yang mendaftar lewat halaman publik otomatis role=customer.
   Role tidak boleh bisa diisi dari form (cegah mass assignment).
4. Middleware EnsureRole (alias `role`) yang menerima satu atau lebih peran.
   Daftarkan di bootstrap/app.php. Jika peran salah, kembalikan 403.
5. Redirect setelah login berdasarkan peran:
   customer ke /pesanan, admin ke /admin/pesanan, owner ke /owner/dashboard.
   Cari dulu di mana redirect login diatur oleh starter kit, lalu sesuaikan.
   Karena halaman tujuan belum ada, buat halaman placeholder sederhana per peran
   dengan route sesuai architecture.md bagian 7.
6. Seeder: owner@sinilaundry.test, admin@sinilaundry.test, pelanggan@sinilaundry.test,
   password "password" (HANYA untuk development, beri komentar peringatan).
   Daftarkan di DatabaseSeeder.
7. Tes (Pest/PHPUnit sesuai proyek): registrasi menghasilkan customer, customer tidak bisa membuka
   /admin/pesanan dan /owner/dashboard (403), admin tidak bisa membuka /owner/dashboard,
   redirect login per peran benar.

Setelah selesai, beri tahu saya perintah: php artisan migrate lalu php artisan db:seed.
Format jawaban sesuai CLAUDE.md bagian 8.
```

**Selesai jika:** login sebagai tiga akun seeder masuk ke halaman berbeda; `php artisan test` hijau.
**Commit:** `feat(T1): add roles, role middleware, seeded accounts`

---

## P3: Layout dan navigasi per peran

```
Kerjakan T1.5 dari docs/tasks.md, sesuai docs/style-guide.md bagian 6.

Gunakan layout dan komponen Flux yang sudah ada di resources/views/layouts sebagai dasar.
Jangan menghapus layout bawaan.

1. Navigasi per peran:
   - Pelanggan: bottom navigation di HP (Beranda, Pesanan, Cek Status, Akun),
     header sederhana di desktop.
   - Admin: sidebar (desktop) / menu geser (HP): Pesanan Baru, Daftar Pesanan, Pelanggan.
   - Owner: sidebar: Dashboard, Laporan, Layanan & Harga, Pengguna,
     ditambah menu admin yang sama (Pesanan Baru, Daftar Pesanan, Pelanggan).
   Menu yang tampil hanya sesuai peran. Item aktif diberi highlight primary.
2. Komponen Blade/Flux yang akan dipakai ulang di halaman berikutnya (buat di resources/views/components):
   - status-badge (menerima status pesanan, warna dan ikon sesuai style-guide.md bagian 2)
   - stat-card (judul, angka, ikon, keterangan)
   - page-header (judul + deskripsi + slot aksi)
   - empty-state (ikon/ilustrasi sederhana, teks, tombol aksi)
   - format uang dengan helper: Rp 25.000 (buat helper/komponen, simpan angka sebagai integer)
   Karena enum OrderStatus belum ada, buat status-badge menerima string status dulu dan
   siapkan peta warnanya di satu tempat.
3. Semua teks Bahasa Indonesia. Responsif 360px sampai desktop.
4. Sembunyikan (jangan hapus) tautan fitur starter kit yang tidak dipakai MVP,
   seperti passkey dan 2FA, dari menu pengaturan. Halaman Profil dan Ganti Password tetap ada.

Jangan menambah dependency. Format jawaban sesuai CLAUDE.md bagian 8.
```

**Selesai jika:** tiga akun melihat menu berbeda; tampilan HP rapi (coba lebar jendela sempit).
**Commit:** `feat(T1.5): add role-based layouts and shared components`

---

## P4: Landing page

```
Ganti halaman resources/views/welcome.blade.php menjadi landing page Sini Laundry
sesuai docs/PRD.md dan docs/style-guide.md. Mobile-first, bahasa Indonesia.

Bagian halaman:
1. Navbar: logo, Cek Status, Masuk, Daftar.
2. Hero: judul "Cucianmu sudah sampai mana? Cek langsung di Sini.",
   subjudul singkat, tombol utama "Pesan Laundry" (ke register/login) dan tombol
   sekunder "Cek Status Cucian" (ke /cek-status). Gunakan ilustrasi sederhana
   dari CSS/SVG bertema gelembung sabun (tanpa gambar eksternal).
3. "Cara kerja" 4 langkah: Pesan, Kami Cuci, Pantau Status, Ambil.
4. Layanan dan harga: ambil dari tabel services jika sudah ada;
   jika belum, tampilkan data statis sementara dan beri komentar TODO untuk P5.
5. Keunggulan: tanpa nota hilang, status transparan, harga otomatis, laporan rapi
   (kartu dengan ikon).
6. Footer: nama usaha, alamat dan jam buka sebagai teks placeholder.

Aturan: jangan memuat gambar atau skrip dari internet; jangan menambah dependency.
Tombol dan kontras mengikuti style-guide.md. Format jawaban sesuai CLAUDE.md bagian 8.
```

**Commit:** `feat: add landing page`

---

## P5: Layanan dan harga (owner)

```
Kerjakan T2.1, T2.2, dan T2.4 dari docs/tasks.md, sesuai docs/architecture.md bagian 5.2.

Saya memberi IZIN membuat migrasi BARU untuk tabel services.

1. Migrasi, model, factory, seeder untuk services:
   name, unit (enum kg/pcs, buat Enum ServiceUnit), price (unsignedInteger, rupiah),
   duration_hours, is_express (bool), is_active (bool), timestamps.
2. Seeder data awal:
   - Cuci Kering Lipat, kg, 6000, 48 jam
   - Cuci + Setrika, kg, 8000, 48 jam
   - Setrika Saja, kg, 5000, 24 jam
   - Express Cuci + Setrika, kg, 8000, 6 jam, is_express=true
   - Selimut, pcs, 25000, 72 jam
   - Jaket, pcs, 20000, 72 jam
3. Biaya tambahan Express: buat config/laundry.php dengan
   express_surcharge_percent (default 50) dan min_weight_kg (default 1).
   Pengaturannya lewat config dulu (antarmuka pengaturan masuk Fase 2).
4. Halaman owner /owner/layanan (Livewire): daftar layanan sebagai tabel di desktop dan kartu di HP,
   tombol Tambah, Ubah, aktif/nonaktif (bukan hapus permanen), validasi form
   (nama wajib, harga angka positif, durasi angka positif), pesan error Bahasa Indonesia.
   Akses hanya owner (middleware role).
5. Landing page (P4): ganti data statis layanan dengan data dari tabel (hanya yang aktif).
6. Tes: owner bisa tambah dan ubah layanan, admin dan customer mendapat 403.

Beri tahu perintah migrate dan seed. Format jawaban sesuai CLAUDE.md bagian 8.
```

**Commit:** `feat(T2): add services management for owner`

---

## P6: Pelanggan (admin)

```
Kerjakan T2.3 dari docs/tasks.md, sesuai docs/architecture.md bagian 5.2.

Saya memberi IZIN membuat migrasi BARU untuk tabel customers.

1. Migrasi, model, factory: customers
   (user_id nullable FK ke users, name, phone indexed, address nullable, timestamps).
   Relasi: Customer belongsTo User (nullable), User hasOne Customer.
2. Saat user dengan role customer mendaftar atau login pertama kali dan belum punya baris customers,
   buat otomatis dari name dan phone-nya. Jika nomor HP sudah ada di customers walk-in,
   hubungkan (isi user_id) alih-alih membuat duplikat.
3. Halaman admin /admin/pelanggan (Livewire), akses admin dan owner:
   daftar dengan pencarian nama/HP dan paginasi, tombol Tambah Pelanggan (walk-in tanpa akun),
   Ubah. Hapus tidak diizinkan jika sudah punya pesanan (nonaktifkan saja atau sembunyikan tombol).
4. Validasi nomor HP: format Indonesia sederhana (08..., +62..., 62...), normalisasi disimpan
   ke format satu bentuk (misal 08xxxxxxxxxx) lewat method/helper di satu tempat.
5. Tes: normalisasi HP, penautan akun ke pelanggan walk-in, otorisasi.

Beri tahu perintah migrate. Format jawaban sesuai CLAUDE.md bagian 8.
```

**Commit:** `feat(T2.3): add customers`

---

## P7: Skema pesanan, status, harga + tes

```
Kerjakan T3.1, T3.2, T3.3, T3.7 (bagian logika), dan T3.8 (bagian logika) dari docs/tasks.md.
Ikuti docs/architecture.md bagian 5 dan 6, serta aturan bisnis di docs/PRD.md bagian 7.

Saya memberi IZIN membuat migrasi BARU untuk: orders, order_items, order_status_logs, payments.

1. Enum: OrderStatus (pending_confirmation, received, washing, ironing, done, picked_up, cancelled)
   dengan label() Indonesia, urutan, method next() dan canTransitionTo(); PaymentMethod
   (cash, transfer, qris); PaymentStatus (unpaid, partial, paid); OrderSource (walk_in, online).
2. Migrasi, model, relasi, factory untuk keempat tabel sesuai kolom di architecture.md 5.2.
   Uang integer rupiah. Indeks sesuai 5.4.
3. App\Services\OrderCodeGenerator: SL-YYYYMMDD-NNN (urutan per hari, aman dari duplikat,
   di dalam transaksi, zona waktu Asia/Jakarta).
4. App\Services\OrderPricingService:
   - berat dibulatkan ke atas per 0,5 kg, minimal sesuai config laundry.min_weight_kg untuk layanan kg
   - subtotal = berat dibulatkan x harga layanan
   - layanan is_express: tambah express_surcharge_percent dari config/laundry.php
   - total = jumlah subtotal; unit_price disimpan sebagai snapshot di order_items
   - layanan pcs: kuantitas bulat, tanpa pembulatan 0,5
5. App\Services\OrderStatusService:
   - hanya boleh maju sesuai urutan
   - mundur hanya role owner dan wajib catatan
   - pembatalan hanya sebelum washing
   - pesanan picked_up dan paid tidak boleh diubah
   - setiap perubahan membuat baris order_status_logs dalam satu transaksi
6. App\Services\PaymentService: catat pembayaran, hitung ulang payment_status
   (paid jika total bayar >= total_price, partial jika ada tapi kurang, selain itu unpaid).
7. OrderPolicy sesuai tabel hak akses di architecture.md bagian 4.
8. Tes unit/feature yang lengkap: pembulatan (0,3 -> min 1; 1,2 -> 1,5; 2,0 -> 2,0),
   express, layanan pcs, perpindahan status valid dan tidak valid, pembatalan,
   rollback oleh owner, status pembayaran, kode pesanan tidak kembar.

Aturan: logika ada di Services, bukan di komponen. Jalankan php artisan test dan pastikan lulus.
Beri tahu perintah migrate. Format jawaban sesuai CLAUDE.md bagian 8.
```

**Selesai jika:** `php artisan migrate` sukses, `php artisan test` hijau, tabel baru ada di HeidiSQL.
**Commit:** `feat(T3): add order schema and business services with tests`

---

## P8: Form pesanan baru (kasir)

```
Kerjakan T3.4 dari docs/tasks.md. Halaman /admin/pesanan/baru (Livewire), akses admin dan owner.
Tampilan mengikuti docs/style-guide.md; prioritas: CEPAT diisi kasir (target di bawah 1 menit).

Isi halaman:
1. Pilih pelanggan: kolom pencarian nama/HP dengan hasil langsung; tombol "Pelanggan baru"
   yang membuka form ringkas (nama, HP) tanpa pindah halaman dan menyimpan sebagai pelanggan walk-in.
2. Item pesanan: baris dinamis, tiap baris pilih layanan (hanya yang aktif) dan isi berat (kg)
   atau jumlah (pcs). Tombol tambah/hapus baris. Input berat memakai keypad numerik di HP.
3. Ringkasan di sisi/bawah: tiap baris menampilkan berat dibulatkan, harga satuan, subtotal,
   serta total. Perhitungan memakai OrderPricingService (jangan menghitung ulang di komponen).
   Total ikut berubah langsung saat input diubah.
4. Catatan pesanan (opsional), estimasi selesai otomatis dari durasi layanan terpanjang
   (bisa diubah).
5. Pembayaran awal (opsional): metode dan jumlah (DP atau lunas) lewat PaymentService.
6. Simpan: dalam satu transaksi buat order (source walk_in, status received, created_by = admin),
   order_items, log status awal, dan pembayaran jika ada. Redirect ke detail pesanan
   (/admin/pesanan/{order}); jika halaman detail belum ada, tampilkan halaman sementara.
7. Validasi lengkap dengan pesan Indonesia (pelanggan wajib, minimal satu item, berat > 0).
8. Tes feature: pesanan walk-in berhasil dibuat dengan total benar, validasi gagal bila tidak ada item.

Format jawaban sesuai CLAUDE.md bagian 8.
```

**Commit:** `feat(T3.4): add cashier new order form`

---

## P9: Daftar pesanan

```
Kerjakan T3.5. Halaman /admin/pesanan (Livewire), akses admin dan owner.

1. Tabel di desktop, kartu pesanan di HP (komponen sesuai style-guide.md:
   kode pesanan monospace, nama pelanggan, layanan ringkas, berat, total Rp, status-badge,
   status bayar, waktu update). Seluruh kartu/baris bisa diklik ke detail.
2. Filter: tab status (Semua, Menunggu Konfirmasi, Diterima, Dicuci, Disetrika, Selesai, Diambil,
   Dibatalkan), filter status bayar, filter rentang tanggal.
3. Pencarian: kode pesanan atau nama/HP pelanggan.
4. Paginasi 15 per halaman, urutan terbaru dulu.
5. Tombol cepat "Pesanan Baru" dan lencana jumlah per tab (hitung dengan satu query efisien).
6. Hindari N+1 (eager load). Keadaan kosong memakai komponen empty-state.
7. Tes: filter dan pencarian mengembalikan data yang benar; customer mendapat 403.

Format jawaban sesuai CLAUDE.md bagian 8.
```

**Commit:** `feat(T3.5): add order list with filters`

---

## P10: Detail pesanan, status, pembayaran

```
Kerjakan T3.6, T3.7 (UI), T3.8 (UI), T4.1. Halaman /admin/pesanan/{order} (Livewire),
akses admin dan owner. Gunakan OrderStatusService dan PaymentService yang sudah ada.

1. Header: kode pesanan, status-badge, status bayar, tombol Cetak Nota (ke P11 nanti;
   sementara tombol nonaktif atau tautan placeholder).
2. Info pelanggan (nama, HP, alamat), info pesanan (sumber, dibuat oleh, estimasi selesai, catatan).
3. Tabel item: layanan, berat/jumlah, harga satuan, subtotal; total di bawah.
4. Timeline status vertikal sesuai style-guide.md (titik per tahap, tahap selesai berwarna primary,
   tahap berikutnya abu-abu, tahap aktif berdenyut halus; hormati prefers-reduced-motion),
   menampilkan waktu dan petugas dari order_status_logs.
5. Tombol "Lanjut ke [status berikutnya]" yang jelas dan besar. Setelah `done`, tombol
   "Tandai Diambil" hanya aktif jika sudah lunas atau tampilkan peringatan dan opsi mencatat
   pembayaran dulu.
6. Pembatalan: tombol Batalkan dengan dialog konfirmasi, hanya tampil sebelum Dicuci.
7. Owner saja: tombol "Mundurkan status" dengan catatan wajib.
8. Pembayaran: daftar pembayaran (waktu, metode, jumlah, penerima), form Catat Pembayaran
   (metode, jumlah, default = sisa tagihan), tampilkan sisa tagihan dan status bayar.
9. Toast sukses/gagal. Semua aksi lewat service, dengan Policy dicek.
10. Tes: alur status lengkap dari received sampai picked_up, pembayaran sebagian lalu lunas,
    admin tidak bisa mundurkan status.

Format jawaban sesuai CLAUDE.md bagian 8.
```

**Commit:** `feat(T3.6): add order detail with status flow and payments`

---

## P11: Nota/struk cetak

```
Kerjakan T4.2 dan T4.3. Route /admin/pesanan/{order}/nota, akses admin dan owner.

1. Halaman nota HTML khusus cetak (tanpa sidebar/layout aplikasi), lebar kertas 80mm
   (struk thermal) dan tetap rapi saat dicetak di A4. Gunakan CSS @media print.
2. Isi: nama usaha (Sini Laundry), alamat dan telepon (placeholder dari config/laundry.php),
   kode pesanan, tanggal masuk, estimasi selesai, nama pelanggan, daftar item
   (layanan, berat, harga, subtotal), total, pembayaran, sisa tagihan, status bayar,
   dan teks ucapan terima kasih.
3. Tombol Cetak (window.print) dan Kembali; hanya tombol yang disembunyikan saat cetak.
4. Kode QR berisi tautan ke /cek-status?kode=KODEPESANAN. JANGAN menambah dependency
   tanpa izin: jika butuh paket QR, hentikan dan ajukan permintaan izin lengkap dengan
   nama paket dan alasannya (format di docs/workflow.md bagian 3). Alternatif tanpa paket:
   tampilkan tautan teks dan kode pesanan besar sebagai pengganti sementara.
5. Hubungkan tombol Cetak Nota di halaman detail pesanan (P10).
6. Tes: halaman nota hanya bisa dibuka admin/owner dan memuat kode pesanan dan total.

Format jawaban sesuai CLAUDE.md bagian 8.
```

**Commit:** `feat(T4): add printable receipt`

---

## P12: Cek status publik

```
Kerjakan T5.4 dan T5.5. Route publik /cek-status (Livewire), tanpa login. Lebar maksimum 480px,
mobile-first, ramah dibuka dari scan QR.

1. Form: Kode Pesanan dan 4 digit terakhir nomor HP. Jika URL berisi ?kode=..., isi otomatis
   kolom kode (tetap minta 4 digit HP).
2. Verifikasi: kode cocok dan 4 digit terakhir HP pelanggan cocok. Jika salah, tampilkan
   pesan generik yang sama ("Data tidak ditemukan, periksa kembali") agar kode tidak bisa ditebak.
3. Rate limiting: maksimal 5 percobaan per menit per IP (gunakan RateLimiter Laravel),
   tampilkan pesan Indonesia saat terkena batas.
4. Hasil: status-badge besar, timeline status (tanpa nama petugas), layanan ringkas dan total,
   status bayar, estimasi selesai. JANGAN menampilkan alamat lengkap, nomor HP penuh,
   atau nama lengkap (tampilkan inisial atau nama depan saja).
5. Pesan ramah per status, misal "Cucianmu sudah selesai, silakan diambil!" untuk done.
6. Tombol "Cek pesanan lain".
7. Tes: kombinasi benar menampilkan status, kombinasi salah menampilkan pesan generik,
   percobaan ke-6 diblokir, data sensitif tidak muncul di respons.

Format jawaban sesuai CLAUDE.md bagian 8.
```

**Commit:** `feat(T5): add public order status check`

---

## P13: Sisi pelanggan

```
Kerjakan T5.1, T5.2, T5.3 (sisi pelanggan). Semua halaman akses role customer, mobile-first,
dengan bottom navigation dari P3.

1. /pesanan (Beranda pelanggan): sapaan nama, kartu "Pesanan Aktif" (status-badge dan
   perkiraan selesai), tombol besar "Pesan Laundry", daftar riwayat pesanan.
2. /pesanan/baru: form pesan online. Pilih satu atau lebih layanan, isi ESTIMASI berat
   (kg) atau jumlah (pcs), pilih tanggal antar ke toko, catatan. Tampilkan perkiraan harga dengan
   OrderPricingService dan beri keterangan jelas bahwa harga final menunggu penimbangan.
   Simpan sebagai order source=online, status=pending_confirmation, terhubung ke customer milik user
   (buat otomatis jika belum ada).
3. /pesanan/{order}: detail milik sendiri (Policy: tidak boleh membuka pesanan orang lain),
   timeline status, item, total, status bayar, tombol "Batalkan" hanya saat
   pending_confirmation.
4. Tab/halaman "Akun" mengarah ke pengaturan profil bawaan starter kit.
5. Tes: customer hanya melihat pesanannya sendiri, pembuatan pesanan online benar,
   pembatalan hanya saat pending_confirmation.

Format jawaban sesuai CLAUDE.md bagian 8.
```

**Commit:** `feat(T5): add customer dashboard and online ordering`

---

## P14: Konfirmasi pesanan online (kasir)

```
Lengkapi alur pesanan online di sisi admin (T5.3).

1. Di /admin/pesanan, tab "Menunggu Konfirmasi" menonjol (lencana jumlah berwarna).
2. Di detail pesanan berstatus pending_confirmation, tampilkan panel
   "Terima Cucian dan Timbang": form berat/jumlah aktual per item (diisi awal dengan estimasi
   pelanggan), pilihan ubah layanan, total dihitung ulang lewat OrderPricingService, dan tombol
   "Konfirmasi dan Terima" yang mengubah status ke received lewat OrderStatusService
   (item dan total diperbarui dalam satu transaksi, log status tercatat).
3. Tombol "Tolak" dengan alasan wajib yang mengubah status ke cancelled.
4. Tes: konfirmasi menghitung ulang total sesuai berat aktual; tolak mencatat alasan.

Format jawaban sesuai CLAUDE.md bagian 8.
```

**Commit:** `feat(T5.3): add admin confirmation for online orders`

---

## P15: Dashboard owner

```
Kerjakan T6.1. Halaman /owner/dashboard (Livewire), akses owner.

1. Kartu statistik (komponen stat-card): pendapatan hari ini (jumlah payments hari ini),
   pesanan aktif, pesanan selesai belum diambil, total tagihan belum lunas.
2. Grafik pendapatan 7 hari terakhir. JANGAN menambah library grafik: buat batang sederhana dengan
   CSS/Tailwind, lengkap dengan label hari dan angka Rp, kontras sesuai style-guide.md.
3. Daftar "Perlu Perhatian": pesanan selesai belum diambil lebih dari 3 hari, pesanan
   menunggu konfirmasi, pesanan belum lunas.
4. Daftar 5 pesanan terbaru dengan tautan ke detail.
5. Query efisien (agregasi di database), tanpa N+1. Zona waktu Asia/Jakarta untuk "hari ini".
6. Tes: angka dashboard sesuai data factory.

Format jawaban sesuai CLAUDE.md bagian 8.
```

**Commit:** `feat(T6.1): add owner dashboard`

---

## P16: Laporan pendapatan

```
Kerjakan T6.2 dan T6.3. Halaman /owner/laporan (Livewire), akses owner.

1. Filter: rentang tanggal (default bulan ini) dan pintasan: Hari ini, 7 hari, Bulan ini, Bulan lalu.
2. Ringkasan: total pendapatan (dari payments), jumlah pesanan, rata-rata nilai pesanan,
   total belum lunas.
3. Tabel pendapatan per hari (tanggal, jumlah pesanan, pendapatan) dan total di bawah.
4. Tabel layanan terlaris (nama layanan, jumlah pesanan, total berat/kuantitas, pendapatan).
5. Rincian per metode pembayaran (tunai, transfer, QRIS).
6. Tombol Cetak laporan (CSS print rapi). Ekspor Excel/PDF TIDAK dikerjakan sekarang (Fase 2).
7. Query efisien dengan agregasi; hindari memuat semua pesanan ke memori.
8. Tes: angka laporan sesuai data factory untuk rentang tanggal tertentu.

Format jawaban sesuai CLAUDE.md bagian 8.
```

**Commit:** `feat(T6): add revenue report`

---

## P17: Kelola pengguna (owner)

```
Halaman /owner/pengguna (Livewire), akses owner, sesuai docs/PRD.md dan style-guide.md.

1. Daftar pengguna dengan filter peran dan pencarian nama/email.
2. Owner bisa membuat akun admin baru (nama, email, HP, password awal) dan mengatur ulang
   password admin. Role admin dan owner TIDAK bisa dibuat lewat halaman Register publik.
3. Owner bisa menonaktifkan/mengaktifkan akun admin. Saya memberi IZIN membuat migrasi BARU
   untuk menambah kolom is_active (default true) ke users; akun nonaktif tidak bisa login
   (tampilkan pesan Indonesia).
4. Owner tidak bisa menonaktifkan atau mengubah perannya sendiri.
5. Tes: pembuatan admin, akun nonaktif ditolak login, proteksi diri sendiri.

Format jawaban sesuai CLAUDE.md bagian 8.
```

**Commit:** `feat: add user management for owner`

---

## P18: Data demo (untuk screenshot dan presentasi)

```
Kerjakan skill #10 di docs/skill.md. Buat DemoDataSeeder terpisah (tidak dipanggil otomatis
oleh DatabaseSeeder; dijalankan dengan php artisan db:seed --class=DemoDataSeeder):

- 10 pelanggan dengan nama Indonesia realistis dan nomor HP fiktif
- 30 pesanan tersebar dalam 30 hari terakhir dengan status bervariasi (semua tahap),
  sebagian online, sebagian walk-in
- pembayaran bervariasi: lunas, DP, belum bayar, metode campur
- riwayat status (order_status_logs) konsisten dengan status akhir
- semua lewat service/factory yang ada supaya total dan kode pesanan valid
- semua data fiktif; jangan pakai data orang nyata

Seeder harus aman dijalankan ulang (hapus data demo lama atau lewati bila sudah ada).
Beri tahu perintahnya. Format jawaban sesuai CLAUDE.md bagian 8.
```

**Commit:** `chore: add demo data seeder`

---

## P19: Poles tampilan dan Bahasa Indonesia

```
Kerjakan T7.2 dan T7.3 dari docs/tasks.md.

1. Audit seluruh halaman terhadap docs/style-guide.md: warna, font, radius, jarak,
   tinggi tombol 44px, kontras, ikon konsisten (satu set), keadaan kosong, keadaan loading,
   pesan error yang menjelaskan solusi.
2. Terjemahkan semua teks yang masih Inggris ke Bahasa Indonesia: halaman login, register,
   lupa/reset password, pengaturan profil, dashboard bawaan, tombol, pesan validasi
   (tambahkan file bahasa Indonesia untuk validasi; jika butuh paket, ajukan izin dulu).
3. Format konsisten: Rp 25.000, tanggal "14 Okt 2026, 15:30 WIB".
4. Responsif: cek lebar 360px, 768px, 1280px. Tabel melebar harus bisa digulir horizontal
   atau berubah jadi kartu. Tidak ada elemen terpotong.
5. Aksesibilitas dasar: label pada semua input, fokus terlihat, alt/aria pada ikon aksi,
   hormati prefers-reduced-motion.
6. Dashboard bawaan starter kit (resources/views/dashboard.blade.php) dialihkan ke halaman
   sesuai peran atau diganti, supaya tidak ada halaman "Laravel" yang tersisa.

Laporkan daftar perubahan per halaman. Jangan mengubah logika bisnis. Format jawaban sesuai
CLAUDE.md bagian 8.
```

**Commit:** `style(T7): polish UI and localize to Indonesian`

---

## P20: Tes dan Pint

```
Kerjakan T7.1 dan T7.4.

1. Jalankan ./vendor/bin/pint dan perbaiki gaya kode.
2. Jalankan php artisan test. Perbaiki tes yang gagal; tes bawaan starter kit yang
   tidak relevan lagi (misal karena redirect berubah) disesuaikan, jangan dihapus tanpa izin.
3. Tambahkan tes otorisasi menyeluruh: untuk tiap route di docs/architecture.md bagian 7,
   periksa peran yang boleh dan yang harus 403/redirect.
4. Tambahkan tes alur end-to-end: pelanggan memesan online, admin mengonfirmasi dan menimbang,
   status berjalan sampai picked_up dengan pembayaran lunas, owner melihat pendapatan di laporan.
5. Laporkan hasil akhir: jumlah tes, yang lulus, dan area yang belum tercakup.

Format jawaban sesuai CLAUDE.md bagian 8.
```

**Commit:** `test(T7): add authorization and end-to-end tests`

---

## P21: Audit keamanan (tanpa mengubah kode dulu)

```
Gunakan template skill #6 di docs/skill.md pada SELURUH proyek.

Periksa: otorisasi tiap route dan aksi Livewire (termasuk method publik komponen yang bisa
dipanggil dari klien), validasi input, mass assignment, SQL mentah, output Blade tanpa escape,
rahasia ter-hardcode atau ikut ter-commit (cek juga git history), rate limiting cek status publik
dan login, data yang terekspos di halaman publik, properti Livewire publik yang bisa dimanipulasi
(ID pesanan, harga, role), serta konfigurasi APP_DEBUG untuk produksi.

Keluaran: daftar temuan dari paling berbahaya, tiap temuan berisi lokasi file, penjelasan,
dan saran perbaikan. JANGAN mengubah kode; tunggu persetujuan saya per temuan.
```

**Setelah itu:** minta AI memperbaiki temuan satu per satu, lalu commit `fix(security): ...`.

---

## P22: Bahan screenshot untuk laporan (Bab 4)

```
Bantu saya menyiapkan Bab 4 (Pembahasan/Implementasi) laporan SIM.

1. Pastikan data demo (P18) sudah terisi.
2. Buat daftar urut screenshot yang harus saya ambil, mengikuti 12 layar di
   docs/style-guide.md bagian 11. Untuk tiap layar tulis: URL, akun login yang dipakai,
   langkah menuju tampilan, dan hal yang harus terlihat jelas.
3. Untuk tiap layar, tulis satu paragraf pembahasan Bahasa Indonesia formal akademik
   (3-5 kalimat): fungsi layar, pengguna, elemen utama, dan hubungannya dengan kebutuhan
   di Bab 2. Mengacu ke fitur yang benar-benar ada di aplikasi (jangan mengarang fitur).
4. Susun dalam satu file docs/bab4-draft.md dengan penomoran gambar
   ("Gambar 4.1 Halaman Login", dst.).

Jangan mengubah kode aplikasi.
```

---

## Jika AI Macet atau Hasilnya Berantakan

| Masalah | Tindakan |
|---|---|
| Error setelah prompt | Tempel pesan error lengkap, minta cari penyebab dulu sebelum mengubah kode (skill #8) |
| AI mengubah banyak file di luar tugas | `git checkout .` untuk membatalkan yang belum di-commit, ulangi dengan prompt lebih sempit |
| AI menebak API Livewire 4/Flux | Minta ia membaca contoh di `resources/views/pages` dan dokumentasi resmi dulu |
| Tampilan tidak sesuai desain | Kutip bagian `docs/style-guide.md` yang dilanggar |
| Migrasi bermasalah | Jangan `migrate:fresh` di database yang sudah berisi data penting tanpa persetujuan tim |
| Terlalu lama | Lewati prompt P17 dan P19 sementara; fokus P0 sampai P13 dan laporan SIM |

Ingat: nilai tugas ada di rancangan, diagram, dan mockup. Kalau waktu mepet, kerjakan laporan dulu.
