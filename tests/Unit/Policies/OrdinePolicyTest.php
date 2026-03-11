<?php

namespace Tests\Unit\Policies;

use App\Enums\StatoOrdine;
use App\Enums\UserRole;
use App\Models\Ordine;
use App\Models\User;
use App\Policies\OrdinePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdinePolicyTest extends TestCase
{
    use RefreshDatabase;

    private OrdinePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new OrdinePolicy();
    }

    public function test_only_admin_can_view_any(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $user = User::factory()->create(['role' => UserRole::OPERATORE]);

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertFalse($this->policy->viewAny($user));
    }

    public function test_only_admin_can_view(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $user = User::factory()->create(['role' => UserRole::OPERATORE]);
        $ordine = Ordine::factory()->create();

        $this->assertTrue($this->policy->view($admin, $ordine));
        $this->assertFalse($this->policy->view($user, $ordine));
    }

    public function test_only_admin_can_create(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $user = User::factory()->create(['role' => UserRole::OPERATORE]);

        $this->assertTrue($this->policy->create($admin));
        $this->assertFalse($this->policy->create($user));
    }

    public function test_admin_can_update_any_ordine(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $otherUser = User::factory()->create();
        $ordine = Ordine::factory()->create([
            'created_by' => $otherUser->id,
            'stato' => StatoOrdine::CONFERMATO,
        ]);

        $this->assertTrue($this->policy->update($admin, $ordine));
    }

    public function test_operatore_cannot_update_own_editable_ordine(): void
    {
        $user = User::factory()->create(['role' => UserRole::OPERATORE]);
        $ordine = Ordine::factory()->create([
            'created_by' => $user->id,
            'stato' => StatoOrdine::CONFERMATO,
        ]);

        $this->assertFalse($this->policy->update($user, $ordine));
    }

    public function test_operatore_cannot_update_non_editable_ordine(): void
    {
        $user = User::factory()->create(['role' => UserRole::OPERATORE]);
        $ordine = Ordine::factory()->create([
            'created_by' => $user->id,
            'stato' => StatoOrdine::CONSEGNATO,
        ]);

        $this->assertFalse($this->policy->update($user, $ordine));
    }

    public function test_admin_can_delete_any_ordine(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $otherUser = User::factory()->create();
        $ordine = Ordine::factory()->create([
            'created_by' => $otherUser->id,
        ]);

        $this->assertTrue($this->policy->delete($admin, $ordine));
    }

    public function test_operatore_cannot_delete_own_editable_ordine(): void
    {
        $user = User::factory()->create(['role' => UserRole::OPERATORE]);
        $ordine = Ordine::factory()->create([
            'created_by' => $user->id,
            'stato' => StatoOrdine::CONFERMATO,
        ]);

        $this->assertFalse($this->policy->delete($user, $ordine));
    }

    public function test_admin_can_change_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $ordine = Ordine::factory()->create();

        $this->assertTrue($this->policy->changeStatus($admin, $ordine));
    }

    public function test_operatore_cannot_change_status(): void
    {
        $user = User::factory()->create(['role' => UserRole::OPERATORE]);
        $ordine = Ordine::factory()->create(['created_by' => $user->id]);

        $this->assertFalse($this->policy->changeStatus($user, $ordine));
    }

    public function test_only_admin_can_restore(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $user = User::factory()->create(['role' => UserRole::OPERATORE]);
        $ordine = Ordine::factory()->create(['created_by' => $user->id]);

        $this->assertTrue($this->policy->restore($admin, $ordine));
        $this->assertFalse($this->policy->restore($user, $ordine));
    }

    public function test_only_admin_can_force_delete(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $user = User::factory()->create(['role' => UserRole::OPERATORE]);
        $ordine = Ordine::factory()->create(['created_by' => $user->id]);

        $this->assertTrue($this->policy->forceDelete($admin, $ordine));
        $this->assertFalse($this->policy->forceDelete($user, $ordine));
    }
}
