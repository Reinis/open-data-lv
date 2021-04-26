<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecordsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('records', function (Blueprint $table) {
            $table->id();
            $table->integer('_id');
            $table->timestamp('date');
            $table->text('territory_name');
            $table->text('territory_code');
            $table->integer('confirmed_cases', false, true);
            $table->integer('active_cases', false, true);
            $table->integer('cumulative_cases', false, true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('records');
    }
}
