<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $id = $this->route('id'); // /api/users/{id}
        return [
            'role'       => ['sometimes','string','in:'.implode(',', User::ROLES)],
            'username'   => ['sometimes','string','max:100',"unique:users,username,{$id}"],
            'password'   => ['nullable','string','min:6'],
            'id_fakultas'=> ['nullable','integer','exists:ref_fakultas,id',
                              'required_if:role,Dekan,Wakadek,AdminFakultas'],
            'id_prodi'   => ['nullable','integer','exists:ref_prodi,id',
                              'required_if:role,Kajur,AdminJurusan'],
        ];
    }
}
