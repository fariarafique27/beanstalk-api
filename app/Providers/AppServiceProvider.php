<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\AttendanceProviderInterface;
use App\Services\Attendance\ZKTecoDeviceAttendanceProvider;
use App\Services\Attendance\MockAttendanceProvider;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AttendanceProviderInterface::class, function ($app) {
        $driver = config('services.attendance.driver', 'mock');

        return $driver === 'zkteco' 
            ? $app->make(ZKTecoDeviceAttendanceProvider::class) 
            : new MockAttendanceProvider();
    });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
