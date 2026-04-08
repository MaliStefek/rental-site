<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login()
    {
        $response = $this->get(route('admin.dashboard')); 
        
        $response->assertRedirect(route('login'));
    }

    public function test_admins_can_access_dashboard()
    {
        $admin = User::factory()->create();
        
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $admin->roles()->attach($adminRole);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }
}