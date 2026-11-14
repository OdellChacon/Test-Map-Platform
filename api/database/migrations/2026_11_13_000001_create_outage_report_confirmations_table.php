<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outage_report_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outage_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['outage_report_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outage_report_confirmations');
    }
};
