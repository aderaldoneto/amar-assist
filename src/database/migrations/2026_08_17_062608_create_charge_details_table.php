<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charge_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('charge_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('barcode')->nullable();
            $table->string('pix_key')->nullable();

            $table->string('card_holder_name')->nullable();
            $table->string('card_brand', 30)->nullable();
            $table->char('card_last_four', 4)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('charge_details');
    }
};
