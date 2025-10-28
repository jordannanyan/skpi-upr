<!doctype html>
<html lang="id" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'Admin – SKPI UPR')</title>

    {{-- CSS & shell --}}
    @vite(['resources/css/app.css', 'resources/js/admin/shell.js'])

    {{-- Bootstrap & Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    {{-- Axios CDN (wajib sebelum shell jalan agar "axios is not defined" tidak muncul) --}}
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    {{-- Bridge: dipakai di JS untuk URL --}}
    <meta id="bridge" data-api-base="{{ url('/api') }}" data-login-url="{{ route('login.page') }}"
        data-admin-url="{{ route('admin.page') }}" data-logout-url="{{ url('/logout') }}"
        data-profile-url="{{ url('/admin/profile') }}" data-password-url="{{ url('/admin/password') }}">
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
                    {{-- Dashboard (exact match) --}}
                    <a class="nav-link {{ (request()->is('admin') && !request()->is('admin/*')) ? 'active' : '' }}"
                        href="{{ route('admin.page') }}" data-active="dashboard"
                        data-roles="SuperAdmin,Dekan,Wakadek,Kajur,AdminFakultas,AdminJurusan,Mahasiswa">
                        <i class="bi bi-speedometer me-2"></i> Dashboard
                    </a>


                    {{-- Laporan SKPI (tetap tidak untuk Mahasiswa) --}}
                    <a class="nav-link {{ request()->is('admin/laporan*') ? 'active' : '' }}"
                        href="{{ url('/admin/laporan') }}" data-active="laporan"
                        data-roles="SuperAdmin,Dekan,Wakadek,Kajur,AdminFakultas,AdminJurusan">
                        <i class="bi bi-file-earmark-text me-2"></i> Laporan SKPI
                    </a>

                    {{-- Mahasiswa (tetap tidak untuk Mahasiswa role) --}}
                    <a class="nav-link {{ request()->is('admin/mahasiswa*') ? 'active' : '' }}"
                        href="{{ url('/admin/mahasiswa') }}" data-active="mahasiswa"
                        data-roles="SuperAdmin,Kajur,AdminJurusan,AdminFakultas,Wakadek,Dekan">
                        <i class="bi bi-people me-2"></i> Mahasiswa
                    </a>

                    {{-- Prodi (tidak untuk Mahasiswa) --}}
                    <a class="nav-link {{ request()->is('admin/prodi*') ? 'active' : '' }}"
                        href="{{ url('/admin/prodi') }}" data-active="prodi"
                        data-roles="SuperAdmin,AdminFakultas,Wakadek,Dekan">
                        <i class="bi bi-diagram-3 me-2"></i> Prodi
                    </a>

                    {{-- Fakultas (tidak untuk Mahasiswa) --}}
                    <a class="nav-link {{ request()->is('admin/fakultas*') ? 'active' : '' }}"
                        href="{{ url('/admin/fakultas') }}" data-active="fakultas"
                        data-roles="SuperAdmin,AdminFakultas,Wakadek,Dekan">
                        <i class="bi bi-building me-2"></i> Fakultas
                    </a>

                    {{-- TA (tambahkan Mahasiswa) --}}
                    <a class="nav-link {{ request()->is('admin/ta*') ? 'active' : '' }}" href="{{ route('ta.index') }}"
                        data-active="ta" data-roles="SuperAdmin,Kajur,AdminJurusan,Mahasiswa">
                        <i class="bi bi-journal-text me-2"></i> Tugas Akhir
                    </a>

                    {{-- KP (tambahkan Mahasiswa) --}}
                    <a class="nav-link {{ request()->is('admin/kp*') ? 'active' : '' }}" href="{{ route('kp.index') }}"
                        data-active="kp" data-roles="SuperAdmin,Kajur,AdminJurusan,Mahasiswa">
                        <i class="bi bi-briefcase me-2"></i> Kerja Praktek
                    </a>

                    {{-- Sertifikasi (tambahkan Mahasiswa) --}}
                    <a class="nav-link {{ request()->is('admin/sertifikasi*') ? 'active' : '' }}"
                        href="{{ route('sertifikasi.index') }}" data-active="sertif"
                        data-roles="SuperAdmin,Kajur,AdminJurusan,Mahasiswa">
                        <i class="bi bi-patch-check me-2"></i> Sertifikasi
                    </a>

                    {{-- CPL & Skor (tetap tidak untuk Mahasiswa, kalau mau tampilkan tinggal tambah Mahasiswa juga)
                    --}}
                    <a class="nav-link {{ request()->is('admin/cpl*') ? 'active' : '' }}"
                        href="{{ route('cpl.index') }}" data-active="cpl" data-roles="SuperAdmin,Kajur,AdminJurusan">
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
                <div class="fw-semibold">@yield('pageTitle', 'Dashboard')</div>

                {{-- USER DROPDOWN --}}
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2" type="button"
                        id="btnUserMenu" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span id="userName">Memuat…</span>
                        <small class="text-muted" id="userRoleMini"></small>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="btnUserMenu">
                        <li class="dropdown-header" id="userDesc">—</li>
                        <li><a class="dropdown-item" id="linkProfile" href="#">Profil Saya</a></li>
                        <li><a class="dropdown-item" id="linkPassword" href="#">Ganti Password</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="#" id="btnLogoutTop">Logout</a></li>
                    </ul>
                </div>
            </div>

            <div class="p-4">
                @yield('content')
            </div>
        </main>
    </div>

    {{-- Bootstrap JS bundle (wajib untuk dropdown) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>

</html>