# Panduan Fitur WhatsApp Business (Meta Official & Marketing)

Dokumen ini menjelaskan cara penggunaan fitur WhatsApp Business yang telah diintegrasikan, termasuk dukungan Meta Cloud API (Verified), Trial 3 Bulan, dan Chat Marketing (Blast).

## 1. Integrasi Meta WhatsApp Business API (Official Verified)

Sistem sekarang menggunakan **Meta Global Configuration** sebagai basis utama.

### Konfigurasi Global:
1. Pergi ke menu **WhatsApp Settings**.
2. Pada bagian **Meta WhatsApp Global Configuration**, masukkan:
   - **Permanent Access Token**: Token utama aplikasi Meta Anda.
   - **Webhook Verify Token**: Token rahasia untuk verifikasi webhook (contoh: `tigasatu_secret`).
   - **Meta App ID**: ID Aplikasi Meta Anda.
   - **WABA ID**: WhatsApp Business Account ID Anda.
3. Klik **Update Global Meta Config**.

### Fitur Sync Otomatis (Sangat Direkomendasikan):
1. Pastikan Anda sudah mendaftarkan nomor sales di Meta Business Suite.
2. Klik tombol **Sync From Meta (WABA)** di atas tabel daftar akun.
3. Sistem akan otomatis menarik semua nomor yang terdaftar di Meta, lengkap dengan Phone Number ID, Nama, dan Status Verifikasinya.
4. Anda tidak perlu lagi input nomor satu per satu secara manual.

---

## 2. Fitur Chat Marketing (WhatsApp Blast)

Fitur ini memungkinkan pengiriman pesan massal. Untuk Meta Official, sangat disarankan menggunakan Template.

### Cara Penggunaan:
1. **Membuat Campaign**:
   - Menu **WhatsApp Blast** di Sidebar.
   - Klik **Create New Blast**.
   - Pilih akun pengirim.
   - Untuk Meta Official, isi kolom **Message / Template Name** dengan nama template yang sudah diapprove di Meta (contoh: `promo_januari`).

2. **Memproses Blast**:
   - Setelah disimpan sebagai draft, klik **Process**.
   - Sistem akan melakukan loop pengiriman dan memberikan laporan real-time.

---

## 3. Sistem Trial 3 Bulan

Setiap akun WhatsApp baru otomatis mendapatkan status:
- `is_trial`: `true`
- `trial_ends_at`: 3 bulan dari tanggal daftar.
- `subscription_plan`: `trial_verified`.

Sistem akan otomatis memblokir pengiriman pesan (Inbox & Blast) jika masa trial telah habis. Admin dapat melihat sisa hari trial di tabel daftar akun.

