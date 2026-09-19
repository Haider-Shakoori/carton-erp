<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Transaction;
use App\Observers\TransactionObserver;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Gate;
use App\Models\Setting;
use App\Models\Attendance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped('view.setting', function () {
            return Setting::first() ?? new Setting([
                'company_name' => config('app.name', 'ERP'),
                'default_language' => config('app.locale', 'en'),
                'currency' => 'USD',
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user) {
            return $user->hasRole('admin') ? true : null;
        });

        \App\Models\Transaction::observe(\App\Observers\DailyBalanceObserver::class);
        Blade::directive('dirMargin', function () {
            return "<?php echo \$isRtl ? 'ms-2' : 'me-2'; ?>";
        });

        View::composer('*', function ($view) {
            $rtlLanguages = ['fa', 'ps'];
            $locale = app()->getLocale();
            $view->with('isRtl', in_array($locale, $rtlLanguages));
            $view->with('setting', app('view.setting'));
        });

        View::composer('admin.hr.attendance.show', function ($view) {
            $attendance = $view->getData()['attendance'];
            $recent = Attendance::where('employee_id', $attendance->employee_id)
                ->where('date', '!=', $attendance->date)
                ->orderBy('date', 'desc')
                ->limit(5)
                ->get();

            $view->with('recent', $recent);
        });

        Transaction::observe(TransactionObserver::class);
    }
}
