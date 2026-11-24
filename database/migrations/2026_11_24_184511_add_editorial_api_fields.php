<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('editorial_key', 120)->nullable()->unique();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('editorial_key', 120)->nullable()->unique();
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->string('editorial_key', 120)->nullable()->unique();
        });

        Schema::create('editorial_api_events', function (Blueprint $table) {
            $table->id();
            $table->string('request_id', 120)->unique();
            $table->string('editorial_key', 120);
            $table->string('entity_type', 30);
            $table->unsignedBigInteger('entity_id');
            $table->string('operation', 30);
            $table->char('payload_hash', 64);
            $table->json('before_summary')->nullable();
            $table->json('after_summary');
            $table->json('result');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entity_type', 'entity_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('editorial_api_events');
        Schema::table('pages', fn (Blueprint $table) => $table->dropColumn('editorial_key'));
        Schema::table('posts', fn (Blueprint $table) => $table->dropColumn('editorial_key'));
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn('editorial_key');
        });
    }
};
