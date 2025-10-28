<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'role'        => ['required','string','in:'.implode(',', User::ROLES)],
            'username'    => ['nullable','string','max:100','unique:users,username'],
            'password'    => ['nullable','string','min:6'],
            'nim'         => ['nullable','string','max:20', 'exists:ref_mahasiswa,nim',
                               // wajib nim untuk role Mahasiswa
                               Rule::requiredIf(fn()=> $this->input('role') === 'Mahasiswa')],
            'id_fakultas' => ['nullable','integer','exists:ref_fakultas,id',
                               'required_if:role,Dekan,Wakadek,AdminFakultas'],
            'id_prodi'    => ['nullable','integer','exists:ref_prodi,id',
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
            $role = $this->input('role');

            // Konsistensi nim -> id_prodi -> id_fakultas
            if ($role === 'Mahasiswa') {
                $nim = $this->input('nim');
                if ($nim) {
                    $idProdiNim = DB::table('ref_mahasiswa')->where('nim', $nim)->value('id_prodi');

                    // Jika id_prodi dikirim, harus cocok dengan milik nim
                    if ($this->filled('id_prodi') && (int)$this->input('id_prodi') !== (int)$idProdiNim) {
                        $v->errors()->add('id_prodi', 'id_prodi tidak sesuai dengan prodi milik NIM.');
                    }

                    // Jika id_fakultas dikirim, harus cocok dengan prodi tersebut
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
