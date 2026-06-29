<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Extension;
use App\Models\Faq;
use App\Models\Server;
use App\Models\FooterMenu;
use App\Models\Language;
use App\Models\MailTemplate;
use App\Models\NavbarMenu;
use App\Models\PaymentGateway;
use App\Models\Plan;
use App\Models\SeoConfiguration;
use App\Models\Settings;
use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();

        // admin table
        Admin::truncate();
        $json = json_decode(file_get_contents(database_path('seeders/data/admins.json')), true);
        foreach ($json as $row) {
            Admin::create([
                'id' => $row['id'],
                'name' => $row['name'],
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'email' => $row['email'],
                'avatar' => $row['avatar'],
                'password' => $row['password'],
                'remember_token' => $row['remember_token'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ]);
        }

        // extensions table
        Extension::truncate();
        $json = json_decode(file_get_contents(database_path('seeders/data/extensions.json')), true);
        foreach ($json as $row) {
            $credentials = (object) json_decode($row['credentials']);
            Extension::create([
                'id' => $row['id'],
                'name' => $row['name'],
                'alias' => $row['alias'],
                'logo' => $row['logo'],
                'credentials' => $credentials,
                'instructions' => $row['instructions'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ]);
        }

        // faqs table
        Faq::truncate();
        $json = json_decode(file_get_contents(database_path('seeders/data/faqs.json')), true);
        foreach ($json as $row) {
            Faq::create([
                'id' => $row['id'],
                'lang' => $row['lang'],
                'title' => $row['title'],
                'content' => $row['content'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ]);
        }

        // footer menu table
        FooterMenu::truncate();
        $json = json_decode(file_get_contents(database_path('seeders/data/footer-menu.json')), true);
        foreach ($json as $row) {
            FooterMenu::create([
                'id' => $row['id'],
                'name' => $row['name'],
                'link' => $row['link'],
                'lang' => $row['lang'],
                'parent_id' => $row['parent_id'],
                'order' => $row['order'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ]);
        }

        // language table
        Language::truncate();
        $json = json_decode(file_get_contents(database_path('seeders/data/languages.json')), true);
        foreach ($json as $row) {
            Language::create([
                'id' => $row['id'],
                'name' => $row['name'],
                'flag' => $row['flag'],
                'code' => $row['code'],
                'direction' => $row['direction'],
                'sort_id' => $row['sort_id'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ]);
        }

        // mail template table
        MailTemplate::truncate();
        $json = json_decode(file_get_contents(database_path('seeders/data/mail-templates.json')), true);
        foreach ($json as $row) {
            $shortcodes = (object) json_decode($row['shortcodes']);
            MailTemplate::create([
                'id' => $row['id'],
                'lang' => $row['lang'],
                'alias' => $row['alias'],
                'name' => $row['name'],
                'subject' => $row['subject'],
                'body' => $row['body'],
                'shortcodes' => $shortcodes,
                'status' => $row['status'],
            ]);
        }

        // navbar menu table
        NavbarMenu::truncate();
        $json = json_decode(file_get_contents(database_path('seeders/data/navbar-menu.json')), true);
        foreach ($json as $row) {
            NavbarMenu::create([
                'id' => $row['id'],
                'name' => $row['name'],
                'link' => $row['link'],
                'lang' => $row['lang'],
                'parent_id' => $row['parent_id'],
                'order' => $row['order'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ]);
        }

        // payment gateways table
        PaymentGateway::truncate();
        $json = json_decode(file_get_contents(database_path('seeders/data/payment-gateways.json')), true);
        foreach ($json as $row) {
            $supported_currencies = json_decode($row['supported_currencies']);
            $credentials = (object) json_decode($row['credentials']);
            PaymentGateway::create([
                'id' => $row['id'],
                'name' => $row['name'],
                'alias' => $row['alias'],
                'handler' => $row['handler'],
                'logo' => $row['logo'],
                'supported_currencies' => $supported_currencies,
                'fees' => $row['fees'],
                'min' => $row['min'],
                'test_mode' => $row['test_mode'],
                'credentials' => $credentials,
                'instructions' => $row['instructions'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ]);
        }

        // plans table
        Plan::truncate();
        $json = json_decode(file_get_contents(database_path('seeders/data/plans.json')), true);
        foreach ($json as $row) {
            Plan::create([
                'id' => $row['id'],
                'name' => $row['name'],
                'product_id' => $row['product_id'],
                'interval' => $row['interval'],
                'price' => $row['price'],
                'expiration' => $row['expiration'],
                'advertisements' => $row['advertisements'],
                'custom_features' => $row['custom_features'],
                'is_free' => $row['is_free'],
                'login_require' => $row['login_require'],
                'is_featured' => $row['is_featured'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ]);
        }

        // Server table
        Server::truncate();
        $json = json_decode(file_get_contents(database_path('seeders/data/server.json')), true);
        foreach ($json as $row) {
            Server::create([
                'country' => $row['country'],
                'state' => $row['state'],
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'status' => $row['status'],
                'ip_address' => $row['ip_address'],
                'recommended' => $row['recommended'],
                'is_premium' => $row['is_premium'],
                'is_ovpn' => $row['is_ovpn'],
                'ovpn_config' => $row['ovpn_config'],
            ]);
        }

        // seo configurations table
        SeoConfiguration::truncate();
        $json = json_decode(file_get_contents(database_path('seeders/data/seo-configurations.json')), true);
        foreach ($json as $row) {
            SeoConfiguration::create([
                'id' => $row['id'],
                'lang' => $row['lang'],
                'title' => $row['title'],
                'description' => $row['description'],
                'keywords' => $row['keywords'],
                'robots_index' => $row['robots_index'],
                'robots_follow_links' => $row['robots_follow_links'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ]);
        }

        // settings table
        Settings::truncate();
        $json = json_decode(file_get_contents(database_path('seeders/data/settings.json')), true);
        foreach ($json as $row) {
            $value = (object) $row['value'];
            Settings::create([
                'id' => $row['id'],
                'key' => $row['key'],
                'value' => $value
            ]);
        }

        // country table
        Country::truncate();
        $json = json_decode(file_get_contents(database_path('seeders/data/countries.json')), true);
        foreach ($json as $row) {
            Country::create([
                'id' => $row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'capital' => $row['capital'],
                'continent' => $row['continent'],
                'continent_code' => $row['continent_code'],
                'phone' => $row['phone'],
                'currency' => $row['currency'],
                'symbol' => $row['symbol'],
                'alpha_3' => $row['alpha_3'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ]);
        }
    }
}
