<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Providers\AppServiceProvider;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        // AppServiceProvider only registers the admin view composers (sidebar
        // and header counters) when the booting request is under the admin
        // path; tests boot on "/", so boot it again for an admin URL.
        $this->app->instance('request', Request::create('/' . adminPath() . '/dashboard'));
        (new AppServiceProvider($this->app))->boot();
        Cache::put('ipinfo:127.0.0.1', (object) ['status' => 'success', 'query' => '127.0.0.1'], now()->addHour());
    }

    public function test_admin_pages_use_the_tabler_fluid_vertical_layout()
    {
        $admin = Admin::first();
        $pages = [
            'dashboard' => 'Dashboard',
            'users' => 'Users',
            'servers' => 'Servers',
            'plans' => 'Pricing Plans',
            'settings/general' => 'General',
            'navigation/navbarMenu?lang=en' => 'Navbar Menu',
        ];
        foreach ($pages as $path => $text) {
            $this->actingAs($admin, 'admin')->get('/' . adminPath() . '/' . $path)->assertOk()
                ->assertSee('tabler.min.css', false)
                ->assertSee('data-bs-layout="fluid"', false)
                ->assertSee('data-bs-navbar-position="vertical"', false)
                ->assertSee($text)
                ->assertSee(route('admin.servers.index'), false)
                ->assertDontSee('assets/vendor/js/menu.js', false);
        }
    }

    public function test_form_pages_keep_the_save_button_for_their_form()
    {
        $this->actingAs(Admin::first(), 'admin')->get('/' . adminPath() . '/settings/general')->assertOk()
            ->assertSee('form="billiongroup-submited-form"', false)
            ->assertSee('id="billiongroup-submited-form"', false);
    }
}
