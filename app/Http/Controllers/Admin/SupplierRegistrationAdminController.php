<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierRegistration;
use App\Models\User;
use App\Services\SupplierRegistrationMailDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierRegistrationAdminController extends Controller
{
    public function __construct(
        private SupplierRegistrationMailDispatcher $mailDispatcher,
    ) {}

    public function index(Request $request): View
    {
        $registrations = SupplierRegistration::query()
            ->with(['reviewedBy:id,name', 'user:id,name,email'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('company_name', 'like', "%{$request->search}%")
                    ->orWhere('company_email', 'like', "%{$request->search}%")
                    ->orWhere('contact_name', 'like', "%{$request->search}%");
            }))
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $pendingCount = SupplierRegistration::pending()->count();
        $suppliers = Supplier::active()->orderBy('name')->pluck('name', 'id');

        return view('admin.supplier-registrations.index', compact('registrations', 'pendingCount', 'suppliers'));
    }

    public function approve(Request $request, SupplierRegistration $registration): RedirectResponse
    {
        abort_if(! $registration->isPending(), 422, 'Bu talep zaten işleme alınmış.');

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
        ], [
            'password.required' => 'Şifre zorunludur.',
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
            'password.confirmed' => 'Şifreler eşleşmiyor.',
        ]);

        $user = User::create([
            'name' => $registration->contact_name,
            'email' => $registration->company_email,
            'password' => $validated['password'],
            'role' => UserRole::SUPPLIER->value,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'is_active' => true,
        ]);

        if ($user->supplier_id) {
            $user->supplierMappings()->updateOrCreate(
                ['supplier_id' => $user->supplier_id],
                ['title' => null, 'is_primary' => true, 'can_download' => true, 'can_approve' => false]
            );
        }

        $registration->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'user_id' => $user->id,
        ]);

        $registration->loadMissing('reviewedBy:id,name');

        $this->mailDispatcher->queueApprovedMail($registration, $user);

        return redirect()
            ->route('admin.supplier-registrations.index')
            ->with('success', "\"{$registration->contact_name}\" kaydı onaylandı ve kullanıcı oluşturuldu.");
    }

    public function reject(Request $request, SupplierRegistration $registration): RedirectResponse
    {
        abort_if(! $registration->isPending(), 422, 'Bu talep zaten işleme alınmış.');

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ], [
            'rejection_reason.required' => 'Red gerekçesi zorunludur.',
        ]);

        $registration->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.supplier-registrations.index')
            ->with('success', "\"{$registration->contact_name}\" kaydı reddedildi.");
    }

    public function sendWelcomeMail(SupplierRegistration $registration): RedirectResponse
    {
        abort_if(! $registration->isApproved(), 422, 'Sadece onaylı kayıtlara hoş geldiniz maili gönderilebilir.');
        abort_if(! $registration->user, 404, 'İlişkili kullanıcı bulunamadı.');

        $registration->loadMissing('reviewedBy:id,name');

        $queued = $this->mailDispatcher->queueApprovedMailManually($registration, $registration->user);

        if (! $queued) {
            return back()->with('error', 'Mail kuyruğa alınamadı. Lütfen mail ayarlarınızı kontrol edin.');
        }

        return back()->with('success', "Onay maili \"{$registration->company_email}\" adresi için kuyruğa alındı.");
    }
}
