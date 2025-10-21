<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CplStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'kode_cpl' => ['required','string','max:50','unique:cpl,kode_cpl'],
            'kategori' => ['required','string','max:100'],
            'deskripsi'=> ['nullable','string','max:1000'],
        ];
    }
}
