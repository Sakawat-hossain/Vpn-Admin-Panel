<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        // Deleting a server used to cascade-delete every user assigned to it
        // (and with them their subscriptions, transactions and logs).
        // Detach the users instead.
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['server_id']);
                $table->foreign('server_id')->references('id')->on('servers')->nullOnDelete();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('verification_code_sent_at')->nullable()->after('verification_code');
        });

        // Per-server wg-easy API password (stored encrypted, see Server::$casts).
        Schema::table('servers', function (Blueprint $table) {
            $table->text('wg_password')->nullable()->after('ovpn_config');
        });

        // One App Store / Google Play purchase may only ever unlock one account.
        Schema::create('iap_purchases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('platform', 20);
            $table->string('original_transaction_id', 150);
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('plan_id')->unsigned()->nullable();
            $table->string('product_id', 150)->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['platform', 'original_transaction_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();
        });

        // VPS root passwords were stored in plain text and never cleared.
        // They are no longer persisted, so wipe the ones already stored.
        DB::table('config_server_jobs')->update(['vps_password' => '']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('iap_purchases');

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('wg_password');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('verification_code_sent_at');
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['server_id']);
                $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
            });
        }
    }
};
