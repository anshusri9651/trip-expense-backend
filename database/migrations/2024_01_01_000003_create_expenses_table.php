<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('paid_by_member_id')->constrained('trip_members')->onDelete('cascade');
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->enum('category', [
                'food', 'hotel', 'transport', 'shopping',
                'entertainment', 'tickets', 'fuel', 'other'
            ])->default('other');
            $table->date('expense_date');
            $table->time('expense_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
