<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Settings;
use App\Models\PaymentGateway;
use App\Models\Extension;
use App\Models\MailTemplate;
use Illuminate\Support\Facades\Schema;

class PatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        
        // settings table
        Settings::truncate();
        $json = json_decode(file_get_contents(database_path('seeders/data/settings.json')), true);
        foreach ($json as $row) {
            $value = (object) $row['value'];
            Settings::create([
                'id'                 => $row['id'],
                'key'                => $row['key'],
                'value'              => $value
            ]);
        }

        // payment gateways table
        PaymentGateway::truncate();
        $json = json_decode(file_get_contents(database_path('seeders/data/payment-gateways.json')), true);
        foreach ($json as $row) {
            $supported_currencies = json_decode($row['supported_currencies']);
            $credentials = (object) json_decode($row['credentials']);
            PaymentGateway::create([
                'id'                   => $row['id'],
                'name'                 => $row['name'],
                'alias'                => $row['alias'],
                'handler'              => $row['handler'],
                'logo'                 => $row['logo'],
                'supported_currencies' => $supported_currencies,
                'fees'                 => $row['fees'],
                'min'                  => $row['min'],
                'test_mode'            => $row['test_mode'],
                'credentials'          => $credentials,
                'instructions'         => $row['instructions'],
                'status'               => $row['status'],
                'created_at'           => $row['created_at'],
                'updated_at'           => $row['updated_at'],
            ]);
        }

        // extensions table
        Extension::truncate();
        $json = json_decode(file_get_contents(database_path('seeders/data/extensions.json')), true);
        foreach ($json as $row) {
            $credentials = (object) json_decode($row['credentials']);
            Extension::create([
                'id'                  => $row['id'],
                'name'                => $row['name'],
                'alias'               => $row['alias'],
                'logo'                => $row['logo'],
                'credentials'         => $credentials,
                'instructions'        => $row['instructions'],
                'status'              => $row['status'],
                'created_at'          => $row['created_at'],
                'updated_at'          => $row['updated_at'],
            ]);
        }

        // mail template table
        MailTemplate::truncate();
        $json = json_decode(file_get_contents(database_path('seeders/data/mail-templates.json')), true);
        foreach ($json as $row) {
            $shortcodes = (object) json_decode($row['shortcodes']);
            MailTemplate::create([
                'id'          => $row['id'],
                'lang'        => $row['lang'],
                'alias'       => $row['alias'],
                'name'        => $row['name'],
                'subject'     => $row['subject'],
                'body'        => $row['body'],
                'shortcodes'  => $shortcodes,
                'status'      => $row['status'],
            ]);
        }
    }
}
