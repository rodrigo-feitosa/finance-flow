<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user')->constrained('users')->onDelete('cascade');
            $table->date('data')->nullable();
            $table->string('description')->nullable();
            $table->float('value', 10, 2)->nullable();
            $table->enum('type', ['fixa', 'variavel', 'parcelada'])->nullable();
            $table->enum('payment_method', ['dinheiro', 'pix', 'credito', 'debito'])->nullable();
            $table->enum('status', ['a pagar', 'paga'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
