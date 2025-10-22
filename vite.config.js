// vite.config.ts
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  plugins: [
    laravel({
      input: [
        // global
        'resources/js/bootstrap.ts',
        'resources/js/pages/admin.ts',

        // halaman Laporan
        'resources/js/pages/laporan/index.ts',
        'resources/js/pages/laporan/create.ts',
        'resources/js/pages/laporan/edit.ts',

        // (nanti tambahkan input untuk halaman lain di sini)
      ],
      refresh: true,
    }),
  ],
})
