<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('trip_name');
            $table->string('group_name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'completed'])->default('active');
            $table->string('currency', 10)->default('INR');
            $table->string('currency_symbol', 5)->default('₹');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('cover_color')->default('#4F46E5');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
