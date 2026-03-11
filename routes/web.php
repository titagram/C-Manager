<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FitokExportController;
use App\Http\Controllers\MagazzinoLottoMovimentiController;
use App\Http\Controllers\PreventivoPdfController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
    $credentials = request()->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (auth()->attempt($credentials)) {
        request()->session()->regenerate();
        $user = auth()->user();
        $fallbackRoute = $user?->isAdmin()
            ? route('dashboard')
            : route('lotti.index');

        return redirect()->intended($fallbackRoute);
    }

    return back()->withErrors([
        'email' => 'Credenziali non valide.',
    ]);
});

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// Protected routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::middleware(['role:admin'])->get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Magazzino
    Route::prefix('magazzino')->name('magazzino.')->group(function () {
        Route::get('/', function () {
            return view('magazzino.index');
        })->name('index');
        Route::get('/aggregato', function () {
            return view('magazzino.aggregato');
        })->name('aggregato');
        Route::get('/{lotto}/movimenti', MagazzinoLottoMovimentiController::class)->name('movimenti');
        Route::middleware(['role:admin'])->group(function () {
            Route::get('/carico', function () {
                return view('magazzino.carico');
            })->name('carico');
            Route::get('/scarico', function () {
                return view('magazzino.scarico');
            })->name('scarico');
        });
    });

    // Registro FITOK
    Route::middleware(['role:admin'])->prefix('fitok')->name('fitok.')->group(function () {
        Route::get('/', function () {
            return view('reports.fitok');
        })->name('index');
        Route::get('/export/pdf', [FitokExportController::class, 'pdf'])->name('export.pdf');
        Route::get('/export/excel', [FitokExportController::class, 'excel'])->name('export.excel');
    });

    // Lotti Produzione
    Route::prefix('lotti')->name('lotti.')->group(function () {
        Route::get('/', function () {
            return view('lotti.index');
        })->name('index');
        Route::middleware(['role:admin'])->group(function () {
            Route::get('/create', function () {
                return view('lotti.create');
            })->name('create');
            Route::get('/{lotto}/edit', function (\App\Models\LottoProduzione $lotto) {
                return view('lotti.edit', compact('lotto'));
            })->name('edit');
        });
        Route::get('/{lotto}', function (\App\Models\LottoProduzione $lotto) {
            return view('lotti.show', compact('lotto'));
        })->name('show');
    });

    // Preventivi
    Route::middleware(['role:admin'])->prefix('preventivi')->name('preventivi.')->group(function () {
        Route::get('/', function () {
            return view('preventivi.index');
        })->name('index');
        Route::get('/create', function () {
            return view('preventivi.create');
        })->name('create');
        Route::get('/{preventivo}/edit', function (\App\Models\Preventivo $preventivo) {
            return view('preventivi.edit', compact('preventivo'));
        })->name('edit');
        Route::get('/{preventivo}/pdf', PreventivoPdfController::class)->name('pdf');
        Route::get('/{preventivo}', function ($preventivo) {
            return view('preventivi.show', compact('preventivo'));
        })->name('show');
    });

    // Clienti
    Route::middleware(['role:admin'])->prefix('clienti')->name('clienti.')->group(function () {
        Route::get('/', function () {
            return view('clienti.index');
        })->name('index');
        Route::get('/create', function () {
            return view('clienti.create');
        })->name('create');
        Route::get('/{cliente}', function ($cliente) {
            return view('clienti.show', compact('cliente'));
        })->name('show');
    });

    // Fornitori
    Route::middleware(['role:admin'])->prefix('fornitori')->name('fornitori.')->group(function () {
        Route::get('/', function () {
            return view('fornitori.index');
        })->name('index');
        Route::get('/create', function () {
            return view('fornitori.create');
        })->name('create');
        Route::get('/{fornitore}', function ($fornitore) {
            return view('fornitori.show', compact('fornitore'));
        })->name('edit');
    });

    // Prodotti (Materiali)
    Route::middleware(['role:admin'])->prefix('prodotti')->name('prodotti.')->group(function () {
        Route::get('/', function () {
            return view('prodotti.index');
        })->name('index');
        Route::get('/create', function () {
            return view('prodotti.create');
        })->name('create');
        Route::get('/{prodotto}', function ($prodotto) {
            return view('prodotti.show', compact('prodotto'));
        })->name('show');
    });

    // Costruzioni
    Route::middleware(['role:admin'])->prefix('costruzioni')->name('costruzioni.')->group(function () {
        Route::get('/', function () {
            return view('costruzioni.index');
        })->name('index');
        Route::get('/create', function () {
            return view('costruzioni.create');
        })->name('create');
        Route::get('/{costruzione}/edit', function (\App\Models\Costruzione $costruzione) {
            return view('costruzioni.edit', compact('costruzione'));
        })->name('edit');
        Route::get('/{costruzione}', function (\App\Models\Costruzione $costruzione) {
            return view('costruzioni.show', compact('costruzione'));
        })->name('show');
    });

    // Ordini
    Route::middleware(['role:admin'])->prefix('ordini')->name('ordini.')->group(function () {
        Route::get('/', function () {
            return view('ordini.index');
        })->name('index');
        Route::get('/create', function () {
            return view('ordini.create');
        })->name('create');
        Route::get('/{ordine}/edit', function (\App\Models\Ordine $ordine) {
            return view('ordini.edit', compact('ordine'));
        })->name('edit');
        Route::get('/{ordine}', function (\App\Models\Ordine $ordine) {
            return view('ordini.show', compact('ordine'));
        })->name('show');
    });

    // BOM (Distinte Base)
    Route::prefix('bom')->name('bom.')->group(function () {
        Route::get('/', fn () => view('bom.index'))->name('index');
        Route::middleware(['role:admin'])->group(function () {
            Route::get('/create', fn () => view('bom.create'))->name('create');
            Route::get('/{bom}/edit', fn (App\Models\Bom $bom) => view('bom.edit', compact('bom')))->name('edit');
        });
        Route::get('/{bom}', fn (App\Models\Bom $bom) => view('bom.show', compact('bom')))->name('show');
    });

    // Settings Produzione (solo admin)
    Route::middleware(['role:admin'])->prefix('settings')->name('settings.')->group(function () {
        Route::get('/production', fn () => view('settings.production'))->name('production');
    });

    // Istruzioni
    Route::middleware(['role:admin'])->get('/istruzioni', fn () => view('istruzioni.index'))->name('istruzioni');
});
