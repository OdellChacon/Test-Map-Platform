<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outage_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('radius_km', 5, 2);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE outage_subscriptions ADD COLUMN location geography(Point, 4326)');
        DB::statement('CREATE INDEX outage_subscriptions_location_index ON outage_subscriptions USING GIST (location)');
    }

    public function down(): void
    {
        Schema::dropIfExists('outage_subscriptions');
    }
};
