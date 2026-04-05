<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('user_id')->nullable(); // null for system actions
            $table->string('action');                          // e.g. 'invoice.sent'
            $table->string('subject_type')->nullable();        // e.g. 'App\Models\Invoice'
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('meta')->nullable();                  // extra context (name, number, etc.)
            $table->timestamp('created_at');

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
