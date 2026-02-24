<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\PacienteDocumento;
use App\Policies\DocumentoMedicoPolicy;

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
        // Registrar Policy para documentos médicos
        Gate::policy(PacienteDocumento::class, DocumentoMedicoPolicy::class);
    }
}
