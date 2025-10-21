<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'role'       => ['required','string','in:'.implode(',', User::ROLES)],
            'username'   => ['required','string','max:100','unique:users,username'],
            'password'   => ['required','string','min:6'],
            'id_fakultas'=> ['nullable','integer','exists:ref_fakultas,id',
                              'required_if:role,Dekan,Wakadek,AdminFakultas'],
            'id_prodi'   => ['nullable','integer','exists:ref_prodi,id',
                              'required_if:role,Kajur,AdminJurusan'],
        ];
    }
}
