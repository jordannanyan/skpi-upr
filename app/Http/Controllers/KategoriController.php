<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;

class KategoriController extends BaseController
{
    public function index()
    {
        try {
            $data = Kategori::all();
            return response()->json(['message' => 'Kategori fetched successfully', 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch kategori', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Storing kategori:', $request->all());
            $validated = $request->validate([
                'nama_kategori' => 'required|string',
                'status' => 'required|in:selesai,proses,batal'
            ]);

            $kategori = Kategori::create($validated);

            return response()->json(['message' => 'Kategori created successfully', 'data' => $kategori], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store kategori', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $kategori = Kategori::findOrFail($id);
            return response()->json(['message' => 'Kategori fetched successfully', 'data' => $kategori], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Kategori not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch kategori', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $kategori = Kategori::findOrFail($id);
            Log::info('Updating kategori:', $request->all());

            $validated = $request->validate([
                'nama_kategori' => 'sometimes|string',
                'status' => 'sometimes|in:selesai,proses,batal'
            ]);

            $kategori->update($validated);

            return response()->json(['message' => 'Kategori updated successfully', 'data' => $kategori], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Kategori not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update kategori', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $kategori = Kategori::findOrFail($id);
            $kategori->delete();
            return response()->json(['message' => 'Kategori deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Kategori not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete kategori', 'error' => $e->getMessage()], 500);
        }
    }
}
