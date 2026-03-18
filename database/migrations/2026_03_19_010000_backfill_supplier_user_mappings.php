<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();

        $rows = DB::table('users')
            ->where('role', 'supplier')
            ->whereNotNull('users.supplier_id')
            ->leftJoin('supplier_users', function ($join) {
                $join->on('supplier_users.user_id', '=', 'users.id')
                    ->on('supplier_users.supplier_id', '=', 'users.supplier_id');
            })
            ->whereNull('supplier_users.id')
            ->select([
                'users.supplier_id',
                'users.id as user_id',
            ])
            ->get()
            ->map(fn ($row) => [
                'supplier_id' => $row->supplier_id,
                'user_id' => $row->user_id,
                'title' => null,
                'is_primary' => true,
                'can_download' => true,
                'can_approve' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('supplier_users')->insert($rows);
        }
    }

    public function down(): void
    {
        DB::table('supplier_users')
            ->whereNull('title')
            ->where('is_primary', true)
            ->where('can_download', true)
            ->where('can_approve', false)
            ->delete();
    }
};
