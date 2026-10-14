# Architecture: Sini Laundry

Rancangan teknis sistem. Dokumen ini menjawab **bagaimana** `PRD.md` diwujudkan.

## 1. Tech Stack

| Lapisan | Teknologi | Catatan |
|---|---|---|
| Bahasa | PHP 8.3+ | Syarat minimum Laravel 13 |
| Framework | **Laravel 13** | Backend, routing, ORM, autentikasi |
| UI reaktif | **Livewire 4** | Halaman dinamis tanpa menulis banyak JavaScript |
| Komponen UI | Flux UI (bawaan starter kit Livewire) | Boleh diganti Blade murni |
| Styling | **Tailwind CSS** | Token mengikuti `style-guide.md` |
| Build frontend | Vite | Bawaan Laravel |
| Database | **MySQL** | SQLite boleh dipakai di awal development |
| Tes | Pest atau PHPUnit | Sesuai bawaan starter kit |
| Format kode | Laravel Pint | Gaya kode seragam |
| Desain | Figma | Mockup |
| Diagram | draw.io | Flowchart dan UML |
| Versi kode | Git + GitHub | |

Catatan: versi paket (Livewire, Tailwind, Flux) mengikuti yang terpasang oleh starter kit. Cek `composer.json` dan `package.json` proyek untuk nomor versi yang pasti sebelum menuliskannya di laporan.

## 2. Gambaran Arsitektur

```
 Browser (HP / Desktop)
        |
        |  HTTPS
        v
 +--------------------------------------------------+
 |  Laravel 13                                      |
 |                                                  |
 |  Routes ──> Middleware (auth, role)              |
 |                |                                 |
 |                v                                 |
 |  Livewire Components (halaman & form)            |
 |                |                                 |
 |                v                                 |
 |  Actions / Services (aturan bisnis)              |
 |    - OrderPricingService                         |
 |    - OrderStatusService                          |
 |    - OrderCodeGenerator                          |
 |                |                                 |
 |                v                                 |
 |  Eloquent Models + Policies                      |
 +----------------|---------------------------------+
                  v
              MySQL
```

Pola: **monolit Laravel** dengan logika bisnis dipisah ke class Service, sehingga komponen Livewire tetap tipis dan mudah dites.

## 3. Struktur Folder (ringkas)

```
app/
  Enums/            Role.php, OrderStatus.php, PaymentMethod.php, PaymentStatus.php
  Models/           User, Customer, Service, Order, OrderItem, OrderStatusLog, Payment
  Policies/         OrderPolicy, ServicePolicy
  Services/         OrderPricingService, OrderStatusService, OrderCodeGenerator
  Http/Middleware/  EnsureRole.php
database/
  migrations/
  seeders/          UserSeeder, ServiceSeeder, DemoOrderSeeder
resources/
  views/            Halaman dan komponen Livewire (ikuti konvensi starter kit)
routes/
  web.php
tests/
  Feature/          Alur pesanan, otorisasi, cek status
  Unit/             Perhitungan harga
```

## 4. Peran dan Hak Akses

| Fitur | customer | admin | owner |
|---|:-:|:-:|:-:|
| Registrasi / login | ✔ | ✔ | ✔ |
| Buat pesanan online | ✔ | | |
| Lihat pesanan sendiri | ✔ | | |
| Input pesanan walk-in | | ✔ | ✔ |
| Ubah status pesanan | | ✔ | ✔ |
| Catat pembayaran | | ✔ | ✔ |
| Kelola pelanggan | | ✔ | ✔ |
| Kelola layanan dan harga | | | ✔ |
| Lihat laporan | | | ✔ |
| Mundurkan status | | | ✔ |
| Cek status publik (tanpa login) | ✔ | ✔ | ✔ |

Diterapkan lewat middleware `EnsureRole` (tingkat route) dan Policy (tingkat data).

## 5. Rancangan Database

### 5.1 ERD (teks)

```
users 1 ───── 0..1 customers
customers 1 ───── * orders
users (admin) 1 ───── * orders        (created_by)
orders 1 ───── * order_items * ───── 1 services
orders 1 ───── * order_status_logs
orders 1 ───── * payments
```

### 5.2 Tabel

**users**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| name | varchar(120) | |
| email | varchar(190), unique | |
| phone | varchar(20), nullable | |
| password | varchar | hash |
| role | enum(`customer`,`admin`,`owner`) | default `customer` |
| email_verified_at | timestamp, nullable | |
| timestamps | | |

**customers** (pelanggan, termasuk walk-in tanpa akun)

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| user_id | FK users, nullable | null jika walk-in |
| name | varchar(120) | |
| phone | varchar(20), indexed | dipakai untuk cek status |
| address | text, nullable | |
| timestamps | | |

**services**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| name | varchar(100) | contoh: Cuci + Setrika |
| unit | enum(`kg`,`pcs`) | |
| price | unsigned int | rupiah, tanpa desimal |
| duration_hours | unsigned smallint | estimasi |
| is_express | boolean | default false |
| is_active | boolean | default true |
| timestamps | | |

**orders**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| code | varchar(20), unique | `SL-20261014-001` |
| customer_id | FK customers | |
| created_by | FK users, nullable | admin yang input |
| source | enum(`walk_in`,`online`) | |
| status | enum(lihat 5.3) | |
| payment_status | enum(`unpaid`,`partial`,`paid`) | |
| total_weight | decimal(6,2) | kg |
| total_price | unsigned int | rupiah |
| notes | text, nullable | |
| estimated_done_at | timestamp, nullable | |
| picked_up_at | timestamp, nullable | |
| timestamps | | |

**order_items**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| order_id | FK orders, cascade | |
| service_id | FK services | |
| quantity | decimal(6,2) | kg atau pcs |
| unit_price | unsigned int | harga saat transaksi (snapshot) |
| subtotal | unsigned int | |

**order_status_logs**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| order_id | FK orders, cascade | |
| status | enum | status baru |
| changed_by | FK users, nullable | |
| note | varchar(255), nullable | wajib jika dimundurkan |
| created_at | timestamp | |

**payments**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| order_id | FK orders, cascade | |
| amount | unsigned int | |
| method | enum(`cash`,`transfer`,`qris`) | |
| received_by | FK users | |
| paid_at | timestamp | |

### 5.3 Enum status pesanan

`pending_confirmation` → `received` → `washing` → `ironing` → `done` → `picked_up`, serta `cancelled`.
Label tampilan Indonesia: Menunggu Konfirmasi, Diterima, Dicuci, Disetrika, Selesai, Diambil, Dibatalkan.

### 5.4 Indeks yang disarankan

- `orders.code` (unique), `orders.status`, `orders.created_at`
- `customers.phone`
- `order_status_logs.order_id`

## 6. Logika Bisnis Utama

**Perhitungan harga (`OrderPricingService`)**
1. Bulatkan berat ke atas per 0,5 kg, minimum 1 kg untuk layanan kiloan.
2. `subtotal = berat_dibulatkan x harga_layanan`.
3. Layanan Express: tambah persentase dari pengaturan owner (default 50%).
4. `total_price = jumlah semua subtotal`.
5. Simpan `unit_price` di `order_items` agar perubahan harga di masa depan tidak mengubah pesanan lama.

**Perpindahan status (`OrderStatusService`)**
1. Hanya boleh maju ke status berikutnya sesuai urutan.
2. Mundur hanya untuk `owner` dengan catatan wajib.
3. Pembatalan hanya sebelum `washing`.
4. Setiap perubahan menambah baris di `order_status_logs` (dalam satu transaksi database).

**Kode pesanan (`OrderCodeGenerator`)**
- Format `SL-{YYYYMMDD}-{urutan 3 digit per hari}`, dibuat dalam transaksi untuk mencegah kembar.

**Status pembayaran**
- `paid` jika total pembayaran >= `total_price`, `partial` jika ada pembayaran tetapi kurang, selain itu `unpaid`.

## 7. Rute Utama

| Method | URL | Akses | Fungsi |
|---|---|---|---|
| GET | `/` | publik | Landing page |
| GET | `/cek-status` | publik | Form cek status |
| GET | `/login`, `/register` | tamu | Autentikasi |
| GET | `/pesanan` | customer | Pesanan saya |
| GET | `/pesanan/baru` | customer | Buat pesanan online |
| GET | `/admin/pesanan` | admin, owner | Daftar pesanan |
| GET | `/admin/pesanan/baru` | admin, owner | Input pesanan walk-in |
| GET | `/admin/pesanan/{order}` | admin, owner | Detail, status, pembayaran |
| GET | `/admin/pelanggan` | admin, owner | Kelola pelanggan |
| GET | `/owner/dashboard` | owner | Dashboard |
| GET | `/owner/laporan` | owner | Laporan pendapatan |
| GET | `/owner/layanan` | owner | Kelola layanan |

## 8. Keamanan

| Ancaman | Penanganan |
|---|---|
| Akses tanpa izin | Middleware peran + Policy pada setiap model |
| Penebakan kode pesanan | Cek status butuh kode + 4 digit HP, dibatasi rate limiting |
| SQL injection | Eloquent / query binding, tanpa SQL mentah dari input |
| XSS | Blade meng-escape output secara default |
| CSRF | Token CSRF bawaan Laravel |
| Kebocoran rahasia | Semua kunci di `.env`, `.env` tidak masuk Git |
| Password | Hash bawaan Laravel (bcrypt/argon) |
| Data publik berlebihan | Halaman cek status hanya menampilkan status, layanan, dan estimasi |

## 9. Lingkungan

| Lingkungan | Keterangan |
|---|---|
| Local | Herd atau Laragon, database SQLite/MySQL lokal |
| Production (opsional) | Hosting PHP 8.3+ atau VPS, MySQL, HTTPS |

Variabel `.env` penting: `APP_NAME`, `APP_ENV`, `APP_URL`, `APP_LOCALE=id`, `DB_*`. Zona waktu `Asia/Jakarta` diatur di `config/app.php` (kunci `timezone`).

## 10. Rencana Integrasi (Fase 2)

| Integrasi | Pendekatan |
|---|---|
| WhatsApp/email | Laravel Notifications + Queue, dikirim saat status `done` |
| QRIS dinamis | Payment gateway, webhook memperbarui `payments` |
| Ekspor laporan | Excel (paket ekspor Laravel) dan PDF |
| Jadwal pengingat | Laravel Scheduler (cron) |

## 11. Pemetaan ke Diagram SIM

| Diagram | Berdasarkan |
|---|---|
| Flowchart | `PRD.md` bagian 6 dan alur status di 5.3 |
| Use Case | Tabel peran dan hak akses di bagian 4 |
| Activity | Alur A, B, C di `PRD.md` |
| Sequence | Logika bisnis bagian 6 (komponen: Pengguna, Livewire, Service, Database) |
| ERD | Bagian 5.1 dan 5.2 |
