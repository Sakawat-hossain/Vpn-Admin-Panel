<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Names the vendor package and earlier installs left in the settings.
    private const OLD_NAMES = ['billiongroup', 'ovpnplus', 'wg-backend', 'wireguard-backend', 'laravel'];

    /**
     * The site name (page titles, footers, e-mails) and the e-mail sender name
     * become "OVPNPlus" when they still hold an old or default name. A name the
     * owner chose himself is kept.
     */
    public function up()
    {
        $this->rename('general', 'site_name');
        $this->rename('smtp', 'from_name');
    }

    public function down()
    {
        // nothing to undo: the old names were placeholders
    }

    private function rename(string $key, string $field): void
    {
        $row = DB::table('settings')->where('key', $key)->first();
        if (!$row || $row->value === null) {
            return;
        }
        $value = json_decode($row->value, true);
        if (!is_array($value) || !array_key_exists($field, $value)) {
            return;
        }
        $current = strtolower(trim((string) $value[$field]));
        if ($current !== '' && !in_array($current, self::OLD_NAMES, true)) {
            return;
        }
        $value[$field] = 'OVPNPlus';
        DB::table('settings')->where('key', $key)->update(['value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    }
};
