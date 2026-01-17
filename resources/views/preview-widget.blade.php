<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Widget Preview - {{ $app->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #f3f4f6;
            background-image: radial-gradient(#d1d5db 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="max-w-2xl w-full mx-4">
        <div class="bg-white rounded-2xl shadow-2xl p-8 text-center border border-gray-100">
            <div class="mb-6 inline-flex items-center justify-center w-16 h-16 bg-green-100 text-green-600 rounded-full">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </div>
            
            <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Simulasi Halaman Client</h1>
            <p class="text-gray-500 mb-8">Halaman ini mensimulasikan website client Anda (misal: WordPress atau CodeIgniter) yang telah dipasangi snippet widget WhatsApp Operra.</p>
            
            <div class="bg-gray-50 rounded-xl p-6 border border-dashed border-gray-300 text-left">
                <h2 class="text-sm font-bold uppercase text-gray-400 mb-4 tracking-wider">Informasi Aplikasi</h2>
                <div class="space-y-3">
                    <div class="flex justify-between border-b border-gray-200 pb-2">
                        <span class="text-gray-600">Nama Aplikasi:</span>
                        <span class="font-bold text-gray-800">{{ $app->name }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-200 pb-2">
                        <span class="text-gray-600">WhatsApp Number:</span>
                        <span class="font-bold text-green-600">{{ $app->phone_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status Widget:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Terpasang & Aktif
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-8 text-sm text-gray-400 italic">
                Lihat di pojok layar Anda untuk melihat widget WhatsApp yang aktif.
            </div>
        </div>
    </div>

    <!-- THE SNIPPET -->
    <script src="{{ url('/js/wa-widget.js') }}" data-key="{{ $appKey }}"></script>
</body>
</html>

