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
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('call_id');
            $table->string('caller')->nullable();
            $table->string('callee')->nullable();
            $table->string('callerNumber')->nullable();
            $table->string('calleeNumber')->nullable();
            $table->longText('inputs')->nullable();
            $table->string('state')->nullable();
            $table->string('direction')->nullable();
            $table->string('type')->nullable();
            $table->string('timestamp')->nullable();
            $table->string('duration')->nullable();
            $table->longText('agents')->nullable();
            $table->longText('recording')->nullable();
            $table->boolean('is_added')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
