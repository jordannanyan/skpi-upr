<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LaporanSkpiDecisionRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'approve' => ['required','boolean'], // true=approve, false=reject
            'note'    => ['sometimes','nullable','string','max:1000'],
        ];
    }
}
