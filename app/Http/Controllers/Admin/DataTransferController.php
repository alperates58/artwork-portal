<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use SimpleXMLElement;

class DataTransferController extends Controller
{
    private function checkAccess(): void
    {
        abort_if(! auth()->user()->isAdmin(), 403);
    }

    public function index(): View
    {
        $this->checkAccess();

        $importedIds = $this->getImportedIds();

        $importedCount = [
            'suppliers'      => count($importedIds['suppliers'] ?? []),
            'users'          => count($importedIds['users'] ?? []),
            'purchase_orders' => count($importedIds['purchase_orders'] ?? []),
        ];

        return view('admin.data-transfer.index', compact('importedCount'));
    }

    public function export(): Response
    {
        $this->checkAccess();

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><portal_export/>');
        $xml->addAttribute('exported_at', now()->toIso8601String());
        $xml->addAttribute('version', '1');

        // ── Suppliers ────────────────────────────────────────────────
        $suppliersNode = $xml->addChild('suppliers');
        $suppliers = Supplier::withTrashed()->with('allUsers')->get();

        foreach ($suppliers as $supplier) {
            $sNode = $suppliersNode->addChild('supplier');
            $sNode->addAttribute('ref', $supplier->code ?: 'sup_' . $supplier->id);
            $sNode->addChild('name', htmlspecialchars($supplier->name));
            $sNode->addChild('code', htmlspecialchars((string) $supplier->code));
            $sNode->addChild('email', htmlspecialchars((string) $supplier->email));
            $sNode->addChild('phone', htmlspecialchars((string) $supplier->phone));
            $sNode->addChild('address', htmlspecialchars((string) $supplier->address));
            $sNode->addChild('is_active', $supplier->is_active ? '1' : '0');
            $sNode->addChild('notes', htmlspecialchars((string) $supplier->notes));
            $sNode->addChild('created_at', $supplier->created_at?->toIso8601String());
            $sNode->addChild('updated_at', $supplier->updated_at?->toIso8601String());
            if ($supplier->deleted_at) {
                $sNode->addChild('deleted_at', $supplier->deleted_at->toIso8601String());
            }

            // Pivot: users linked to this supplier
            $mappingsNode = $sNode->addChild('user_mappings');
            foreach ($supplier->allUsers as $u) {
                $mNode = $mappingsNode->addChild('mapping');
                $mNode->addAttribute('user_email', $u->email);
                $mNode->addChild('title', htmlspecialchars((string) $u->pivot->title));
                $mNode->addChild('is_primary', $u->pivot->is_primary ? '1' : '0');
                $mNode->addChild('can_download', $u->pivot->can_download ? '1' : '0');
                $mNode->addChild('can_approve', $u->pivot->can_approve ? '1' : '0');
            }
        }

        // ── Non-admin users ───────────────────────────────────────────
        $usersNode = $xml->addChild('users');
        $users = User::where('role', '!=', 'admin')
            ->with('department:id,name')
            ->get();

        foreach ($users as $user) {
            $uNode = $usersNode->addChild('user');
            $uNode->addAttribute('email', $user->email);
            $uNode->addChild('name', htmlspecialchars($user->name));
            $uNode->addChild('email', htmlspecialchars($user->email));
            $uNode->addChild('role', $user->role->value);
            $uNode->addChild('is_active', $user->is_active ? '1' : '0');
            $uNode->addChild('department_name', htmlspecialchars((string) ($user->department?->name ?? '')));
            // supplier_id reference by code
            $supplierCode = $user->supplier_id
                ? (Supplier::withTrashed()->find($user->supplier_id)?->code ?: 'sup_' . $user->supplier_id)
                : '';
            $uNode->addChild('supplier_ref', htmlspecialchars((string) $supplierCode));
            $uNode->addChild('created_at', $user->created_at?->toIso8601String());
            $uNode->addChild('updated_at', $user->updated_at?->toIso8601String());
        }

        // ── Purchase orders ───────────────────────────────────────────
        $ordersNode = $xml->addChild('purchase_orders');
        $orders = PurchaseOrder::with(['lines', 'createdBy:id,email', 'supplier:id,code'])->get();

        foreach ($orders as $order) {
            $oNode = $ordersNode->addChild('purchase_order');
            $oNode->addAttribute('order_no', htmlspecialchars($order->order_no));
            $oNode->addChild('supplier_ref', htmlspecialchars($order->supplier?->code ?: 'sup_' . $order->supplier_id));
            $oNode->addChild('status', $order->status);
            $oNode->addChild('order_date', $order->order_date?->format('Y-m-d'));
            $oNode->addChild('due_date', $order->due_date?->format('Y-m-d'));
            $oNode->addChild('notes', htmlspecialchars((string) $order->notes));
            $oNode->addChild('created_by_email', htmlspecialchars((string) ($order->createdBy?->email ?? '')));
            $oNode->addChild('created_at', $order->created_at->toIso8601String());
            $oNode->addChild('updated_at', $order->updated_at->toIso8601String());

            $linesNode = $oNode->addChild('lines');
            foreach ($order->lines as $line) {
                $lNode = $linesNode->addChild('line');
                $lNode->addChild('line_no', (string) $line->line_no);
                $lNode->addChild('product_code', htmlspecialchars((string) $line->product_code));
                $lNode->addChild('description', htmlspecialchars((string) $line->description));
                $lNode->addChild('quantity', (string) $line->quantity);
                $lNode->addChild('unit', htmlspecialchars((string) $line->unit));
                $lNode->addChild('artwork_status', $line->artwork_status?->value ?? 'pending');
                $lNode->addChild('notes', htmlspecialchars((string) $line->notes));
                $lNode->addChild('created_at', $line->created_at->toIso8601String());
            }
        }

        $content = $xml->asXML();

        return response($content, 200, [
            'Content-Type'        => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="portal-export-' . now()->format('Y-m-d') . '.xml"',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->checkAccess();

        $request->validate([
            'xml_file' => ['required', 'file', 'mimetypes:application/xml,text/xml,text/plain', 'max:10240'],
        ]);

        $content = file_get_contents($request->file('xml_file')->getRealPath());

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);

        if ($xml === false) {
            return back()->withErrors(['xml_file' => 'Geçersiz XML dosyası.']);
        }

        $importedIds = $this->getImportedIds();

        DB::transaction(function () use ($xml, &$importedIds) {
            // ── Suppliers ─────────────────────────────────────────────
            $supplierMap = []; // ref => id

            foreach ($xml->suppliers->supplier ?? [] as $s) {
                $ref  = (string) $s['ref'];
                $code = (string) $s->code ?: null;

                $existing = $code ? Supplier::withTrashed()->where('code', $code)->first() : null;

                if ($existing) {
                    $supplierMap[$ref] = $existing->id;
                    continue;
                }

                $newSupplier = Supplier::create([
                    'name'      => (string) $s->name,
                    'code'      => $code,
                    'email'     => (string) $s->email ?: null,
                    'phone'     => (string) $s->phone ?: null,
                    'address'   => (string) $s->address ?: null,
                    'is_active' => ((string) $s->is_active) === '1',
                    'notes'     => (string) $s->notes ?: null,
                ]);

                if (!empty((string) $s->created_at)) {
                    $newSupplier->timestamps = false;
                    $newSupplier->created_at = (string) $s->created_at;
                    $newSupplier->updated_at = (string) $s->updated_at;
                    $newSupplier->save();
                    $newSupplier->timestamps = true;
                }

                $importedIds['suppliers'][] = $newSupplier->id;
                $supplierMap[$ref] = $newSupplier->id;
            }

            // ── Non-admin users ───────────────────────────────────────
            $defaultPassword = Hash::make('Import@' . now()->year);

            foreach ($xml->users->user ?? [] as $u) {
                $email = (string) $u->email;

                if (User::where('email', $email)->exists()) {
                    continue;
                }

                $supplierRef = (string) $u->supplier_ref;
                $supplierId  = $supplierRef && isset($supplierMap[$supplierRef]) ? $supplierMap[$supplierRef] : null;

                $deptName = (string) $u->department_name;
                $deptId   = null;
                if ($deptName) {
                    $dept   = \App\Models\Department::where('name', $deptName)->first();
                    $deptId = $dept?->id;
                }

                $newUser = new User([
                    'name'          => (string) $u->name,
                    'email'         => $email,
                    'password'      => $defaultPassword,
                    'role'          => (string) $u->role,
                    'is_active'     => ((string) $u->is_active) === '1',
                    'supplier_id'   => $supplierId,
                    'department_id' => $deptId,
                ]);

                $newUser->save();

                if (!empty((string) $u->created_at)) {
                    DB::table('users')->where('id', $newUser->id)->update([
                        'created_at' => (string) $u->created_at,
                        'updated_at' => (string) $u->updated_at,
                    ]);
                }

                $importedIds['users'][] = $newUser->id;
            }

            // ── Supplier user mappings ────────────────────────────────
            foreach ($xml->suppliers->supplier ?? [] as $s) {
                $ref = (string) $s['ref'];
                $supplierId = $supplierMap[$ref] ?? null;
                if (! $supplierId) continue;

                foreach ($s->user_mappings->mapping ?? [] as $m) {
                    $userEmail = (string) $m['user_email'];
                    $user = User::where('email', $userEmail)->first();
                    if (! $user) continue;

                    $alreadyLinked = DB::table('supplier_users')
                        ->where('supplier_id', $supplierId)
                        ->where('user_id', $user->id)
                        ->exists();

                    if (! $alreadyLinked) {
                        DB::table('supplier_users')->insert([
                            'supplier_id'  => $supplierId,
                            'user_id'      => $user->id,
                            'title'        => (string) $m->title ?: null,
                            'is_primary'   => ((string) $m->is_primary) === '1',
                            'can_download' => ((string) $m->can_download) === '1',
                            'can_approve'  => ((string) $m->can_approve) === '1',
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ]);
                    }
                }
            }

            // ── Purchase orders ───────────────────────────────────────
            foreach ($xml->purchase_orders->purchase_order ?? [] as $o) {
                $orderNo = (string) $o['order_no'];

                if (PurchaseOrder::where('order_no', $orderNo)->exists()) {
                    continue;
                }

                $supplierRef = (string) $o->supplier_ref;
                $supplierId  = $supplierMap[$supplierRef] ?? null;
                if (! $supplierId) continue;

                $createdByEmail = (string) $o->created_by_email;
                $createdBy = $createdByEmail ? User::where('email', $createdByEmail)->value('id') : null;

                $newOrder = new PurchaseOrder([
                    'order_no'    => $orderNo,
                    'supplier_id' => $supplierId,
                    'status'      => (string) $o->status,
                    'order_date'  => (string) $o->order_date ?: null,
                    'due_date'    => (string) $o->due_date ?: null,
                    'notes'       => (string) $o->notes ?: null,
                    'created_by'  => $createdBy,
                ]);

                $newOrder->save();

                if (!empty((string) $o->created_at)) {
                    DB::table('purchase_orders')->where('id', $newOrder->id)->update([
                        'created_at' => (string) $o->created_at,
                        'updated_at' => (string) $o->updated_at,
                    ]);
                }

                $importedIds['purchase_orders'][] = $newOrder->id;

                foreach ($o->lines->line ?? [] as $l) {
                    $lineData = [
                        'purchase_order_id' => $newOrder->id,
                        'line_no'           => (int)(string) $l->line_no,
                        'product_code'      => (string) $l->product_code ?: null,
                        'description'       => (string) $l->description ?: null,
                        'quantity'          => (int)(string) $l->quantity,
                        'unit'              => (string) $l->unit ?: null,
                        'artwork_status'    => (string) $l->artwork_status ?: 'pending',
                        'notes'             => (string) $l->notes ?: null,
                    ];

                    $createdAtLine = (string) $l->created_at;
                    $lineRecord = \App\Models\PurchaseOrderLine::create($lineData);

                    if ($createdAtLine) {
                        DB::table('purchase_order_lines')->where('id', $lineRecord->id)->update([
                            'created_at' => $createdAtLine,
                            'updated_at' => $createdAtLine,
                        ]);
                    }
                }
            }
        });

        $this->saveImportedIds($importedIds);

        return back()->with('success', 'İçe aktarma tamamlandı. Tedarikçiler, kullanıcılar ve siparişler yüklendi.');
    }

    public function destroyImported(): RedirectResponse
    {
        $this->checkAccess();

        $importedIds = $this->getImportedIds();

        DB::transaction(function () use ($importedIds) {
            $orderIds = $importedIds['purchase_orders'] ?? [];
            if ($orderIds) {
                \App\Models\PurchaseOrderLine::whereIn('purchase_order_id', $orderIds)->delete();
                PurchaseOrder::whereIn('id', $orderIds)->delete();
            }

            $userIds = $importedIds['users'] ?? [];
            if ($userIds) {
                DB::table('supplier_users')->whereIn('user_id', $userIds)->delete();
                User::whereIn('id', $userIds)->delete();
            }

            $supplierIds = $importedIds['suppliers'] ?? [];
            if ($supplierIds) {
                DB::table('supplier_users')->whereIn('supplier_id', $supplierIds)->delete();
                Supplier::whereIn('id', $supplierIds)->forceDelete();
            }
        });

        SystemSetting::where('group', 'data_transfer')->where('key', 'imported_ids')->delete();

        return back()->with('success', 'İçe aktarılan tüm veriler silindi.');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function getImportedIds(): array
    {
        $setting = SystemSetting::where('group', 'data_transfer')->where('key', 'imported_ids')->first();
        if (! $setting) return [];

        return json_decode($setting->value, true) ?? [];
    }

    private function saveImportedIds(array $ids): void
    {
        SystemSetting::updateOrCreate(
            ['group' => 'data_transfer', 'key' => 'imported_ids'],
            ['value' => json_encode($ids), 'is_encrypted' => false]
        );
    }
}
