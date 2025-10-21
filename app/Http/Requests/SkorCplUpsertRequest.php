<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SkorCplUpsertRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        // dukung body tunggal {kode_cpl,nim,skor} atau batch {items:[...]}
        if ($this->has('items')) {
            return [
                'items' => ['required','array','min:1'],
                'items.*.kode_cpl' => ['required','string','exists:cpl,kode_cpl'],
                'items.*.nim'      => ['required','string','exists:ref_mahasiswa,nim'],
                'items.*.skor'     => ['required','numeric','between:0,100'],
            ];
        }

        return [
            'kode_cpl' => ['required','string','exists:cpl,kode_cpl'],
            'nim'      => ['required','string','exists:ref_mahasiswa,nim'],
            'skor'     => ['required','numeric','between:0,100'],
        ];
    }
}
