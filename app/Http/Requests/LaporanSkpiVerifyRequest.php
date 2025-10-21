<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LaporanSkpiVerifyRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'tgl_pengesahan' => ['required','date'],
            'no_pengesahan'  => ['required','string','max:100'],
            'catatan_verifikasi' => ['sometimes','nullable','string','max:1000'],
        ];
    }
}
