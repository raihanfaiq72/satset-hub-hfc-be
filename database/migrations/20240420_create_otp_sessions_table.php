<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOtpSessionsTable extends Migration
{
    public function up()
    {
        Schema::create('otp_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('otp', 6);
            $table->enum('type', ['receive', 'redeem']);
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->text('qr_data')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'type', 'is_used']);
            $table->index(['otp', 'type']);
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('otp_sessions');
    }
}
