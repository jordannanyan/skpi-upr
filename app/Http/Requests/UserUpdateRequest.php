<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('id'); // /api/users/{id}

        return [
            'role'        => ['sometimes','string','in:'.implode(',', User::ROLES)],
            'username'    => ['sometimes','nullable','string','max:100', Rule::unique('users','username')->ignore($id)],
            'password'    => ['nullable','string','min:6'],
            'nim'         => ['sometimes','nullable','string','max:20','exists:ref_mahasiswa,nim'],
            'id_fakultas' => ['sometimes','nullable','integer','exists:ref_fakultas,id',
                               'required_if:role,Dekan,Wakadek,AdminFakultas'],
            'id_prodi'    => ['sometimes','nullable','integer','exists:ref_prodi,id',
                               'required_if:role,Kajur,AdminJurusan'],
        ];
    }

    public function prepareForValidation()
    {
        if ($this->has('username') && is_string($this->username)) {
            $this->merge(['username' => strtolower(trim($this->username))]);
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $role = $this->input('role') ?? optional($this->route('user'))->role;

            // Konsistensi nim -> id_prodi -> id_fakultas untuk Mahasiswa
            if ($role === 'Mahasiswa') {
                $nim = $this->input('nim');
                if ($nim) {
                    $idProdiNim = DB::table('ref_mahasiswa')->where('nim', $nim)->value('id_prodi');

                    if ($this->filled('id_prodi') && (int)$this->input('id_prodi') !== (int)$idProdiNim) {
                        $v->errors()->add('id_prodi', 'id_prodi tidak sesuai dengan prodi milik NIM.');
                    }

                    if ($this->filled('id_fakultas')) {
                        $idf = DB::table('ref_prodi')->where('id', $idProdiNim)->value('id_fakultas');
                        if ((int)$this->input('id_fakultas') !== (int)$idf) {
                            $v->errors()->add('id_fakultas', 'id_fakultas tidak sesuai dengan fakultas dari prodi NIM.');
                        }
                    }
                }
            }
        });
    }
}
