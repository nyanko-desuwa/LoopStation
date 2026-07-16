<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // Localize the API docs title + description at runtime. Config files load
        // before the translator exists, so __() must be called here, not in config.
        Scramble::extendOpenApi(function (OpenApi $openApi) {
            $openApi->info->title = __('docs.api.title');
            $openApi->info->setDescription(__('docs.api.description'));

            // Add a Bearer token scheme so the docs UI shows an "Authorize"
            // input (Swagger-style) for Sanctum-protected endpoints.
            $openApi->secure(
                SecurityScheme::http('bearer', 'JWT')
                    ->setDescription(__('docs.api.security.bearer'))
            );
        });
    }
}
