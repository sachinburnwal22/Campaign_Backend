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
        Schema::table('ai_conversations', function (Blueprint $table) {
            $table->renameColumn('response', 'response_json');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->decimal('predicted_revenue', 12, 2)->nullable();
            $table->decimal('predicted_conversion_rate', 5, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_conversations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->renameColumn('response_json', 'response');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['predicted_revenue', 'predicted_conversion_rate']);
        });
    }
};
