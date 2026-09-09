<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_admin')->default(false)->after('theme'));
        if (app()->environment('local')) { \Illuminate\Support\Facades\DB::table('users')->where('email', 'test@example.com')->update(['is_admin' => true]); }
    }
    public function down(): void { Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin')); }
};
