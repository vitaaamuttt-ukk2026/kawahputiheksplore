<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - Kawah Putih</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Mencegah elemen melebar melebihi layar HP */
        img, video, table { max-width: 100%; height: auto; }
        
        .sidebar-link { 
            display: flex; 
            align-items: center; 
            gap: 0.75rem; 
            padding: 0.65rem 0.85rem; 
            border-radius: 0.5rem; 
            color: #4b5563; 
            transition: all 0.2s;
            text-decoration: none;
            white-space: nowrap;
        }
        .sidebar-link:hover { 
            background-color: #f3f4f6; 
            color: #1f2937;
        }
        .sidebar-link.active { 
            background-color: #ecfdf5; 
            color: #059669; 
            font-weight: 600;
        }
        .sidebar-link .link-text {
            transition: opacity 0.2s;
        }
        
        .sidebar-collapsed .link-text,
        .sidebar-collapsed .menu-label,
        .sidebar-collapsed .sidebar-footer-text,
        .sidebar-collapsed .user-info,
        .sidebar-collapsed .logo-text {
            display: none !important;
        }
        
        .sidebar-collapsed .sidebar-link {
            justify-content: center;
            padding: 0.65rem;
        }
        .sidebar-collapsed {
            width: 5rem !important;
        }
        
        .sidebar-collapsed .logo-wrapper { 
            justify-content: center !important; 
            gap: 0 !important; 
            width: 100%;
        }
        .sidebar-collapsed .logo-icon { 
            margin: 0 auto !important; 
        }

        .material-symbols-outlined {
            font-size: 22px;
        }
        #main-content {
            transition: margin-left 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Backdrop untuk HP -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden" onclick="toggleSidebar()"></div>

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed md:static inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 flex flex-col transition-all duration-300 transform -translate-x-full md:translate-x-0 shrink-0">
            
            <!-- Logo -->
            <div class="p-4 border-b border-gray-200">
                <div class="logo-wrapper flex items-center gap-3 overflow-hidden transition-all duration-300">
                    <div class="logo-icon w-9 h-9 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-lg flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-white text-xl">landscape</span>
                    </div>
                    <div class="logo-text">
                        <h1 class="font-bold text-gray-800 text-sm">Kawah Putih</h1>
                        <p class="text-[10px] text-gray-500">CIWIDEY ECO PORTAL</p>
                    </div>
                </div>
            </div>

            <!-- Menu Navigasi -->
            <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
                <p class="menu-label text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-2 px-3">Menu</p>
                
                <a href="{{ route('pengunjung.dashboard') }}" class="sidebar-link {{ request()->routeIs('pengunjung.dashboard') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">home</span>
                    <span class="link-text">Dashboard</span>
                </a>
                <a href="{{ route('pengunjung.info') }}" class="sidebar-link {{ request()->routeIs('pengunjung.info') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">info</span>
                    <span class="link-text">Info</span>
                </a>
                <a href="{{ route('pengunjung.wisata') }}" class="sidebar-link {{ request()->routeIs('pengunjung.wisata') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">travel_explore</span>
                    <span class="link-text">Wisata</span>
                </a>
                <a href="{{ route('pengunjung.booking') }}" class="sidebar-link {{ request()->routeIs('pengunjung.booking') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">shopping_cart</span>
                    <span class="link-text">Booking</span>
                </a>
                <a href="{{ route('pengunjung.tiket') }}" class="sidebar-link {{ request()->routeIs('pengunjung.tiket') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">confirmation_number</span>
                    <span class="link-text">Tiket</span>
                </a>
            </nav>

            <!-- Footer Sidebar -->
            <div class="p-3 border-t border-gray-200">
                @auth
                <div class="sidebar-footer-text bg-emerald-50 rounded-lg p-2.5 mb-2 border border-emerald-100">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                        <span class="text-[10px] font-medium text-emerald-700">Jaringan Cagar Biosfer</span>
                    </div>
                    <p class="text-[10px] text-emerald-600 mt-0.5">Aktif</p>
                </div>
                @endauth
                
                @auth
                <form action="{{ route('pengunjung.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="sidebar-link text-red-600 hover:bg-red-50 w-full">
                        <span class="material-symbols-outlined">logout</span>
                        <span class="link-text">Keluar</span>
                    </button>
                </form>
                @else
                <a href="{{ route('pengunjung.login') }}" class="sidebar-link text-emerald-600 hover:bg-emerald-50 w-full">
                    <span class="material-symbols-outlined">login</span>
                    <span class="link-text">Login</span>
                </a>
                @endauth
            </div>
        </aside>

        <!-- Main Content -->
        <div id="main-content" class="flex-1 flex flex-col overflow-hidden w-full">
            <!-- Top Bar -->
            <header class="bg-white border-b border-gray-200 px-4 md:px-6 py-3">
                <div class="flex items-center justify-between">
                    <!-- Breadcrumb -->
                    <div class="flex items-center gap-2 text-sm">
                        <button onclick="toggleSidebar()" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 mr-2" title="Buka/Tutup Sidebar">
                            <span class="material-symbols-outlined text-xl">menu</span>
                        </button>
                        <span class="text-gray-500">Portal Wisatawan</span>
                        <span class="text-gray-400">›</span>
                        <span class="font-semibold text-gray-800">@yield('page-title', 'Dashboard')</span>
                    </div>

                    <!-- Status & User Info -->
                    <div class="flex items-center gap-4">
                        <div class="hidden md:flex items-center gap-2 text-xs">
                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                            <span class="text-gray-600">Sistem Live</span>
                        </div>

                        <div class="flex items-center gap-3">
                            <button class="relative p-2 text-gray-400 hover:text-gray-600">
                                <span class="material-symbols-outlined text-xl">notifications</span>
                                <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                            </button>
                            
                            @auth
                            <div class="flex items-center gap-2 pl-3 border-l border-gray-200">
                                <div class="w-8 h-8 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-full flex items-center justify-center text-white font-semibold text-xs">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                                <div class="user-info">
                                    <p class="text-xs font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                                    <p class="text-[10px] text-gray-500">Tamu</p>
                                </div>
                            </div>
                            @endauth
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                @if(session('success'))
                    <div class="mb-4 bg-emerald-50 border-l-4 border-emerald-500 p-3 rounded-lg">
                        <p class="text-emerald-700 text-sm">{{ session('success') }}</p>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-3 rounded-lg">
                        <p class="text-red-700 text-sm">{{ session('error') }}</p>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            
            if (window.innerWidth < 768) {
                // Mode HP: Geser masuk/keluar
                sidebar.classList.toggle('-translate-x-full');
                backdrop.classList.toggle('hidden');
            } else {
                // Mode Laptop/Tablet: Kecilkan/besarkan sidebar
                sidebar.classList.toggle('sidebar-collapsed');
            }
        }

        // Reset tampilan saat layar diubah ukurannya
        window.addEventListener('resize', () => {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            if (window.innerWidth >= 768) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.add('hidden');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            // Hanya terapkan collapse dari localStorage jika di desktop
            if (window.innerWidth >= 768 && localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('sidebar-collapsed');
            }
        });
    </script>
</body>
</html>