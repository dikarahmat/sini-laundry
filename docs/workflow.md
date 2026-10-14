# Workflow: Sini Laundry

Panduan alur kerja AI dan tim: kapan boleh jalan sendiri, kapan harus berhenti dan minta izin.

## 1. Alur Kerja Standar per Task

```
1. Baca task di tasks.md dan bagian terkait di PRD.md / architecture.md
2. Cek kode yang sudah ada di sekitar area task
3. Susun rencana singkat (3-6 baris)
4. Kerjakan perubahan kecil
5. Jalankan tes dan Pint
6. Ringkas hasil sesuai format di agent.md
7. Centang task di tasks.md
```

Untuk task besar (menyentuh banyak file atau tabel), **tampilkan rencana dulu** dan tunggu persetujuan sebelum menulis kode.

## 2. Boleh Langsung Jalan (Tanpa Izin)

| Kegiatan | Contoh |
|---|---|
| Membuat file baru yang diminta task | Model, komponen Livewire, Service, test baru |
| Mengedit file yang jelas termasuk task | Menambah method di `OrderPricingService` |
| Menulis atau memperbaiki tes | Tes perhitungan harga |
| Memperbaiki bug kecil di kode sendiri | Typo, validasi kurang |
| Menjalankan perintah baca-saja | `php artisan route:list`, `php artisan test`, `git status` |
| Menjalankan Pint dan perbaikan gaya | `./vendor/bin/pint` |
| Menambah seeder data contoh | Layanan awal, akun demo |
| Memperbarui dokumentasi | Mencentang `tasks.md`, menambah catatan |
| Migrasi **baru** untuk tabel/kolom **baru** yang sudah ada di `architecture.md` | Membuat tabel `payments` |

## 3. Wajib Minta Izin Dulu

| Kegiatan | Alasan |
|---|---|
| Mengubah skema yang sudah ada (ubah/hapus kolom, ubah tipe, ubah relasi) | Bisa merusak data dan pekerjaan teman |
| Menjalankan `migrate:fresh`, `migrate:rollback`, `db:wipe` | Menghapus data |
| Menghapus file, folder, atau fitur | Sulit dikembalikan |
| Menulis ulang file besar atau banyak file sekaligus | Risiko konflik di Git |
| Menambah atau menghapus dependency (`composer require`, `npm install`) | Mengubah stack dan ukuran proyek |
| Mengubah `.env`, konfigurasi autentikasi, atau middleware keamanan | Dampak ke seluruh sistem |
| Mengubah aturan bisnis di `PRD.md` (harga, status, pembulatan) | Harus konsisten dengan laporan |
| Mengubah warna, font, atau pola komponen di `style-guide.md` | Mockup ikut berubah |
| Git: `push --force`, `reset --hard`, hapus branch, merge ke `main` | Bisa menimpa kerja orang lain |
| Mengakses internet atau layanan eksternal (API, webhook, payment) | Keamanan dan biaya |
| Menyentuh data produksi | Risiko kehilangan data nyata |

Format permintaan izin:

```
Izin: (apa yang ingin dilakukan)
Alasan: (kenapa perlu)
Dampak: (file/tabel/fitur yang terpengaruh)
Alternatif: (jika ada opsi lebih aman)
```

## 4. Aturan Berhenti (Stop Conditions)

AI harus **berhenti dan bertanya** jika:
- Instruksi bertentangan dengan `PRD.md` atau `architecture.md`.
- Task ambigu dan ada lebih dari satu tafsir yang masuk akal.
- Tes gagal dua kali berturut-turut dengan penyebab yang tidak jelas.
- Perubahan akan menyentuh file di luar task.
- Ditemukan potensi celah keamanan atau kebocoran data.

## 5. Alur Git Tim

1. Tarik terbaru: `git pull origin main`.
2. Buat branch: `git checkout -b feat/T3.3-order-pricing`.
3. Kerjakan dan commit kecil-kecil.
4. Jalankan `php artisan test` dan Pint.
5. Push branch dan buat Pull Request.
6. Minimal satu anggota lain meninjau sebelum merge.
7. Jangan push langsung ke `main`.

## 6. Pembagian Area Kerja (Hindari Konflik)

| Anggota | Area utama | Hindari menyentuh |
|---|---|---|
| A (backend) | Migrasi, model, service, policy, tes | View/tampilan |
| B (frontend) | Komponen Livewire (tampilan), layout, Tailwind, Figma | Service dan migrasi |
| C (laporan) | Dokumen SIM, diagram, mockup final | Kode aplikasi |

Jika dua orang perlu mengedit file yang sama, kabari dulu di grup.

## 7. Checklist Sebelum Merge

- [ ] Sesuai task dan `PRD.md`
- [ ] Tes lulus
- [ ] Pint bersih
- [ ] Tidak ada rahasia di commit
- [ ] Tampilan dicek di HP dan desktop (jika ada perubahan UI)
- [ ] `tasks.md` diperbarui
- [ ] Ditinjau satu anggota lain

## 8. Rutinitas Tim

| Kapan | Apa |
|---|---|
| Awal sesi kerja | Pull terbaru, baca `tasks.md`, tentukan task hari ini |
| Akhir sesi kerja | Commit, push, perbarui `tasks.md` |
| Mingguan | Sinkron 15 menit: apa selesai, apa terhambat, siapa mengerjakan apa |
| Sebelum deadline | Bekukan fitur baru, fokus perapihan laporan dan pengecekan format |
