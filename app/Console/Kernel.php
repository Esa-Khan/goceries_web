<?php

namespace App\Console;

use App\Models\Driver;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AssignedOrder;
use DateInterval;
use DateTime;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

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
        $schedule->call(function() {
            $this->updateDriverActiveStatus();
            $this->resendNotifications();
        })->everyMinute();
    }


    private function resendNotifications(){
        $pending_orders = Order::where('order_status_id', 1)
            ->where('active', 1)
            ->get();
        $drivers = Driver::where('available', '1')
            ->join('users', 'users.id', '=', 'user_id')
            ->where('users.isDriver', 1)
            ->pluck('user_id')->toArray();
        foreach ($drivers as $currDriver_id) {
            foreach ($pending_orders as $curr_order) {
                $user = User::where('id', $currDriver_id)->get();
                Log::info("Sending notification to ".$currDriver_id." for Order #".$curr_order['id']."\n");
                echo "Sending notification to ".$currDriver_id." for Order #".$curr_order['id']."\n";
                Notification::send($user, new AssignedOrder($curr_order));
            }
        }
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
            Driver::where('user_id', $curr_driver['user_id'])->update(['available' => $isWorking]);

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
