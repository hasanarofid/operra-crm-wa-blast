<?php

namespace App\Http\Controllers\ERP;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class SalesOrderController extends Controller
{
    public function index()
    {
        return Inertia::render('ERP/Sales/Orders/Index', [
            'orders' => SalesOrder::with(['customer', 'invoice'])->latest()->paginate(10)
        ]);
    }

    public function create()
    {
        return Inertia::render('ERP/Sales/Orders/Create', [
            'customers' => Customer::all(),
            'next_so_number' => 'SO-' . date('Ymd') . '-' . Str::upper(Str::random(4))
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'so_number' => 'required|unique:sales_orders',
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
        ]);

        $order = SalesOrder::create($validated);

        // Auto generate Invoice if confirmed
        if ($request->status === 'confirmed') {
            $this->generateInvoice($order);
        }

        return redirect()->route('sales.orders.index')->with('message', 'Sales order created.');
    }

    public function confirm(SalesOrder $order)
    {
        $order->update(['status' => 'confirmed']);
        $this->generateInvoice($order);
        return redirect()->back()->with('message', 'Order confirmed and invoice generated.');
    }

    private function generateInvoice(SalesOrder $order)
    {
        return Invoice::create([
            'invoice_number' => str_replace('SO-', 'INV-', $order->so_number),
            'sales_order_id' => $order->id,
            'due_date' => now()->addDays(7),
            'total_amount' => $order->total_amount,
            'payment_status' => 'unpaid'
        ]);
    }
}
