{{-- Halaman Kelola Pengguna (UC-02 Admin User Management) --}}
@extends('layouts.app')

@section('title', 'Kelola Pengguna')
@section('page-title', 'Kelola Pengguna')

@php
    $activeMenu = 'kelola-pengguna';
@endphp

@section('content')
<div class="flex-1 bg-gray-50 overflow-y-auto" id="page-kelola-pengguna"
     x-data="{ showModalTambah: false }">

    {{-- Top Bar --}}
    <x-topbar title="Kelola Pengguna Sistem" subtitle="Manajemen akun Administrator & Petugas DLH Kabupaten Cianjur (UC-02)">
        <x-slot:actions>
            <button @click="showModalTambah = true"
                    class="flex items-center gap-2 bg-green-700 hover:bg-green-800 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Tambah Petugas DLH Baru
            </button>
        </x-slot:actions>
    </x-topbar>

    <div class="px-4 lg:px-8 py-6 max-w-6xl mx-auto space-y-6">

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl p-4 flex items-center gap-3 shadow-sm">
                <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-4 space-y-1 shadow-sm">
                @foreach($errors->all() as $err)
                    <p class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        {{ $err }}
                    </p>
                @endforeach
            </div>
        @endif

        {{-- Main User Table Card --}}
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-gray-800">Daftar Pengguna Terdaftar</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Otoritas hak akses akun internal instansi</p>
                </div>
                <span class="text-xs font-semibold text-gray-600 bg-gray-100 rounded-full px-3 py-1">
                    Total: {{ count($users) }} Akun
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3.5">Nama Lengkap</th>
                            <th class="px-6 py-3.5">Email Kredensial</th>
                            <th class="px-6 py-3.5">Hak Akses / Role</th>
                            <th class="px-6 py-3.5">Tanggal Dibuat</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @foreach($users as $u)
                            <tr class="hover:bg-gray-50/80 transition-colors">
                                <td class="px-6 py-4 font-semibold text-gray-800 flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-teal-100 text-teal-700 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ strtoupper(substr($u->name, 0, 2)) }}
                                    </div>
                                    <span>{{ $u->name }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-600 font-mono text-xs">{{ $u->email }}</td>
                                <td class="px-6 py-4">
                                    @if(str_contains(strtolower($u->email), 'admin'))
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-700">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                            Administrator
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                            Petugas DLH
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-400">
                                    {{ isset($u->created_at) ? date('d M Y', strtotime($u->created_at)) : '-' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if(!str_contains(strtolower($u->email), 'admin'))
                                        <form action="{{ url('/kelola-pengguna/' . $u->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun pengguna ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-semibold hover:underline">
                                                Hapus Akun
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Utama</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Modal Tambah User --}}
    <div x-show="showModalTambah" x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4" @click.outside="showModalTambah = false">
            <div class="flex items-center justify-between border-b pb-3">
                <h3 class="text-base font-bold text-gray-800">Tambah Akun Petugas DLH</h3>
                <button @click="showModalTambah = false" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <form action="{{ url('/kelola-pengguna') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" required placeholder="Masukkan nama petugas..."
                           class="w-full border rounded-xl px-3 py-2.5 text-sm outline-none focus:border-green-500" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Email Kredensial</label>
                    <input type="email" name="email" required placeholder="petugas@dlh.cianjurkab.go.id"
                           class="w-full border rounded-xl px-3 py-2.5 text-sm outline-none focus:border-green-500" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required placeholder="Minimal 6 karakter"
                           class="w-full border rounded-xl px-3 py-2.5 text-sm outline-none focus:border-green-500" />
                </div>
                <div class="pt-3 flex gap-2 justify-end border-t">
                    <button type="button" @click="showModalTambah = false"
                            class="px-4 py-2 text-xs font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-xs font-bold text-white bg-green-700 hover:bg-green-800 rounded-xl shadow">
                        Simpan Akun
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
