<?php

namespace App\Http\Controllers;

use App\Models\CustomerStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerStatusController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/CustomerStatuses', [
            'statuses' => CustomerStatus::orderBy('order')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:customer_statuses,name',
            'color' => 'required|string',
            'order' => 'required|integer',
        ]);

        CustomerStatus::create($validated);

        return redirect()->back()->with('success', 'Status created successfully.');
    }

    public function update(Request $request, CustomerStatus $customerStatus)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:customer_statuses,name,' . $customerStatus->id,
            'color' => 'required|string',
            'order' => 'required|integer',
        ]);

        $customerStatus->update($validated);

        return redirect()->back()->with('success', 'Status updated successfully.');
    }

    public function destroy(CustomerStatus $customerStatus)
    {
        // Prevent deleting if assigned to customers? For now just delete or set null.
        $customerStatus->delete();

        return redirect()->back()->with('success', 'Status deleted successfully.');
    }
}
