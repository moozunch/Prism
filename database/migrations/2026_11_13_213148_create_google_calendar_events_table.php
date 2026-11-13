<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('google_calendar_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                ->unique()
                ->constrained('tickets')
                ->cascadeOnDelete();

            $table->foreignId('google_connection_id')
                ->nullable()
                ->constrained('google_connections')
                ->nullOnDelete();

            $table->string('calendar_id')->default('primary');
            $table->string('google_event_id')->nullable();

            $table->string('sync_status', 20)
                ->default('pending');

            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_calendar_events');
    }
};