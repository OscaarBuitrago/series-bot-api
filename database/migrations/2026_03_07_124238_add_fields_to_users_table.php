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
        Schema::table('users', function (Blueprint $table) {
            $table->integer('queries_this_month')->default(0);
            $table->enum('subscription_tier', ['free', 'pro'])->default('free');
            $table->timestamp('queries_reset_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'queries_this_month',
                'subscription_tier',
                'queries_reset_at',
            ]);
        });
    }
};
