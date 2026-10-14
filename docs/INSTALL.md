# INSTALL: Menjalankan Sini Laundry di Komputer Lokal

Panduan dari nol sampai halaman login Laravel tampil di browser, lalu memasukkan file dokumentasi ini ke proyek. Cocok untuk Windows (utama), dengan catatan untuk macOS/Linux.

Waktu yang dibutuhkan: sekitar 30-45 menit, tergantung koneksi internet.

---

## 0. Yang Dibutuhkan

| Alat | Versi | Fungsi |
|---|---|---|
| PHP | **8.3 atau lebih baru** (syarat Laravel 13) | Menjalankan Laravel |
| Composer | terbaru | Pengelola paket PHP |
| Node.js + npm | LTS terbaru | Build Tailwind dan Vite |
| Database | SQLite (bawaan) atau MySQL | Penyimpanan data |
| Git | terbaru | Versi kode |
| Code editor | VS Code | Menulis kode |

Cara termudah mendapat PHP + Composer + Node sekaligus: **Laravel Herd** (Opsi A). Alternatif populer: **Laragon** (Opsi B).

---

## 1. Opsi A: Laravel Herd (paling mudah)

1. Unduh **Laravel Herd** dari situs resmi `herd.laravel.com`, pilih Windows atau macOS, lalu install seperti biasa.
2. Buka Herd, pastikan PHP yang aktif **8.3 atau lebih baru** (pengaturan PHP di Herd, pilih 8.3/8.4/8.5).
3. Herd memasang PHP, Composer, Node, dan Laravel installer, serta menyediakan folder proyek (biasanya `Herd` di folder user Anda).
4. Install **Git** dari `git-scm.com` bila belum ada.
5. Untuk **MySQL**: versi gratis Herd tidak selalu menyertakan layanan database. Anda punya dua pilihan:
   - Pakai **SQLite** (tidak perlu install apa-apa), cocok untuk tahap awal.
   - Install MySQL lewat **DBngin** (gratis), XAMPP, atau Laragon.

## 1. Opsi B: Laragon (populer di Indonesia, sudah ada MySQL)

1. Unduh **Laragon Full** dari `laragon.org` dan install.
2. Pastikan versi PHP bawaannya **8.3+**. Jika masih lebih lama, tambahkan PHP 8.3+ lewat menu Laragon (Menu > PHP > pilih versi / unduh versi baru), lalu aktifkan.
3. Klik **Start All** agar Apache/Nginx dan MySQL berjalan.
4. Buka terminal Laragon (tombol **Terminal**), semua perintah di bawah dijalankan dari sana.
5. Install **Node.js** LTS dari `nodejs.org` bila belum ada, dan **Git** dari `git-scm.com`.

> Jangan menjalankan Laragon dan Herd sekaligus. Keduanya memakai port yang sama dan bisa bentrok.

---

## 2. Cek Semua Sudah Terpasang

Buka terminal (PowerShell, Terminal Laragon, atau Terminal Herd), lalu jalankan:

```bash
php -v
composer -V
node -v
npm -v
git --version
```

Hasil yang diharapkan: PHP menunjukkan `8.3.x` atau lebih tinggi. Jika PHP masih `8.2` atau lebih rendah, Laravel 13 tidak bisa dipasang. Ganti versi PHP dulu di Herd/Laragon, lalu **tutup dan buka ulang terminal**.

---

## 3. Pasang Laravel Installer (jika belum ada)

Cek dulu:

```bash
laravel --version
```

Jika perintah tidak dikenali (biasanya kalau memakai Laragon), jalankan:

```bash
composer global require laravel/installer
```

Lalu pastikan folder global Composer ada di PATH (Windows: `%APPDATA%\Composer\vendor\bin`). Tutup dan buka ulang terminal, lalu cek `laravel --version`.

> Jika tidak mau memasang installer, pakai cara cadangan di langkah 4 (composer create-project).

---

## 4. Buat Proyek Sini Laundry

Pindah ke folder tempat proyek akan disimpan, lalu buat proyek.

- Herd: folder `Herd` di dalam folder user Anda
- Laragon: `C:\laragon\www`
- Lainnya: bebas, misalnya `C:\Projects`

```bash
cd C:\laragon\www          # sesuaikan dengan folder Anda
laravel new sini-laundry
```

Installer akan menanyakan beberapa pilihan. Pilih:

| Pertanyaan | Pilih |
|---|---|
| Starter kit | **Livewire** |
| Autentikasi | Autentikasi bawaan Laravel (bukan WorkOS) |
| Framework tes | Pest atau PHPUnit (bebas, Pest biasanya default) |
| Database | SQLite untuk mulai cepat, atau MySQL jika sudah siap |
| Jalankan `npm install` dan build | **Yes** |

Teks pertanyaan bisa sedikit berbeda antarversi installer. Pilih opsi yang paling mirip dengan tabel di atas.

**Cara cadangan (tanpa installer):**

```bash
composer create-project laravel/laravel sini-laundry
cd sini-laundry
composer require livewire/livewire
npm install
```

Cara ini menghasilkan Laravel polos tanpa halaman login siap pakai. Untuk tugas ini, **starter kit Livewire lebih disarankan** karena login, registrasi, dan reset password langsung tersedia.

---

## 5. Atur Database

### Opsi 1: SQLite (paling cepat)

Biasanya sudah otomatis. Pastikan di `.env`:

```env
DB_CONNECTION=sqlite
```

Jalankan migrasi:

```bash
php artisan migrate
```

Jika muncul pertanyaan membuat file database SQLite, jawab **Yes**.

### Opsi 2: MySQL

1. Pastikan MySQL menyala (Laragon: Start All, XAMPP: Start MySQL).
2. Buat database kosong bernama `sini_laundry` lewat phpMyAdmin, HeidiSQL (ada di Laragon), atau terminal MySQL:

```sql
CREATE DATABASE sini_laundry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Buka file `.env` di root proyek, ubah bagian database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sini_laundry
DB_USERNAME=root
DB_PASSWORD=
```

Isi `DB_PASSWORD` jika MySQL Anda memakai password (Laragon dan XAMPP biasanya kosong).

4. Jalankan migrasi:

```bash
php artisan migrate
```

---

## 6. Pengaturan Dasar (Bahasa dan Zona Waktu)

Buka `.env`, ubah:

```env
APP_NAME="Sini Laundry"
APP_LOCALE=id
APP_FALLBACK_LOCALE=en
```

Buka `config/app.php`, cari baris `'timezone'` dan ubah menjadi:

```php
'timezone' => 'Asia/Jakarta',
```

> Terjemahan pesan validasi Bahasa Indonesia mungkin perlu ditambahkan terpisah (misalnya lewat paket bahasa Laravel). Itu bisa dikerjakan belakangan di task T7.2.

---

## 7. Jalankan Aplikasi

Di folder proyek:

```bash
composer run dev
```

Perintah ini biasanya menjalankan server Laravel, antrean, log, dan Vite sekaligus. Biarkan terminal tetap terbuka.

Buka browser: **http://localhost:8000** (atau alamat yang tampil di terminal).

- Jika memakai **Herd**, aplikasi biasanya otomatis tersedia di **http://sini-laundry.test**.
- Jika memakai **Laragon**, bisa juga otomatis di **http://sini-laundry.test** (aktifkan Auto Virtual Hosts di Laragon).

Jika `composer run dev` tidak ada atau error, jalankan manual di **dua terminal**:

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

Coba buka halaman **Register**, buat akun, lalu login. Jika berhasil masuk ke dashboard, instalasi sukses.

---

## 8. Masukkan File Dokumentasi ke Proyek

File `.md` yang Anda unduh dari chat (`PRD.md`, `style-guide.md`, `tasks.md`, `architecture.md`, `agent.md`, `workflow.md`, `skill.md`, `INSTALL.md`) diletakkan seperti ini:

```
sini-laundry/
  CLAUDE.md          <- salinan agent.md (untuk Claude Code)
  AGENTS.md          <- salinan agent.md (untuk alat AI lain, opsional)
  docs/
    PRD.md
    style-guide.md
    tasks.md
    architecture.md
    workflow.md
    skill.md
    INSTALL.md
```

**Windows (PowerShell)**, anggap file ada di folder `Downloads\sini-laundry-docs`:

```powershell
cd C:\laragon\www\sini-laundry          # sesuaikan
mkdir docs
copy "$HOME\Downloads\sini-laundry-docs\PRD.md" docs\
copy "$HOME\Downloads\sini-laundry-docs\style-guide.md" docs\
copy "$HOME\Downloads\sini-laundry-docs\tasks.md" docs\
copy "$HOME\Downloads\sini-laundry-docs\architecture.md" docs\
copy "$HOME\Downloads\sini-laundry-docs\workflow.md" docs\
copy "$HOME\Downloads\sini-laundry-docs\skill.md" docs\
copy "$HOME\Downloads\sini-laundry-docs\INSTALL.md" docs\
copy "$HOME\Downloads\sini-laundry-docs\agent.md" CLAUDE.md
copy "$HOME\Downloads\sini-laundry-docs\agent.md" AGENTS.md
```

**macOS/Linux:**

```bash
cd ~/Herd/sini-laundry
mkdir docs
cp ~/Downloads/sini-laundry-docs/{PRD,style-guide,tasks,architecture,workflow,skill,INSTALL}.md docs/
cp ~/Downloads/sini-laundry-docs/agent.md CLAUDE.md
cp ~/Downloads/sini-laundry-docs/agent.md AGENTS.md
```

Karena file dipindah ke folder `docs/`, ubah rujukan di `CLAUDE.md`/`AGENTS.md` bagian 1 menjadi `docs/PRD.md`, `docs/architecture.md`, dan seterusnya. Jika nama file download Anda berbeda (misalnya hasil unduh zip), sesuaikan path-nya.

---

## 9. Git dan GitHub

```bash
git init
git add .
git commit -m "chore: initial Laravel 13 + Livewire project"
```

Pastikan `.env` **tidak** ikut ter-commit (sudah ada di `.gitignore` bawaan Laravel). Cek dengan `git status`, file `.env` tidak boleh muncul.

Buat repository kosong di GitHub, lalu:

```bash
git branch -M main
git remote add origin https://github.com/USERNAME/sini-laundry.git
git push -u origin main
```

Anggota lain menyalin proyek dengan:

```bash
git clone https://github.com/USERNAME/sini-laundry.git
cd sini-laundry
composer install
npm install
copy .env.example .env        # macOS/Linux: cp .env.example .env
php artisan key:generate
php artisan migrate
composer run dev
```

---

## 10. Perintah Harian

| Tujuan | Perintah |
|---|---|
| Jalankan aplikasi | `composer run dev` |
| Migrasi database | `php artisan migrate` |
| Isi data contoh | `php artisan db:seed` |
| Reset database (HAPUS SEMUA DATA) | `php artisan migrate:fresh --seed` |
| Buat model + migrasi + factory | `php artisan make:model Order -mf` |
| Lihat daftar rute | `php artisan route:list` |
| Jalankan tes | `php artisan test` |
| Rapikan gaya kode | `./vendor/bin/pint` |
| Bersihkan cache | `php artisan optimize:clear` |
| Lihat log error | file `storage/logs/laravel.log` |

Hati-hati dengan `migrate:fresh`, perintah ini menghapus seluruh data. Sesuai `workflow.md`, minta persetujuan tim sebelum memakainya pada database bersama.

---

## 11. Masalah Umum

| Masalah | Solusi |
|---|---|
| `composer` atau `php` tidak dikenali | Tutup dan buka ulang terminal, pastikan PHP/Composer ada di PATH, atau pakai terminal bawaan Herd/Laragon |
| Composer menolak karena versi PHP | Laravel 13 butuh PHP 8.3+. Ganti versi PHP aktif, buka ulang terminal, cek `php -v` |
| Ekstensi PHP kurang (mbstring, openssl, pdo_mysql, fileinfo, dll.) | Aktifkan di `php.ini` (hapus tanda `;` di depan `extension=...`), restart server |
| `SQLSTATE[HY000] [2002]` saat migrasi | MySQL belum menyala atau `DB_HOST`/`DB_PORT` salah |
| `Access denied for user` | `DB_USERNAME`/`DB_PASSWORD` di `.env` tidak cocok |
| `Unknown database 'sini_laundry'` | Database belum dibuat, buat dulu (langkah 5) |
| Halaman tampil tanpa gaya (CSS hilang) | `npm run dev` belum berjalan, atau jalankan `npm run build` |
| `Vite manifest not found` | Jalankan `npm install` lalu `npm run build` |
| Error `No application encryption key` | Jalankan `php artisan key:generate` |
| Port 8000 dipakai aplikasi lain | `php artisan serve --port=8001` |
| Perubahan `.env` tidak terbaca | `php artisan config:clear`, lalu restart server |
| `npm install` gagal/lama | Cek koneksi, pastikan Node versi LTS, hapus `node_modules` lalu ulangi |
| Herd dan Laragon bentrok | Jalankan salah satu saja |

---

## 12. Setelah Instalasi Berhasil

1. Buka `docs/tasks.md`, mulai dari **Tahap 0** dan **Tahap 1**.
2. Kerjakan satu task per branch Git (lihat `workflow.md`).
3. Jika memakai AI coding assistant, mulai dengan template nomor 1 di `skill.md`.
4. Paralel, anggota C mengerjakan laporan SIM (Tahap 9 di `tasks.md`).

Pengingat: nilai tugas ada di **rancangan, diagram, dan mockup**. Jika aplikasi belum sempat selesai, laporan tetap bisa lengkap dengan mockup dari Figma dan screenshot bagian yang sudah jalan.
