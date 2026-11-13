<?php

use App\Enums\ReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outage_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('radius_km', 5, 2);
            $table->string('duration_type', 20);
            $table->unsignedSmallInteger('duration_hours')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('closed_at')->nullable();
            $table->string('status', 20)->default(ReportStatus::Active->value);
            $table->string('description', 500)->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
        });

        DB::statement('ALTER TABLE outage_reports ADD COLUMN location geography(Point, 4326)');
        DB::statement('CREATE INDEX outage_reports_location_index ON outage_reports USING GIST (location)');
    }

    public function down(): void
    {
        Schema::dropIfExists('outage_reports');
    }
};
