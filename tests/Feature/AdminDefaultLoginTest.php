<?php

namespace Tests\Feature;

use App\Filament\Auth\Login;
use App\Models\User;
use Livewire\Livewire;
use Tests\CrmTestCase;

class AdminDefaultLoginTest extends CrmTestCase
{
    public function test_seed_creates_default_super_admin(): void
    {
        $user = User::query()->where('email', 'admin@admin.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('super_admin'));
        $this->assertTrue($user->is_active);
    }

    public function test_admin_can_log_in_with_short_username_and_password(): void
    {
        Livewire::test(Login::class)
            ->set('data.email', 'admin')
            ->set('data.password', 'admin')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs(User::query()->where('email', 'admin@admin.com')->firstOrFail());
    }
}
