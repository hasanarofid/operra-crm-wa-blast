<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulasi Embed - {{ $app->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #f8fafc;
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 30px 30px;
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Navbar Simulasi Sistem Client -->
    <nav class="bg-indigo-700 text-white p-4 shadow-lg mb-8">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white/20 rounded flex items-center justify-center font-bold">OP</div>
                <span class="font-bold tracking-tight">Client System Dashboard </span>
            </div>
            <div class="flex gap-4 text-xs font-medium opacity-80 uppercase tracking-widest">
                <span>Dashboard</span>
                <span>Reports</span>
                <span class="border-b-2 border-white pb-1">WhatsApp CRM</span>
                <span>Settings</span>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 pb-12">
        @if($type === 'inbox')
            <!-- PREVIEW FULL INBOX IFRAME -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
                <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                        <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                        <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                        <span class="ml-2 text-xs text-gray-400 font-medium italic">Simulasi Iframe: {{ url('/embed/inbox?key=' . $appKey) }}</span>
                    </div>
                    <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded">MODUL WHATSAPP AKTIF</span>
                </div>
                
                <!-- THE IFRAME -->
                <iframe 
                    src="{{ url('/embed/inbox?key=' . $appKey) }}" 
                    class="w-full h-[700px] border-none"
                    allow="clipboard-read; clipboard-write">
                </iframe>
            </div>
        @else
            <!-- PREVIEW FLOATING WIDGET -->
            <div class="max-w-2xl mx-auto">
                <div class="bg-white rounded-2xl shadow-2xl p-10 text-center border border-gray-100">
                    <div class="mb-6 inline-flex items-center justify-center w-20 h-20 bg-green-100 text-green-600 rounded-full shadow-inner">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </div>
                    
                    <h1 class="text-4xl font-extrabold text-gray-900 mb-4 tracking-tight">Simulasi Website Client</h1>
                    <p class="text-gray-500 mb-10 leading-relaxed">Ini adalah visualisasi bagaimana <b>JS Widget</b> Operra muncul di website client Anda (misal: Toko Online atau Landing Page).</p>
                    
                    <div class="bg-indigo-50 rounded-2xl p-8 border border-indigo-100 text-left relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-10">
                            <svg class="w-24 h-24 text-indigo-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                        </div>
                        <h2 class="text-xs font-bold uppercase text-indigo-400 mb-4 tracking-widest">Aplikasi Terkoneksi</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="text-[10px] text-indigo-300 font-bold uppercase">Nama Sistem</label>
                                <div class="text-lg font-bold text-indigo-900">{{ $app->name }}</div>
                            </div>
                            <div>
                                <label class="text-[10px] text-indigo-300 font-bold uppercase">Nomor Tujuan</label>
                                <div class="text-lg font-bold text-indigo-900">{{ $app->phone_number }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-12 text-sm text-gray-400 flex items-center justify-center gap-2">
                        <span class="animate-bounce text-xl">👇</span>
                        <span>Lihat pojok layar Anda untuk melihat tombol chat yang aktif</span>
                    </div>
                </div>
            </div>

            <!-- THE WIDGET SNIPPET -->
            <script src="{{ url('/js/wa-widget.js') }}" data-key="{{ $appKey }}"></script>
        @endif
    </div>

    <!-- Footer Simulasi -->
    <footer class="text-center py-8 text-gray-400 text-xs">
        &copy; 2026 Powered by Operra CRM WhatsApp Official &bull; Secure Embedding Simulation
    </footer>
</body>
</html>
