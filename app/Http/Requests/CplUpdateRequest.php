<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CplUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $kode = $this->route('kode'); // /api/cpl/{kode}
        return [
            'kategori' => ['sometimes','string','max:100'],
            'deskripsi'=> ['sometimes','nullable','string','max:1000'],
            // kode_cpl sebagai PK tidak diubah lewat update (lebih aman)
        ];
    }
}
