<?php

namespace App\Providers;

use App\Models\Cliente;
use App\Models\Costruzione;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\Ordine;
use App\Models\Preventivo;
use App\Models\Prodotto;
use App\Policies\ClientePolicy;
use App\Policies\CostruzionePolicy;
use App\Policies\LottoMaterialePolicy;
use App\Policies\LottoProduzionePolicy;
use App\Policies\OrdinePolicy;
use App\Policies\PreventivoPolicy;
use App\Policies\ProdottoPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        Gate::policy(Prodotto::class, ProdottoPolicy::class);
        Gate::policy(Cliente::class, ClientePolicy::class);
        Gate::policy(Costruzione::class, CostruzionePolicy::class);
        Gate::policy(Preventivo::class, PreventivoPolicy::class);
        Gate::policy(LottoMateriale::class, LottoMaterialePolicy::class);
        Gate::policy(LottoProduzione::class, LottoProduzionePolicy::class);
        Gate::policy(Ordine::class, OrdinePolicy::class);
    }
}
