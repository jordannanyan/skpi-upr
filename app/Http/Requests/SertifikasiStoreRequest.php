<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SertifikasiStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nim'                  => ['required','string','exists:ref_mahasiswa,nim'],
            'nama_sertifikasi'     => ['required','string','max:255'],
            'kategori_sertifikasi' => ['required','string','max:100'],
            'file'                 => ['required','file','max:5120','mimes:pdf,jpg,jpeg,png'], // 5MB
        ];
    }
}
