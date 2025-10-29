<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AuthUserSeeder extends Seeder
{
	public function run(): void
	{
		// SuperAdmin
		User::updateOrCreate(
			['username' => 'superadmin'],
			['role' => 'SuperAdmin', 'password' => 'admin123']
		);

		// Dekan FT (id_fakultas = 5)
		User::updateOrCreate(
			['username' => 'dekan_ft'],
			['role' => 'Dekan', 'password' => 'secret123', 'id_fakultas' => 5]
		);

		// Wakadek FT
		User::updateOrCreate(
			['username' => 'wakadek_ft'],
			['role' => 'Wakadek', 'password' => 'secret123', 'id_fakultas' => 5]
		);

		// Admin Fakultas FT
		User::updateOrCreate(
			['username' => 'adminf_ft'],
			['role' => 'AdminFakultas', 'password' => 'secret123', 'id_fakultas' => 5]
		);

		// Kajur TI (id_prodi = 58)
		User::updateOrCreate(
			['username' => 'kajur_ti'],
			['role' => 'Kajur', 'password' => 'secret123', 'id_prodi' => 58]
		);

		// Admin Jurusan TI
		User::updateOrCreate(
			['username' => 'adminj_ti'],
			['role' => 'AdminJurusan', 'password' => 'secret123', 'id_prodi' => 58]
		);
	}
}
