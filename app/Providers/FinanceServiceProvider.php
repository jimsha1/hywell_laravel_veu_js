<?php

namespace App\Providers;

use App\Repositories\Implementation\FinanceManagement\EditCustomerOpeningBalanceRepository;
use App\Repositories\interfaces\FinanceManagement\EditCustomerOpeningBalanceRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class FinanceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            EditCustomerOpeningBalanceRepositoryInterface::class,
            EditCustomerOpeningBalanceRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
