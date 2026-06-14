<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('segment_id')->constrained('segments')->onDelete('cascade');
            $table->string('channel'); // whatsapp, email, sms, rcs
            $table->text('message');
            $table->string('status')->default('draft'); // draft, scheduled, running, completed
            $table->decimal('expected_revenue', 12, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
