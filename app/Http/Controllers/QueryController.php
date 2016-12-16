<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

date_default_timezone_set('Europe/Brussels');

class QueryController extends Controller
{
    /**
     * Handle an openinghours query
     *
     * @param  Request  $request
     * @return Response
     */
    public function query(Request $request)
    {
        $type = $request->input('q');

        switch ($type) {
            case 'week':
                $data = $this->renderWeek($request);
                break;
            case 'now':
                break;
            default:
                abort(400, 'The endpoint did not find a handler for your query.');
                break;
        }

        return response()->json($data);
    }

    /**
     * Compute a week schedule for a service
     *
     * @param  Request $request
     * @return array
     */
    private function renderWeek($request)
    {
        $services = app()->make('ServicesRepository');

        // Get the service URI for which we need to compute the week schedule
        $serviceUri = $request->input('serviceUri');
        $channel = $request->input('channel');

        // Get the service
        $service = $services->where('uri', $serviceUri)->first();

        if (empty($service)) {
            return response()->json(['message' => 'The service was not found.'], 404);
        }

        $channels = [];

        // If no channel is passed, return all channels
        if (! empty($channel)) {
            $channels[] = $channel;
        } else {
            $channelObjects = $service->channels->toArray();

            foreach ($channelObjects as $object) {
                $channels[] = $object['label'];
            }
        }

        if (empty($channels)) {
            abort(404, 'Deze dienst heeft geen enkel kanaal met openingsuren.');
        }

        $openinghours = [];

        foreach ($channels as $channel) {
            $weekSchedule = $this->renderWeekForChannel($serviceUri, $channel);

            $openinghours[$channel] = $weekSchedule;
        }

        return $openinghours;
    }

    /**
     * Return the week schedule for a service and channel
     *
     * @param  string $serviceUri
     * @param  string $channel
     * @return array
     */
    private function renderWeekForChannel($serviceUri, $channel)
    {
        // Check if the service and channel exist
        $openinghoursRepo = app()->make('OpeninghoursRepository');
        $openinghours = $openinghoursRepo->getAllForServiceAndChannel($serviceUri, $channel);

        if (empty($openinghours)) {
            abort(404, 'Het gevraagde kanaal heeft geen openingsuren binnen de gevraagde dienst.');
        }

        $weekDays = ['Zondag', 'Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag'];

        // Get the openinghours that is active now
        $relevantOpeninghours = '';

        foreach ($openinghours as $openinghoursInstance) {
            if (Carbon::now()->between(
                (new Carbon($openinghoursInstance->start_date)),
                (new Carbon($openinghoursInstance->end_date))
            )) {
                $relevantOpeninghours = $openinghoursInstance;
                break;
            }
        }

        if (empty($relevantOpeninghours)) {
            abort(404, 'No relevant openinghours found for this week.');
        }

        // Go to the start of the week starting from today and iterate over every day
        // then check if there are events for that given day in the calendar, by priority
        $weekDay = Carbon::now();

        $week = [];

        for ($day = 0; $day <= 6; $day++) {
            $calendars = array_sort($relevantOpeninghours->calendars, function ($calendar) {
                return $calendar->priority;
            });

            $dayInfo = 'Gesloten';

            // Iterate all calendars for the day of the week
            foreach ($calendars as $calendar) {
                $ical = $this->createIcalFromCalendar($calendar);

                $extractedDayInfo = $this->extractDayInfo($ical, $weekDay->toDateString(), $weekDay->toDateString());

                if (! empty($extractedDayInfo)) {
                    $dayInfo = $extractedDayInfo;

                    break;
                }
            }

            $week[$weekDay->dayOfWeek] = $dayInfo;

            $weekDay->addDay();
        }

        $schedule = [];

        foreach ($week as $dayIndex => $daySchedule) {
            $schedule[] = $weekDays[$dayIndex] . ': ' . $daySchedule;
        }

        return $schedule;
    }

    /**
     * Check if there are events in a given range (day)
     *
     * @param  ICal   $ical
     * @param  string $start date string YYYY-mm-dd
     * @param  string $end   date string YYYY-mm-dd
     * @return array
     */
    private function extractDayInfo($ical, $start, $end)
    {
        $events = $ical->eventsFromRange($start, $end);

        if (empty($events)) {
            return '';
        }

        $hours = [];

        foreach ($events as $event) {
            $dtStart = Carbon::createFromTimestamp($ical->iCalDateToUnixTimestamp($event->dtstart));
            $dtEnd = Carbon::createFromTimestamp($ical->iCalDateToUnixTimestamp($event->dtend));

            $hours[] = $dtStart->format('h:i') . ' - ' . $dtEnd->format('h:i');
        }

        return rtrim(implode($hours, ', '), ',');
    }

    /**
     * Create ICal from a calendar object
     *
     * @param  Calendar $calendar
     * @return ICal
     */
    private function createIcalFromCalendar($calendar)
    {
        $icalString = "BEGIN:VCALENDAR\nVERSION:2.0\nCALSCALE:GREGORIAN\n";

        foreach ($calendar->events as $event) {
            $dtStart = $this->convertIsoToIcal($event->start_date);
            $dtEnd = $this->convertIsoToIcal($event->end_date);

            $icalString .= "BEGIN:VEVENT\n";
            $icalString .= 'DTSTART;TZID=Europe/Brussels:' . $dtStart . "\n";
            $icalString .= 'DTEND;TZID=Europe/Brussels:' . $dtEnd . "\n";
            $icalString .= 'RRULE:' . $event->rrule . "\n";
            $icalString .= 'UID:' . str_random(32) . "\n";
            $icalString .= "END:VEVENT\n";
        }

        $icalString .= 'END:VCALENDAR';

        return new \ICal\ICal(explode(PHP_EOL, $icalString), 'MO');
    }

    /**
     * Format an ISO date to YYYYmmddThhmmss
     *
     * @param string $date
     * @return
     */
    private function convertIsoToIcal($date)
    {
        $date = new Carbon($date);
        $date = $date->format('Ymd his');

        return str_replace(' ', 'T', $date);
    }
}