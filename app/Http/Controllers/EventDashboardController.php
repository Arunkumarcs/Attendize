<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventStats;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;

class EventDashboardController extends MyBaseController
{
    /**
     * Show the event dashboard
     *
     * @param bool|false $event_id
     * @return \Illuminate\View\View
     */
    public function showDashboard($event_id = false)
    {
        $event = Event::scope()->findOrFail($event_id);

        $num_days = 20;

        /*
         * This is a fairly hackish way to get the data for the dashboard charts. I'm sure someone
         * with better SQL skill could do it in one simple query.
         *
         * Filling in the missing days here seems to be fast(ish) (with 20 days history), but the work
         * should be done in the DB
         */
        $chartData = EventStats::where('event_id', '=', $event->id)
                ->where('date', '>', Carbon::now()->subDays($num_days)->format('Y-m-d'))
                ->get()
                ->toArray();

        $startDate = new DateTime("-$num_days days");
        $dateItter = new DatePeriod(
                $startDate, new DateInterval('P1D'), $num_days
        );

        $original = $chartData;

        /*
         * I have no idea what I was doing here, but it seems to work;
         */
        $result = [];
        $i = 0;
        foreach ($dateItter as $date) {
            $views = 0;
            $sales_volume = 0;
            $unique_views = 0;
            $tickets_sold = 0;
            $organiser_fees_volume = 0;

            foreach ($original as $item) {
                if ($item['date'] == $date->format('Y-m-d')) {
                    $views = $item['views'];
                    $sales_volume = $item['sales_volume'];
                    $organiser_fees_volume = $item['organiser_fees_volume'];
                    $unique_views = $item['unique_views'];
                    $tickets_sold = $item['tickets_sold'];
                }
                $i++;
            }

            $result[] = [
                'date'         => $date->format('Y-m-d'),
                'views'        => $views,
                'unique_views' => $unique_views,
                'sales_volume' => $sales_volume + $organiser_fees_volume,
                'tickets_sold' => $tickets_sold,
            ];
        }

        $data = [
            'event'     => $event,
            'chartData' => json_encode($result),
        ];

        return view('ManageEvent.Dashboard', $data);
    }

}
