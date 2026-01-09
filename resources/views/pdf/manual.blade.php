<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>User Manual - Operra CRM WA Blast</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 40px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
        }
        h1 { color: #4f46e5; font-size: 22px; }
        h2 { color: #1f2937; font-size: 18px; border-left: 4px solid #4f46e5; padding-left: 10px; margin-top: 30px; }
        h3 { color: #374151; font-size: 16px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f9fafb;
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }
        .page-break {
            page-break-after: always;
        }
        .highlight {
            background-color: #fef3c7;
            padding: 2px 5px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">OPERRA CRM & WA BLAST</div>
        <h1>MANUAL PENGGUNA (USER MANUAL)</h1>
        <p>URL: <a href="https://crm.hasanarofid.site/">https://crm.hasanarofid.site/</a></p>
    </div>

    <h2>1. PENDAHULUAN</h2>
    <p>Operra CRM adalah platform manajemen hubungan pelanggan yang terintegrasi langsung dengan WhatsApp. Sistem ini memungkinkan tim Anda untuk mengelola banyak akun WhatsApp (Multi-Account), membagi pesan secara otomatis ke sales (Auto-assignment), dan memantau riwayat percakapan secara terpusat.</p>

    <h2>2. AKSES LOGIN DEFAULT</h2>
    <table>
        <thead>
            <tr>
                <th>Peran (Role)</th>
                <th>Email Login</th>
                <th>Password</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Super Admin</strong></td>
                <td>admin@operra.com</td>
                <td>password</td>
            </tr>
            <tr>
                <td><strong>Manager</strong></td>
                <td>manager@operra.com</td>
                <td>password</td>
            </tr>
            <tr>
                <td><strong>Sales (Jakarta)</strong></td>
                <td>sales.jakarta@operra.com</td>
                <td>password</td>
            </tr>
            <tr>
                <td><strong>Sales (Surabaya)</strong></td>
                <td>sales.surabaya@operra.com</td>
                <td>password</td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>

    <h2>3. PANDUAN ADMINISTRATOR (SUPER ADMIN)</h2>
    
    <h3>3.1 Menghubungkan Akun WhatsApp (WA Multi-Account)</h3>
    <p>Menu ini digunakan untuk mendaftarkan nomor WhatsApp kantor ke sistem melalui pihak ketiga (seperti Fonnte atau Official API).</p>
    <ol>
        <li>Pilih menu <strong>WA Multi-Account</strong> di sidebar.</li>
        <li>Klik tombol <strong>Add New Account</strong>.</li>
        <li>Isi Nama Akun, Nomor WhatsApp, dan masukkan <strong>API Token</strong> yang didapat dari provider.</li>
        <li>Klik <strong>Save</strong>.</li>
        <li>Klik tombol <strong>Sync</strong> untuk memastikan status akun menjadi "Active".</li>
    </ol>

    <h3>3.2 Manajemen Staf & Sales (Manage Staff)</h3>
    <p>Gunakan menu ini untuk membuat akun login bagi tim sales Anda dan menghubungkan mereka ke nomor WhatsApp tertentu.</p>
    <ol>
        <li>Pilih menu <strong>Manage Staff</strong>.</li>
        <li>Isi Nama, Email, dan Password untuk sales baru.</li>
        <li>Pilih Role (Sales/Manager).</li>
        <li>Pada bagian <strong>Linked WhatsApp Account</strong>, pilih nomor WA yang akan dikelola oleh sales tersebut.</li>
        <li>Klik <strong>Create Staff</strong>.</li>
    </ol>

    <div class="page-break"></div>

    <h2>4. PANDUAN OPERASIONAL (SALES & TEAM)</h2>

    <h3>4.1 Chat Inbox (Shared Team Inbox)</h3>
    <p>Ini adalah pusat komunikasi tim dengan pelanggan.</p>
    <ol>
        <li>Pilih menu <strong>Chat Inbox</strong>.</li>
        <li>Di sisi kiri terdapat daftar sesi chat yang masuk.</li>
        <li>Klik pada salah satu nama pelanggan untuk membuka percakapan.</li>
        <li>Anda dapat membalas pesan secara real-time.</li>
        <li><strong>Real-time Notification:</strong> Sistem akan mengeluarkan suara notifikasi dan tanda angka merah jika ada pesan baru yang masuk.</li>
    </ol>

    <h3>4.2 Manajemen Leads (Manage Leads)</h3>
    <p>Gunakan menu ini untuk melacak status setiap pelanggan.</p>
    <ol>
        <li>Pilih menu <strong>Manage Leads</strong>.</li>
        <li>Anda akan melihat daftar customer yang menghubungi via WhatsApp.</li>
        <li>Anda bisa mengubah status mereka (misal: dari Lead menjadi Prospect atau Customer).</li>
    </ol>

    <h2>5. FITUR UNGGULAN</h2>
    <ul>
        <li><strong>Auto-Assignment:</strong> Pembagian pesan otomatis secara Round Robin.</li>
        <li><strong>Permission-based:</strong> Sales hanya bisa melihat chat yang ditugaskan kepada mereka.</li>
        <li><strong>Verified Badge Support:</strong> Integrasi dengan Official WA Business API.</li>
    </ul>

    <div class="footer">
        © 2026 Operra CRM by hasanarofid. All rights reserved.
    </div>
</body>
</html>

