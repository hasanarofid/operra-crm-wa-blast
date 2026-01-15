<?php

namespace App\Http\Controllers\ERP;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Customer::with(['assignedSales', 'chatSessions' => function($q) {
            $q->whereIn('status', ['open', 'pending'])->latest();
        }])->latest();

        // Jika bukan super-admin atau manager, hanya tampilkan customer yang ditugaskan ke dia
        if (!$user->hasRole('super-admin') && !$user->hasRole('manager')) {
            $query->where('assigned_to', $user->id);
        }

        return Inertia::render('ERP/Master/Customers/Index', [
            'customers' => $query->paginate(10)
        ]);
    }

    public function create()
    {
        // Hanya Admin dan Manager yang bisa menambah customer/lead secara manual
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->hasRole('manager')) {
            abort(403, 'Hanya Admin/Manager yang bisa menambah customer.');
        }

        return Inertia::render('ERP/Master/Customers/Create', [
            'sales' => \App\Models\User::role('sales')->get()
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->hasRole('manager')) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'required|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        Customer::create($request->all());

        return redirect()->route('master.customers.index')->with('message', 'Customer created.');
    }

    public function edit(Customer $customer)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->hasRole('manager')) {
            abort(403);
        }

        return Inertia::render('ERP/Master/Customers/Edit', [
            'customer' => $customer,
            'sales' => \App\Models\User::role('sales')->get()
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->hasRole('manager')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'required|string',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'nullable|string',
        ]);

        $customer->update($validated);

        return redirect()->route('master.customers.index')->with('message', 'Customer reassigned/updated.');
    }

    public function updateStatus(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'status' => 'required|in:lead,prospect,customer,lost',
        ]);

        $customer->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Status customer berhasil diperbarui',
            'customer' => $customer
        ]);
    }
}
