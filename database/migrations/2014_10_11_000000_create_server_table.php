<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('country', 191);
            $table->string('state', 191);
            $table->string('latitude', 191);
            $table->string('longitude', 191);
            $table->tinyInteger('status');
            $table->string('ip_address', 191);
            $table->tinyInteger('recommended');
            $table->tinyInteger('is_premium')->default(0);
            $table->tinyInteger('is_ovpn')->default(0);
            $table->longText('ovpn_config');
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
        Schema::dropIfExists('servers');
    }
}
