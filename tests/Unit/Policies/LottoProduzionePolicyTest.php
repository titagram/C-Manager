<?php

namespace Tests\Unit\Policies;

use App\Enums\StatoLottoProduzione;
use App\Enums\UserRole;
use App\Models\LottoProduzione;
use App\Models\User;
use App\Policies\LottoProduzionePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LottoProduzionePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_delete_cancelled_lotto(): void
    {
        $user = User::factory()->create(['role' => UserRole::OPERATORE]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::ANNULLATO,
            'created_by' => $user->id,
        ]);

        $policy = new LottoProduzionePolicy();

        $this->assertFalse($policy->delete($user, $lotto));
        $this->assertTrue($policy->delete($admin, $lotto));
    }

    public function test_cannot_delete_non_cancelled_lotto(): void
    {
        $user = User::factory()->create();
        $lottoInLavorazione = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
        ]);
        $lottoCompletato = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::COMPLETATO,
        ]);

        $policy = new LottoProduzionePolicy();

        $this->assertFalse($policy->delete($user, $lottoInLavorazione));
        $this->assertFalse($policy->delete($user, $lottoCompletato));
    }

    public function test_operatore_cannot_delete_bozza_lotto(): void
    {
        $user = User::factory()->create(['role' => UserRole::OPERATORE]);
        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::BOZZA,
            'created_by' => $user->id,
        ]);

        $policy = new LottoProduzionePolicy();

        $this->assertFalse($policy->delete($user, $lotto));
    }

    public function test_non_creator_cannot_delete_bozza_lotto(): void
    {
        $creator = User::factory()->create(['role' => UserRole::OPERATORE]);
        $otherUser = User::factory()->create(['role' => UserRole::OPERATORE]);
        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::BOZZA,
            'created_by' => $creator->id,
        ]);

        $policy = new LottoProduzionePolicy();

        $this->assertFalse($policy->delete($otherUser, $lotto));
    }

    public function test_admin_can_delete_any_lotto(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $differentUser = User::factory()->create(['role' => UserRole::OPERATORE]);
        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::BOZZA,
            'created_by' => $differentUser->id,
        ]);

        $policy = new LottoProduzionePolicy();

        $this->assertTrue($policy->delete($admin, $lotto));
    }

    public function test_admin_can_delete_confermato_lotto(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::CONFERMATO,
            'created_by' => $user->id,
        ]);

        $policy = new LottoProduzionePolicy();

        $this->assertTrue($policy->delete($user, $lotto));
    }

    public function test_admin_can_start_confermato_lotto(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::CONFERMATO,
        ]);

        $policy = new LottoProduzionePolicy();

        $this->assertTrue($policy->start($user, $lotto));
    }

    public function test_operatore_cannot_start_bozza_lotto(): void
    {
        $user = User::factory()->create(['role' => UserRole::OPERATORE]);
        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::BOZZA,
        ]);

        $policy = new LottoProduzionePolicy();

        $this->assertFalse($policy->start($user, $lotto));
    }

    public function test_operatore_can_complete_in_lavorazione_lotto(): void
    {
        $user = User::factory()->create(['role' => UserRole::OPERATORE]);
        $lotto = LottoProduzione::factory()->create([
            'stato' => StatoLottoProduzione::IN_LAVORAZIONE,
        ]);

        $policy = new LottoProduzionePolicy();

        $this->assertTrue($policy->complete($user, $lotto));
    }

    public function test_admin_can_restore_lotto(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $lotto = LottoProduzione::factory()->create();
        
        $policy = new LottoProduzionePolicy();
        
        $this->assertTrue($policy->restore($admin, $lotto));
    }

    public function test_non_admin_cannot_restore_lotto(): void
    {
        $user = User::factory()->create(['role' => UserRole::OPERATORE]);
        $lotto = LottoProduzione::factory()->create();
        
        $policy = new LottoProduzionePolicy();
        
        $this->assertFalse($policy->restore($user, $lotto));
    }
}
