<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $q = Payment::with('user');
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        return view('admin.payments', ['payments' => $q->latest()->paginate(40)->withQueryString()]);
    }
}
