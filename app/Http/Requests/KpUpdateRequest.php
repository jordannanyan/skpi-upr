<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KpUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'nim'            => ['sometimes','string','exists:ref_mahasiswa,nim'],
            'nama_kegiatan'  => ['sometimes','string','max:255'],
            // file opsional saat update
            'file'           => ['sometimes','file','max:5120','mimes:pdf,jpg,jpeg,png'],
        ];
    }
}
