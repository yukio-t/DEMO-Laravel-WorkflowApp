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
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();

            // デモ用途：URL等で連番を見せないため
            $table->ulid('public_id')->unique();

            $table->string('title');
            $table->text('body')->nullable();

            // 状態：draft/submitted/approved/rejected など（ルールはコード側）
            $table->string('current_state', 32)->default('draft')->index();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
