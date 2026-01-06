<p align="center">
  <img src="public/images/logo_operra.png" alt="Operra Logo" width="150">
</p>

# Operra - Business Operations Control Panel

Operra adalah sistem internal bisnis *production-ready* yang dirancang untuk UKM (SME) dan startup yang sedang berkembang. Sistem ini berfokus pada efisiensi operasional, pengambilan keputusan berbasis data, dan skalabilitas alur kerja.

## Fitur Utama

- **Manajemen Pesanan (Orders)**: Pelacakan status pesanan secara real-time.
- **Pelacakan Inventaris (Inventory)**: Monitoring stok barang dengan sistem peringatan dini untuk stok rendah.
- **Aktivitas Staf**: Log aktivitas harian staf untuk audit dan transparansi operasional.
- **Sistem Persetujuan (Approvals)**: Alur kerja persetujuan (biaya, diskon, cuti, dll) berbasis peran.
- **Analitik Pendapatan**: Monitoring Monthly Recurring Revenue (MRR) dan performa penjualan.
- **Monitoring AI**: Pelacakan penggunaan token dan estimasi biaya layanan AI (GPT-4o, Claude, dll).

## Tech Stack

- **Framework**: [Laravel 12](https://laravel.com)
- **Frontend**: [Vue 3](https://vuejs.org) dengan [Inertia.js](https://inertiajs.com)
- **Styling**: [Tailwind CSS](https://tailwindcss.com) (Full Dark Mode Support)
- **Database**: MySQL 8.0
- **Manajemen Role**: [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
- **Charts**: [ApexCharts](https://apexcharts.com)

## Informasi Login (Demo/Testing)

Sistem ini menggunakan Role-Based Access Control (RBAC). Berikut adalah kredensial yang dapat digunakan untuk mencoba berbagai peran:

| Role | Email | Password |
| :--- | :--- | :--- |
| **Admin** | `admin@example.com` | `password` |
| **Manager** | `manager@example.com` | `password` |
| **Staff** | `staff@example.com` | `password` |

## Instalasi

1. Clone repositori ini.
2. Jalankan `composer install`.
3. Jalankan `npm install && npm run build`.
4. Salin `.env.example` ke `.env` dan sesuaikan konfigurasi database.
5. Jalankan migrasi dan seeder:
   ```bash
   php artisan migrate:fresh --seed
   ```
6. Jalankan server lokal:
   ```bash
   php artisan serve
   ```

## License

Sistem ini dikembangkan sebagai bagian dari sistem kontrol internal bisnis khusus.

---
Developed by **[hasanarofid](https://hasanarofid.site)**

