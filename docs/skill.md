# Skill: Template Instruksi Berulang

Kumpulan pola instruksi yang sering dipakai, supaya tidak mengetik ulang. Salin template, ganti bagian dalam `[...]`, lalu kirim ke AI.

Semua template mengasumsikan AI sudah membaca `agent.md`, `PRD.md`, dan `architecture.md`.

---

## 1. Mengerjakan Task dari `tasks.md`

```
Kerjakan task [T3.3] dari tasks.md.
Baca bagian terkait di PRD.md dan architecture.md dulu.
Tampilkan rencana singkat, tunggu saya setuju, baru tulis kode.
Setelah selesai, jawab dengan format di agent.md bagian 8.
```

## 2. Membuat Migrasi + Model + Factory

```
Buat migrasi, model Eloquent, dan factory untuk tabel [orders] sesuai
architecture.md bagian 5.2.
- Tambahkan relasi, $fillable, dan cast enum.
- Tambahkan indeks sesuai bagian 5.4.
- Jangan ubah tabel lain.
Jelaskan perintah untuk menjalankannya.
```

## 3. Membuat Halaman Livewire

```
Buat halaman Livewire [Daftar Pesanan] untuk peran [admin dan owner].
- Fitur: [filter status, pencarian kode/nama, paginasi].
- Tampilan mengikuti style-guide.md (kartu di HP, tabel di desktop).
- Logika bisnis taruh di Service, bukan di komponen.
- Otorisasi lewat middleware peran dan Policy.
- Semua teks dalam Bahasa Indonesia.
- Tambahkan rute di web.php sesuai architecture.md bagian 7.
Ikuti konvensi file starter kit yang sudah ada di proyek.
```

## 4. Membuat Service Class

```
Buat class [OrderPricingService] di app/Services.
Aturan bisnis: PRD.md bagian 7 (nomor [1-3]).
- Method publik: [calculate(array $items): array].
- Gunakan integer rupiah.
- Sertakan unit test dengan minimal [6] kasus, termasuk batas pembulatan 0,5 kg,
  minimal 1 kg, dan layanan Express.
```

## 5. Menulis Tes

```
Tulis feature test untuk [alur ubah status pesanan]:
- status hanya boleh maju sesuai urutan
- admin tidak boleh mundurkan status, owner boleh dengan catatan
- pembatalan hanya sebelum status Dicuci
- setiap perubahan membuat baris di order_status_logs
Gunakan factory dan database test. Jalankan tesnya dan laporkan hasilnya.
```

## 6. Audit Keamanan

```
Lakukan audit keamanan pada [seluruh proyek / folder app/Livewire / fitur cek status].
Periksa:
1. Otorisasi: apakah ada rute atau aksi yang bisa diakses peran yang salah?
2. Validasi input di setiap form.
3. Mass assignment, SQL mentah, dan output Blade tanpa escape.
4. Rahasia yang ter-hardcode atau ikut ter-commit.
5. Cek status publik: rate limiting dan data yang terekspos.
Keluaran: daftar temuan berurutan dari paling berbahaya, tiap temuan berisi
lokasi file, penjelasan, dan saran perbaikan. JANGAN langsung mengubah kode;
tunggu persetujuan saya.
```

## 7. Refactor

```
Refactor [nama file/class] tanpa mengubah perilaku.
Tujuan: [memindahkan logika bisnis dari komponen ke Service / mengurangi duplikasi].
Syarat: tes yang ada tetap lulus, tidak ada perubahan skema, tidak ada dependency baru.
Tunjukkan rencana dulu, lalu perubahan dalam commit kecil.
```

## 8. Debugging

```
Terjadi error: [tempel pesan error lengkap]
Terjadi saat: [langkah yang dilakukan]
Yang sudah dicoba: [...]
Cari penyebab utamanya dengan membaca kode terkait dan log (storage/logs/laravel.log).
Jelaskan penyebabnya dulu, lalu usulkan perbaikan minimal.
```

## 9. Review Kode (Pull Request)

```
Review perubahan di branch [feat/T3.3-order-pricing] terhadap main.
Cek: kesesuaian dengan PRD.md, aturan di agent.md, keamanan, tes, dan N+1 query.
Keluaran: daftar masalah (wajib diperbaiki / saran) beserta lokasi file dan baris.
```

## 10. Membuat Seeder Data Demo

```
Buat seeder data demo untuk screenshot dan mockup:
- 1 owner, 2 admin, 10 pelanggan (nama Indonesia realistis)
- 5 layanan sesuai PRD.md bagian 5.1
- 30 pesanan dalam 30 hari terakhir dengan status dan pembayaran yang bervariasi
Gunakan factory. Semua data fiktif, jangan pakai data orang nyata.
```

## 11. Menulis Bagian Laporan SIM

```
Tulis [Bab 2: Analisis Sistem] untuk laporan SIM berdasarkan PRD.md.
Struktur: Analisa Permasalahan, Pengguna Sistem dan Kebutuhannya.
Bahasa: Indonesia formal akademik, tanpa kata gaul.
Panjang: [sekitar 2 halaman]. Gunakan tabel untuk pengguna dan kebutuhan.
```

## 12. Membuat Diagram (Mermaid atau PlantUML)

```
Buat [Use Case Diagram / Activity Diagram / Sequence Diagram] dalam format
[Mermaid / PlantUML] untuk [alur pesanan walk-in] berdasarkan PRD.md bagian 6.
Pastikan aktor, nama langkah, dan istilah status sama persis dengan dokumen.
Beri keterangan singkat di bawah diagram untuk dimasukkan ke laporan.
```

## 13. Deskripsi Mockup

```
Untuk layar [Pesanan Baru (kasir)], jelaskan elemen yang harus ada, urutan field,
perilaku tombol, dan keadaan kosong/error, mengikuti style-guide.md.
Keluarannya dipakai sebagai acuan desain di Figma dan sebagai teks pembahasan Bab 4.
```

## 14. Pemeriksaan Konsistensi Dokumen

```
Periksa konsistensi antara PRD.md, architecture.md, tasks.md, dan style-guide.md.
Cari: istilah berbeda untuk hal yang sama, status yang tidak cocok, fitur di PRD
yang tidak ada di tasks, tabel di architecture yang tidak dipakai di PRD.
Keluaran: daftar ketidakkonsistenan dan usulan perbaikannya. Jangan edit dulu.
```

## 15. Persiapan Sebelum Deadline

```
Lakukan pemeriksaan akhir:
1. Jalankan semua tes dan Pint.
2. Pastikan seeder demo berjalan dari database kosong (migrate:fresh --seed) di salinan lokal.
3. Cek tidak ada rahasia di riwayat Git.
4. Cocokkan fitur yang dibahas di laporan dengan yang benar-benar ada di aplikasi.
Laporkan hasilnya dalam checklist.
```

---

## Tips Memakai Template

- Semakin spesifik bagian `[...]`, semakin bagus hasilnya.
- Berikan satu task per permintaan.
- Jika hasil meleset, kutip bagian dokumen yang dilanggar, misalnya "lihat PRD bagian 7 nomor 2".
- Hasil AI tetap harus dibaca dan dipahami anggota tim. Dosen bisa menanyakan cara kerja sistem.
