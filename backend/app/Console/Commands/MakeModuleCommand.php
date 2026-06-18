<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module {name : The name of the module}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new DDD Module structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $modulePath = app_path("Modules/{$name}");

        if (File::exists($modulePath)) {
            $this->error("El módulo {$name} ya existe.");

            return;
        }

        // Crear carpetas
        $folders = [
            'Controllers',
            'Services',
            'Repositories',
            'DTOs',
            'Mappers',
            'Entities',
            'Routes',
            'Providers',
        ];

        foreach ($folders as $folder) {
            File::makeDirectory("{$modulePath}/{$folder}", 0755, true);
        }

        // Crear ServiceProvider base
        $providerStub = $this->getProviderStub($name);
        File::put("{$modulePath}/Providers/{$name}ServiceProvider.php", $providerStub);

        // Crear archivo de rutas api.php base
        $routesStub = $this->getRoutesStub();
        File::put("{$modulePath}/Routes/api.php", $routesStub);

        $this->info("Módulo {$name} creado exitosamente.");
        $this->warn("RECUERDA: Añade App\\Modules\\{$name}\\Providers\\{$name}ServiceProvider::class a tu archivo bootstrap/providers.php");
    }

    protected function getProviderStub(string $name): string
    {
        return <<<PHP
<?php

namespace App\Modules\\{$name}\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class {$name}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrar bindings de repositorios y servicios
    }

    public function boot(): void
    {
        \$this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../Routes/api.php');
    }
}
PHP;
    }

    protected function getRoutesStub(): string
    {
        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;

// Rutas para el módulo
// Route::get('/', function () { return 'OK'; });

PHP;
    }
}
