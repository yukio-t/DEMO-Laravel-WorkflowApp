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
        Schema::create('workflow_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();

            $table->string('from_state', 32)->nullable();
            $table->string('to_state', 32);

            $table->foreignId('acted_by')->constrained('users')->cascadeOnDelete();

            $table->text('comment')->nullable();
            $table->json('meta')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['workflow_id', 'created_at']);
            $table->index(['acted_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_histories');
    }
};
