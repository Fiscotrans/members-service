<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 150)->unique();
            $table->string('phone', 30)->nullable();
            $table->string('cpf_cnpj', 20)->nullable();
            $table->string('company_name', 150)->nullable();
            $table->string('password')->nullable();
            $table->enum('status', ['active', 'inactive', 'blocked', 'pending'])->default('pending');
            $table->string('origin', 50)->default('wordpress');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};

