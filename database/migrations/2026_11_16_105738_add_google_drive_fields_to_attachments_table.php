<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {

            // Identify where the attachment is stored.
            // Existing attachments remain local.
            $table->string('storage_provider', 20)
                ->default('local');

            // Unique Google Drive file identifier.
            $table->string('google_drive_file_id')
                ->nullable()
                ->unique();

            // Link to open the file in Google Drive.
            $table->text('google_drive_url')
                ->nullable();

        });
    }

    /**
     * Remove only Google Drive metadata columns.
     */
    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {

            $table->dropUnique([
                'google_drive_file_id',
            ]);

            $table->dropColumn([
                'storage_provider',
                'google_drive_file_id',
                'google_drive_url',
            ]);

        });
    }
};