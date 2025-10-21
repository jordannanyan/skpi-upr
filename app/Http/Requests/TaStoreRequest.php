<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nim'      => ['required','string','exists:ref_mahasiswa,nim'],
            'kategori' => ['required','string','max:100'], // mis: Skripsi/Tesis/Disertasi
            'judul'    => ['required','string','max:500'],
        ];
    }
}
