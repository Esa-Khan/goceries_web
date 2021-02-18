<?php

namespace App\Console;

use App\Models\Driver;
use DateInterval;
use DateTime;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function() {Log::emergency('The system is down!');})
            ->everyMinute();
    }

    private function updateDriverActiveStatus(){
        $drivers = Driver::get()->toArray();
        foreach ($drivers as $curr_driver) {
            $isWorking = false;
            $work_hours = $curr_driver['work_hours'];

            try {
                if ($work_hours === '24/7') {
                    $isWorking = true;
                } else {
                    $current_time = new DateTime("now");
                    $work_hours = explode('|', $work_hours);
                    $start_time = new DateTime($work_hours[0]);

                    $time = explode(':', $work_hours[1]);
                    $end_time = new DateTime($work_hours[0]);
                    $end_time->add(new DateInterval('PT' . $time[0] . 'H' . $time[1] . 'M' . $time[2] . 'S'));

                    if ($current_time >= $start_time && $current_time <= $end_time) {
                        $isWorking = true;
                    } else {
                        $start_time->sub(new DateInterval('P1D'));
                        $end_time->sub(new DateInterval('P1D'));
                        if ($current_time >= $start_time && $current_time <= $end_time) {
                            $isWorking = true;
                        } else {
                            $isWorking = false;
                        }
                    }
                }
            } catch (\Exception $e) {
                echo 'Error';
            }
            Driver::where('user_id', $curr_driver['user_id'])->update(['available' => '1']);

        }
    }
    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
