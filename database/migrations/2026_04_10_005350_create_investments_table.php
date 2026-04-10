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
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->enum('type', ['renda fixa', 'renda variavel', 'cripto', 'outros']);
            $table->enum('category', ['tesouro', 'CDB', 'ações', 'FII', 'outros']);
            $table->float('value', 10, 2);
            $table->string('institution');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
