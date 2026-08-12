<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emis_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('total_synced')->default(0);
            $table->string('status')->default('success');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emis_sync_logs');
    }
};
