<?php

namespace App\Http\Controllers;

use App\Models\SuperAdmin;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controller as BaseController;

class SuperAdminController extends BaseController
{
    public function index()
    {
        try {
            $admins = SuperAdmin::all();
            return response()->json(['message' => 'Super admins fetched successfully', 'data' => $admins], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch super admins', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Storing super admin:', $request->all());
            $validated = $request->validate([
                'username' => 'required|string|unique:tb_super_admin,username',
                'password' => 'required|string|min:8'
            ]);

            $validated['password'] = Hash::make($request->password);

            $admin = SuperAdmin::create($validated);
            return response()->json(['message' => 'Super admin created successfully', 'data' => $admin], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create super admin', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $admin = SuperAdmin::findOrFail($id);
            return response()->json(['message' => 'Super admin fetched successfully', 'data' => $admin], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Super admin not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch super admin', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $admin = SuperAdmin::findOrFail($id);
            Log::info('Updating super admin:', $request->all());

            $validated = $request->validate([
                'username' => 'sometimes|string|unique:tb_super_admin,username,' . $id . ',id_super_admin',
                'password' => 'nullable|string|min:8'
            ]);

            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            $admin->update($validated);
            return response()->json(['message' => 'Super admin updated successfully', 'data' => $admin], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Super admin not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update super admin', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $admin = SuperAdmin::findOrFail($id);
            $admin->delete();
            return response()->json(['message' => 'Super admin deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Super admin not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete super admin', 'error' => $e->getMessage()], 500);
        }
    }
}