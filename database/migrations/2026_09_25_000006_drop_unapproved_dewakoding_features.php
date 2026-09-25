<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tickets', 'epic_id')) {
            Schema::table('tickets', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('epic_id');
            });
        }

        Schema::dropIfExists('epics');
        Schema::dropIfExists('external_access');
    }

    public function down(): void
    {
        // Legacy Epic and external portal structures are intentionally not restored.
    }
};