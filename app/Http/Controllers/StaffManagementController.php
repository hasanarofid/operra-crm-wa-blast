<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WhatsappAccount;
use App\Models\WhatsappAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class StaffManagementController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'whatsappAgents.whatsappAccount'])->get();
        $roles = Role::all();
        $whatsappAccounts = WhatsappAccount::all();

        return Inertia::render('Settings/Staff', [
            'users' => $users,
            'roles' => $roles,
            'whatsappAccounts' => $whatsappAccounts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|exists:roles,name',
            'whatsapp_account_id' => 'nullable|exists:whatsapp_accounts,id',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->assignRole($validated['role']);

            if ($validated['whatsapp_account_id']) {
                WhatsappAgent::create([
                    'user_id' => $user->id,
                    'whatsapp_account_id' => $validated['whatsapp_account_id'],
                    'is_available' => true,
                ]);
            }

            DB::commit();
            return redirect()->back()->with('message', 'Staff created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error creating staff: ' . $e->getMessage());
        }
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|exists:roles,name',
            'whatsapp_account_id' => 'nullable|exists:whatsapp_accounts,id',
        ]);

        DB::beginTransaction();
        try {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            if ($validated['password']) {
                $user->update(['password' => Hash::make($validated['password'])]);
            }

            $user->syncRoles([$validated['role']]);

            // Update WhatsApp Account link
            if ($validated['whatsapp_account_id']) {
                WhatsappAgent::updateOrCreate(
                    ['user_id' => $user->id],
                    ['whatsapp_account_id' => $validated['whatsapp_account_id'], 'is_available' => true]
                );
            } else {
                WhatsappAgent::where('user_id', $user->id)->delete();
            }

            DB::commit();
            return redirect()->back()->with('message', 'Staff updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating staff: ' . $e->getMessage());
        }
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete yourself.');
        }

        $user->delete();
        return redirect()->back()->with('message', 'Staff deleted successfully.');
    }
}

