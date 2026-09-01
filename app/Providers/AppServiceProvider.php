<?php

namespace App\Providers;

use App\Authorization\DTOs\AuthorizationContext;
use App\Authorization\Enums\Permission;
use App\Authorization\Services\AuthorizationService;
use App\Authorization\Services\PermissionRegistry;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PermissionRegistry::class, function (): PermissionRegistry {
            $registry = new PermissionRegistry;

            foreach (Permission::cases() as $permission) {
                $registry->register($permission, [
                    'domain' => $permission->domain(),
                    'action' => $permission->action(),
                ]);
            }

            return $registry;
        });

        $this->app->singleton(AuthorizationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define(
            'policyforge.authorize',
            fn (User $user, AuthorizationContext $context): Response => app(AuthorizationService::class)
                ->inspect($context->forPrincipal($user))
                ->toLaravelResponse(),
        );

        foreach (Permission::cases() as $permission) {
            Gate::define(
                $permission->value,
                fn (User $user, AuthorizationContext $context): Response => app(AuthorizationService::class)
                    ->inspect($context->forPrincipal($user)->withPermission($permission))
                    ->toLaravelResponse(),
            );
        }
    }
}
