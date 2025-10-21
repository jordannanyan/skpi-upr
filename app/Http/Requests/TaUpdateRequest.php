<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nim'      => ['sometimes','string','exists:ref_mahasiswa,nim'],
            'kategori' => ['sometimes','string','max:100'],
            'judul'    => ['sometimes','string','max:500'],
        ];
    }
}
