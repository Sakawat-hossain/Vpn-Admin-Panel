<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('firstname', 50);
            $table->string('lastname', 50);
            $table->string('email', 100)->unique();
            $table->text('address')->nullable();
            $table->string('avatar');
            $table->string('client_id', 80)->nullable();
            $table->string('password');
            $table->string('api_token', 80)
            ->unique()
            ->nullable()
            ->default(null);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_token', 191)->nullable();
            $table->string('verification_code', 6)->nullable();
            $table->boolean('google2fa_status')->default(false)->comment('0: Disabled, 1: Active');
            $table->text('google2fa_secret')->nullable();
            $table->boolean('status')->default(true)->comment('0: Banned, 1: Active');
            $table->rememberToken();
            $table->boolean('is_viewed')->default(false);
            $table->string('dns', 50)->nullable();
            $table->float('download')->default(0);
            $table->float('upload')->default(0);
            $table->bigInteger('server_id')->unsigned()->nullable();
            $table->foreign("server_id")->references("id")->on('servers')->onDelete('cascade');
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
        Schema::dropIfExists('users');
    }
}
