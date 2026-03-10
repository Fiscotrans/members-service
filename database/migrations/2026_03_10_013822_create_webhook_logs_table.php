<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 100);
            $table->string('event', 150)->nullable();
            $table->json('payload')->nullable();
            $table->integer('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->string('status', 50)->default('received');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};

