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

        $timeout        = (int) config('services.tik.timeout', 180);        // detik
        $connectTimeout = (int) config('services.tik.connect_timeout', 10); // detik
        $retries        = (int) config('services.tik.retries', 3);
        $retryDelayMs   = (int) config('services.tik.retry_delay', 2000);

        return Http::baseUrl($base)
            ->withHeaders([
                'x-api-key'       => $key,
                'Accept-Encoding' => 'gzip',
            ])
            ->acceptJson()
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
            ->retry($retries, $retryDelayMs, throw: false);
    }

    public function getFakultas(?array $params = null): array
    {
        $path = config('services.tik.endpoints.fakultas');
        $res  = $this->client()->get($path, $params ?? [])->throw()->json();
        return Arr::get($res, 'data', []);
    }

    public function getProdi(?array $params = null): array
    {
        $path = config('services.tik.endpoints.prodi');
        $res  = $this->client()->get($path, $params ?? [])->throw()->json();
        return Arr::get($res, 'data', []);
    }

    public function getMahasiswa(?array $params = null): array
    {
        $path = config('services.tik.endpoints.mahasiswa');
        $res  = $this->client()->get($path, $params ?? [])->throw()->json();
        return Arr::get($res, 'data', []);
    }
}
