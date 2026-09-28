{{-- Sidebar Component --}}
{{-- Usage: <x-sidebar :active="'dashboard'" /> --}}
{{-- $active: 'dashboard' | 'data-latih' | 'usulan-lokasi' | 'hasil-klasifikasi' | 'confusion-matrix' --}}

@props(['active' => 'dashboard'])

@php
    $userRole = session('user_role', 'petugas');
    $userName = session('user_name', ($userRole === 'admin' ? 'Admin Dinas DLH' : 'Petugas Lapangan DLH'));

    if ($userRole === 'admin') {
        $navItems = [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard'],
            ['route' => 'data-latih', 'label' => 'Kelola Data Latih', 'icon' => 'database'],
            ['route' => 'decision-tree', 'label' => 'Decision Tree', 'icon' => 'git-branch'],
            ['route' => 'usulan-lokasi', 'label' => 'Usulan & Klasifikasi TPS', 'icon' => 'map-pin'],
            ['route' => 'hasil-klasifikasi', 'label' => 'Hasil Klasifikasi', 'icon' => 'bar-chart-3'],
            ['route' => 'confusion-matrix', 'label' => 'Confusion Matrix', 'icon' => 'grid-3x3'],
            ['route' => 'kelola-pengguna', 'label' => 'Kelola Pengguna', 'icon' => 'users'],
        ];
    } else {
        $navItems = [
            ['route' => 'dashboard', 'label' => 'Dashboard Petugas', 'icon' => 'layout-dashboard'],
            ['route' => 'usulan-lokasi', 'label' => 'Input Usulan & Peta Jarak', 'icon' => 'map-pin'],
            ['route' => 'hasil-klasifikasi', 'label' => 'Peta Hasil Klasifikasi', 'icon' => 'bar-chart-3'],
        ];
    }
@endphp

<aside class="relative flex flex-col w-64 h-full min-h-screen bg-gray-900 text-white shrink-0"
       x-data="{ 
           showNotif: false, 
           showLogout: false,
           notifications: [
               { id: 1, text: 'Sistem Klasifikasi C4.5 Aktif', time: 'Baru saja', read: false },
               { id: 2, text: 'Peta Spasial QGIS & BPS Siap Digunakan', time: '1 jam lalu', read: false }
           ],
           get unreadCount() {
               return this.notifications.filter(n => !n.read).length;
           },
           markAllRead() {
               this.notifications.forEach(n => n.read = true);
           },
           deleteNotif(id) {
               this.notifications = this.notifications.filter(n => n.id !== id);
           }
       }" id="sidebar-nav">

    {{-- Logo + Close (mobile) --}}
    <div class="flex items-center justify-between px-5 py-5 border-b border-gray-700">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shrink-0">
                {{-- Leaf icon --}}
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 19 2c1 2 2 4.5 1 8-1.5 5-5.7 8-9 10Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-bold text-emerald-400 uppercase tracking-wider">SIG-TPS C4.5</p>
                <p class="text-xs text-gray-400 leading-tight">DLH Kab. Cianjur</p>
            </div>
        </div>
        <button @click="sidebarOpen = false"
                class="lg:hidden w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-white hover:bg-gray-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    {{-- Role Indicator Banner --}}
    <div class="px-4 pt-3 pb-1">
        <div class="px-3 py-1.5 rounded-xl text-xs font-bold flex items-center justify-between {{ $userRole === 'admin' ? 'bg-purple-900/50 border border-purple-700/50 text-purple-300' : 'bg-blue-900/50 border border-blue-700/50 text-blue-300' }}">
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full {{ $userRole === 'admin' ? 'bg-purple-400' : 'bg-blue-400' }} animate-pulse"></span>
                <span>{{ $userRole === 'admin' ? 'Role: Administrator' : 'Role: Petugas Lapangan' }}</span>
            </span>
            <span class="text-[10px] uppercase font-mono px-1.5 py-0.5 rounded bg-black/40">{{ strtoupper($userRole) }}</span>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 px-3 py-3 space-y-1 overflow-y-auto" id="sidebar-menu">
        <p class="px-3 mb-2 text-[11px] font-semibold text-gray-500 uppercase tracking-widest">
            {{ $userRole === 'admin' ? 'Menu Administrator' : 'Menu Petugas' }}
        </p>

        @foreach ($navItems as $item)
            @php $isActive = $active === $item['route']; @endphp
            <a href="{{ url('/' . $item['route']) }}"
               id="nav-{{ $item['route'] }}"
               class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 group
                      {{ $isActive
                          ? ($userRole === 'admin' ? 'bg-emerald-700 text-white shadow-md' : 'bg-blue-700 text-white shadow-md')
                          : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                {{-- Icon --}}
                @switch($item['icon'])
                    @case('layout-dashboard')
                        <svg class="w-[18px] h-[18px] {{ $isActive ? 'text-emerald-200' : 'text-gray-500 group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/>
                            <rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>
                        </svg>
                        @break
                    @case('database')
                        <svg class="w-[18px] h-[18px] {{ $isActive ? 'text-emerald-200' : 'text-gray-500 group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/>
                            <path d="M3 12A9 3 0 0 0 21 12"/>
                        </svg>
                        @break
                    @case('git-branch')
                        <svg class="w-[18px] h-[18px] {{ $isActive ? 'text-emerald-200' : 'text-gray-500 group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/>
                            <path d="M18 9a9 9 0 0 1-9 9"/>
                        </svg>
                        @break
                    @case('map-pin')
                        <svg class="w-[18px] h-[18px] {{ $isActive ? 'text-emerald-200' : 'text-gray-500 group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        @break
                    @case('bar-chart-3')
                        <svg class="w-[18px] h-[18px] {{ $isActive ? 'text-emerald-200' : 'text-gray-500 group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/>
                        </svg>
                        @break
                    @case('grid-3x3')
                        <svg class="w-[18px] h-[18px] {{ $isActive ? 'text-emerald-200' : 'text-gray-500 group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M3 15h18"/>
                            <path d="M9 3v18"/><path d="M15 3v18"/>
                        </svg>
                        @break
                    @case('users')
                        <svg class="w-[18px] h-[18px] {{ $isActive ? 'text-emerald-200' : 'text-gray-500 group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        @break
                @endswitch

                <span class="flex-1 text-left">{{ $item['label'] }}</span>

                @if ($isActive)
                    <svg class="w-3.5 h-3.5 text-emerald-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/>
                    </svg>
                @endif
            </a>
        @endforeach
    </nav>

    {{-- Bottom actions --}}
    <div class="px-3 pt-3 border-t border-gray-700 space-y-1">

        {{-- Notification --}}
        <div class="relative">
            <button @click="showNotif = !showNotif; showLogout = false"
                    id="btn-notifikasi"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-all">
                <svg class="w-[18px] h-[18px] text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                </svg>
                <span>Notifikasi</span>
                <span class="ml-auto text-xs bg-emerald-500 text-white rounded-full px-1.5 py-0.5 font-bold min-w-[20px] text-center" 
                      x-show="unreadCount > 0" 
                      x-text="unreadCount" 
                      id="notif-count"></span>
            </button>

            {{-- Notification dropdown --}}
            <div x-show="showNotif" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-2"
                 @click.outside="showNotif = false"
                 class="absolute bottom-full left-0 right-0 mb-2 bg-gray-800 border border-gray-600 rounded-xl overflow-hidden shadow-2xl z-50 w-64"
                 id="notif-panel">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-700">
                    <span class="text-xs font-bold text-white">Notifikasi</span>
                    <button @click="markAllRead()" class="text-xs text-emerald-400 hover:text-emerald-300" id="btn-mark-read">Tandai dibaca</button>
                </div>
                <div class="max-h-60 overflow-y-auto">
                    <template x-if="notifications.length === 0">
                        <div class="px-4 py-6 text-center text-xs text-gray-500">
                            Tidak ada notifikasi
                        </div>
                    </template>
                    <template x-for="notif in notifications" :key="notif.id">
                        <div class="px-4 py-3 border-b border-gray-700 flex justify-between items-start gap-2 hover:bg-gray-800/50 transition-colors cursor-pointer"
                             @click="notif.read = true">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs leading-snug text-white font-medium">
                                    <span class="inline-block w-1.5 h-1.5 rounded-full mr-1.5 align-middle"
                                          :class="notif.read ? 'bg-gray-600' : 'bg-emerald-400'"></span>
                                    <span x-text="notif.text"></span>
                                </p>
                                <p class="text-[10px] text-gray-500 mt-0.5" x-text="notif.time"></p>
                            </div>
                            <button @click.stop="deleteNotif(notif.id)" class="text-gray-500 hover:text-red-400 shrink-0 mt-0.5" title="Hapus">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Settings --}}
        <a href="{{ url('/pengaturan') }}"
           class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-all">
            <svg class="w-[18px] h-[18px] text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            <span>Pengaturan</span>
        </a>

        {{-- Logout --}}
        <div class="relative">
            <button @click="showLogout = !showLogout; showNotif = false"
                    id="btn-keluar"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-400 hover:bg-gray-800 hover:text-red-400 transition-all">
                <svg class="w-[18px] h-[18px] text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span>Keluar</span>
            </button>

            {{-- Logout confirmation --}}
            <div x-show="showLogout" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 @click.outside="showLogout = false"
                 class="absolute bottom-full left-0 right-0 mb-2 bg-gray-800 border border-gray-600 rounded-xl p-4 shadow-2xl z-50"
                 id="logout-panel">
                <p class="text-sm text-white font-semibold mb-1">Keluar dari sistem?</p>
                <p class="text-xs text-gray-400 mb-3">Sesi Anda akan berakhir dan data yang belum disimpan akan hilang.</p>
                <div class="flex gap-2">
                    <button @click="showLogout = false"
                            class="flex-1 py-2 text-xs font-semibold text-gray-300 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors">
                        Batal
                    </button>
                    <a href="{{ url('/logout') }}"
                       class="flex-1 py-2 text-xs font-semibold text-white text-center bg-red-600 hover:bg-red-700 rounded-lg transition-colors"
                       id="btn-confirm-logout">
                        Ya, Keluar
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- User profile footer --}}
    <div class="px-4 py-4 border-t border-gray-700 flex items-center gap-3" id="user-profile">
        <div class="w-9 h-9 rounded-full {{ $userRole === 'admin' ? 'bg-purple-700' : 'bg-blue-600' }} flex items-center justify-center text-xs font-black text-white shrink-0 shadow-md">
            {{ $userRole === 'admin' ? 'AD' : 'PL' }}
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-white truncate">{{ $userName }}</p>
            <p class="text-xs text-gray-400 truncate">{{ $userRole === 'admin' ? 'Administrator DLH' : 'Petugas Lapangan' }}</p>
        </div>
    </div>
</aside>
