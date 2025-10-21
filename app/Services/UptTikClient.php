<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;

class UptTikClient
{
    protected function client(): PendingRequest
    {
        $base = rtrim(config('services.tik.base'), '/');
        $key  = config('services.tik.key');

        return Http::baseUrl($base)
            ->withHeaders(['x-api-key' => $key])
            ->acceptJson()
            ->timeout(60);
    }

    public function getFakultas(): array
    {
        $path = config('services.tik.endpoints.fakultas');
        $res  = $this->client()->get($path)->throw()->json();
        return Arr::get($res, 'data', []);
    }

    public function getProdi(): array
    {
        $path = config('services.tik.endpoints.prodi');
        $res  = $this->client()->get($path)->throw()->json();
        return Arr::get($res, 'data', []);
    }

    public function getMahasiswa(): array
    {
        $path = config('services.tik.endpoints.mahasiswa');
        $res  = $this->client()->get($path)->throw()->json();
        // API contoh menyertakan "data" array
        return Arr::get($res, 'data', []);
    }
}
