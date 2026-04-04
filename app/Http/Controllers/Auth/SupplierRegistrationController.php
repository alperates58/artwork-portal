<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SupplierRegistration;
use App\Services\SupplierRegistrationMailDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierRegistrationController extends Controller
{
    public function store(Request $request, SupplierRegistrationMailDispatcher $mailDispatcher): JsonResponse
    {
        // Honeypot: bots fill the hidden website field
        if (filled($request->input('website'))) {
            return response()->json(['message' => 'Talebiniz alındı.'], 200);
        }

        $validated = $request->validate([
            'company_name'  => ['required', 'string', 'max:200'],
            'company_email' => ['required', 'email', 'max:200'],
            'contact_name'  => ['required', 'string', 'max:200'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ], [
            'company_name.required'  => 'Firma adı zorunludur.',
            'company_email.required' => 'Firma e-posta adresi zorunludur.',
            'company_email.email'    => 'Geçerli bir e-posta adresi giriniz.',
            'contact_name.required'  => 'Adı Soyadı zorunludur.',
        ]);

        $registration = SupplierRegistration::create([
            ...$validated,
            'status'     => 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);

        $mailDispatcher->queueSubmittedMail($registration);

        return response()->json([
            'message' => 'Kayıt talebiniz alındı. En kısa sürede incelenip size bilgi verilecektir.',
        ]);
    }
}
