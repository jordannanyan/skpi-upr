<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SertifikasiUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nim'                  => ['sometimes','string','exists:ref_mahasiswa,nim'],
            'nama_sertifikasi'     => ['sometimes','string','max:255'],
            'kategori_sertifikasi' => ['sometimes','string','max:100'],
            'file'                 => ['sometimes','file','max:5120','mimes:pdf,jpg,jpeg,png'],
        ];
    }
}
