<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LaporanSkpiSubmitRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'nim' => ['required','string','exists:ref_mahasiswa,nim'],
            // catatan opsional dari admin jurusan
            'catatan' => ['sometimes','nullable','string','max:1000'],
        ];
    }
}
