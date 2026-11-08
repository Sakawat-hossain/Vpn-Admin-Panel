<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Devices signed in from the desktop app before it sent a device name were
     * stored with the Go HTTP client's User-Agent ("Go-http-client/1.1").
     * Show them as "Desktop app", like new sign-ins without a name.
     */
    public function up()
    {
        DB::table('vpn_sessions')
            ->where('device_name', 'like', 'Go-http-client%')
            ->update(['device_name' => 'Desktop app']);
    }

    public function down()
    {
        // nothing to undo: the old names carried no information
    }
};
