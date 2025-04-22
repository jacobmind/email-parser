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
        Schema::create('successful_emails', function (Blueprint $table) {
            $table->id();
            $table->unsignedMediumInteger('affiliate_id');
            $table->text('envelope');
            $table->string('from');
            $table->text('subject');
            $table->string('dkim')->nullable();
            $table->string('SPF')->nullable();
            $table->float('spam_score')->nullable();
            $table->longText('email');
            $table->longText('raw_text');
            $table->string('sender_ip')->nullable();
            $table->text('to');
            $table->unsignedInteger('timestamp');

            $table->index('affiliate_id', 'affiliate_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('successful_emails');
    }
};
