<!doctype html>
<html lang="id" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title','Admin – SKPI UPR')</title>

    {{-- CSS & shell (ubah .js -> .ts kalau file Vite kamu TypeScript) --}}
    @vite(['resources/css/app.css','resources/js/admin/shell.js'])

    {{-- Bootstrap & Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    {{-- Axios CDN (wajib sebelum shell jalan agar "axios is not defined" tidak muncul) --}}
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    {{-- Bridge: dipakai di JS untuk URL --}}
    <meta id="bridge"
        data-api-base="{{ url('/api') }}"
        data-login-url="{{ route('login.page') }}"
        data-admin-url="{{ route('admin.page') }}"
        data-logout-url="{{ url('/logout') }}">
    @stack('head')
</head>

<body>
    <div class="d-flex">
        {{-- SIDEBAR --}}
        <aside class="border-end bg-white" style="width:260px;min-height:100vh;">
            <div class="p-3 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-mortarboard fs-4 text-primary"></i>
                <div>
                    <div class="fw-bold">SKPI UPR</div>
                    <small class="text-muted" id="userRole">Memuat…</small>
                </div>
            </div>

            <div class="p-3">
                <div class="mb-2 text-uppercase small fw-semibold text-secondary">Menu</div>

                <div class="nav nav-pills flex-column gap-1" id="adminMenu">
                    {{-- Dashboard (semua role) --}}
                    <a class="nav-link {{ request()->is('admin') ? 'active' : '' }}"
                        href="{{ route('admin.page') }}"
                        data-active="dashboard"
                        data-roles="SuperAdmin,Dekan,Wakadek,Kajur,AdminFakultas,AdminJurusan">
                        <i class="bi bi-speedometer me-2"></i> Dashboard
                    </a>

                    {{-- Laporan SKPI (semua role melihat, aksi beda di page) --}}
                    <a class="nav-link {{ request()->is('admin/laporan*') ? 'active' : '' }}"
                        href="{{ url('/admin/laporan') }}"
                        data-active="laporan"
                        data-roles="SuperAdmin,Dekan,Wakadek,Kajur,AdminFakultas,AdminJurusan">
                        <i class="bi bi-file-earmark-text me-2"></i> Laporan SKPI
                    </a>

                    {{-- Mahasiswa --}}
                    <a class="nav-link {{ request()->is('admin/mahasiswa*') ? 'active' : '' }}"
                        href="{{ url('/admin/mahasiswa') }}"
                        data-active="mahasiswa"
                        data-roles="SuperAdmin,Kajur,AdminJurusan,AdminFakultas,Wakadek,Dekan">
                        <i class="bi bi-people me-2"></i> Mahasiswa
                    </a>

                    {{-- Prodi --}}
                    <a class="nav-link {{ request()->is('admin/prodi*') ? 'active' : '' }}"
                        href="{{ url('/admin/prodi') }}"
                        data-active="prodi"
                        data-roles="SuperAdmin,AdminFakultas,Wakadek,Dekan">
                        <i class="bi bi-diagram-3 me-2"></i> Prodi
                    </a>

                    {{-- Fakultas --}}
                    <a class="nav-link {{ request()->is('admin/fakultas*') ? 'active' : '' }}"
                        href="{{ url('/admin/fakultas') }}"
                        data-active="fakultas"
                        data-roles="SuperAdmin,AdminFakultas,Wakadek,Dekan">
                        <i class="bi bi-building me-2"></i> Fakultas
                    </a>
                    <a class="nav-link {{ request()->is('admin/ta*') ? 'active' : '' }}"
                        href="{{ route('ta.index') }}"
                        data-active="ta"
                        data-roles="SuperAdmin,Kajur,AdminJurusan">
                        <i class="bi bi-journal-text me-2"></i> Tugas Akhir
                    </a>
                    <a class="nav-link {{ request()->is('admin/kp*') ? 'active' : '' }}"
                        href="{{ route('kp.index') }}"
                        data-active="kp"
                        data-roles="SuperAdmin,Kajur,AdminJurusan">
                        <i class="bi bi-briefcase me-2"></i> Kerja Praktek
                    </a>
                    <a class="nav-link {{ request()->is('admin/sertifikasi*') ? 'active' : '' }}"
                        href="{{ route('sertifikasi.index') }}"
                        data-active="sertif"
                        data-roles="SuperAdmin,Kajur,AdminJurusan">
                        <i class="bi bi-patch-check me-2"></i> Sertifikasi
                    </a>

                    <a class="nav-link {{ request()->is('admin/cpl*') ? 'active' : '' }}"
                        href="{{ route('cpl.index') }}"
                        data-active="cpl"
                        data-roles="SuperAdmin,Kajur,AdminJurusan">
                        <i class="bi bi-graph-up me-2"></i> CPL & Skor
                    </a>

                    <a href="#" id="btnLogout" class="nav-link mt-2">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </div>
            </div>
        </aside>

        {{-- MAIN --}}
        <main class="flex-grow-1">
            <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
                <div class="fw-semibold">@yield('pageTitle','Dashboard')</div>
                <div class="small text-muted" id="userInfo">—</div>
            </div>

            <div class="p-4">
                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>

</html>