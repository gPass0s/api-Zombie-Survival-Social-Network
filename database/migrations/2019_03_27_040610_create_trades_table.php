<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       
      
        Schema::create('trades', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('offer_id_01')->unsigned();
            $table->bigInteger('offer_id_02')->unsigned();
            $table->bigInteger('user_id_01')->unsigned();
            $table->bigInteger('user_id_02')->unsigned();
            $table->boolean('status_user_id_01')->default(FALSE);
            $table->boolean('status_user_id_02')->default(FALSE);
            $table->boolean('status')->default(FALSE);
            $table->timestamps();


            $table->foreign('offer_id_01')->references('id')->on('offers');
            $table->foreign('offer_id_02')->references('id')->on('offers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trades');
    }
}
