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
        // One row per signed-in device of the client apps (/api/client).
        // Each session carries its own OpenVPN username/password, so signing a
        // device out also stops it connecting.
        Schema::create('vpn_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->unsigned();
            $table->string('token_hash', 64)->unique();
            $table->string('vpn_username', 40)->unique();
            $table->string('vpn_password_hash', 64);
            $table->string('device_name', 100)->nullable();
            $table->string('wg_public_key', 64)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Panel-managed OpenVPN servers: installed by the panel with its CA,
        // users log in with their own credentials (checked by the panel).
        Schema::table('servers', function (Blueprint $table) {
            $table->boolean('ovpn_managed')->default(false)->after('is_ovpn');
            $table->text('ovpn_secret')->nullable()->after('wg_password');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['ovpn_managed', 'ovpn_secret']);
        });
        Schema::dropIfExists('vpn_sessions');
    }
};
