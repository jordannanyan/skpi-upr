<?php

namespace App\Http\Controllers;

use App\Models\Fakultas;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;

class FakultasController extends BaseController
{
    public function index()
    {
        try {
            $data = Fakultas::all();
            return response()->json(['message' => 'Fakultas fetched successfully', 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch fakultas', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Storing fakultas:', $request->all());
            $validated = $request->validate([
                'nama_fakultas' => 'required|string',
                'username' => 'required|string|unique:tb_fakultas,username',
                'password' => 'required|string',
                'nama_dekan' => 'required|string',
                'nip' => 'required|string',
                'alamat' => 'required|string'
            ]);

            $validated['password'] = bcrypt($validated['password']);
            $fakultas = Fakultas::create($validated);

            return response()->json(['message' => 'Fakultas created successfully', 'data' => $fakultas], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store fakultas', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $fakultas = Fakultas::findOrFail($id);
            return response()->json(['message' => 'Fakultas fetched successfully', 'data' => $fakultas], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Fakultas not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch fakultas', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $fakultas = Fakultas::findOrFail($id);
            Log::info('Updating fakultas:', $request->all());

            $validated = $request->validate([
                'nama_fakultas' => 'sometimes|string',
                'username' => 'sometimes|string|unique:tb_fakultas,username,' . $id . ',id_fakultas',
                'password' => 'sometimes|string',
                'nama_dekan' => 'sometimes|string',
                'nip' => 'sometimes|string',
                'alamat' => 'sometimes|string'
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            }

            $fakultas->update($validated);

            return response()->json(['message' => 'Fakultas updated successfully', 'data' => $fakultas], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Fakultas not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update fakultas', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $fakultas = Fakultas::findOrFail($id);
            $fakultas->delete();
            return response()->json(['message' => 'Fakultas deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Fakultas not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete fakultas', 'error' => $e->getMessage()], 500);
        }
    }
}
