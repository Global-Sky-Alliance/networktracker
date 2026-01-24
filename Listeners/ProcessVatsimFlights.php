<?php

namespace Modules\CHNetTRAK\Listeners;

use App\Contracts\Listener;
use App\Models\Enums\PirepState;
use App\Models\Enums\PirepStatus;
use App\Models\Pirep;
use App\Models\User;
use App\Services\GeoService;
use App\Services\PirepService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Geotools;

/**
 * Class ProcessVatsimFlights
 * @package Modules\CHNetTRAK\Listeners
 */
class ProcessVatsimFlights extends Listener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(public GeoService $geoService, public PirepService $pirepService)
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        // first, start with grabbing the users with a VATSIM ID
        $pireps = Pirep::where('state', PirepState::IN_PROGRESS)->whereHas('user', function ($query) {
            $query->whereNotNull('vatsim_id');
        })->with('user')->get();
        Log::info('Checking '.count($pireps).' PIREPs for VATSIM telemetry');
        $vatsim_pilot_data = collect(json_decode(Http::get('https://data.vatsim.net/v3/vatsim-data.json')->body(), true)['pilots']);

        foreach ($pireps as $pirep) {
            // search the vatsim data for the PIREP

            $vatsim_telemetry = $vatsim_pilot_data->where('cid', $pirep->user->vatsim_id)->first();

            if ($vatsim_telemetry === null) {
                // check if the pirep has acars records with the right source.
                $telemetry = $pirep->acars()->where('source', 'NTV')->latest()->get();
                // if there's telemetry from any other source, continue
                if ($pirep->acars()->where('source', '!=', 'NTV')->count() > 0) {
                    continue;
                }

                if ($telemetry->count() > 0) {
                    // if it does, and the pirep status was within 5nm of the destination, mark it as arrived.
                    $last_acars = $pirep->position;
                    // check the actual ordering by date

                    //dd($last_acars);
                    $geotools = new Geotools();
                    $start = new Coordinate([$pirep->arr_airport->lat, $pirep->arr_airport->lon]);
                    $end = new Coordinate([$last_acars->lat, $last_acars->lon]);

                    $distance = $geotools->distance()->setFrom($start)->setTo($end);
                    //dd([$last_acars->id, $distance->greatCircle()]);
                    if ($distance->greatCircle() < 62000) {
                        Log::info('PIREP '.$pirep->id.' has arrived. Closing Pirep.');
                        $pirep->status = PirepStatus::ARRIVED;
                        $pirep->state = PirepState::PENDING;
                        $pirep->flight_time = $last_acars->created_at->diffInMinutes($pirep->created_at);
                        $pirep->save();
                        // submit the pirep
                        $this->pirepService->submit($pirep);
                        continue;
                    }
                    continue;
                }
                continue;
            }

            // Write to the pirep the new telemetry information
            $pirep->status = PirepStatus::ENROUTE;
            $pirep->save();
            $pirep->acars()->create([
                'source'   => "NTV",
                'status'   => PirepStatus::ENROUTE,
                'lat'      => $vatsim_telemetry['latitude'],
                'lon'      => $vatsim_telemetry['longitude'],
                'gs'       => $vatsim_telemetry['groundspeed'],
                'heading'  => $vatsim_telemetry['heading'],
                'altitude' => $vatsim_telemetry['altitude'],
            ]);

        }
    }
    public function resolvePirepStatus(Pirep $pirep, $telemetry) : string
    {
        // check if the pilot is stopped within 1nm of the departure airport
        if ($pirep->status === PirepStatus::INITIATED ||
            $pirep->status === PirepStatus::BOARDING) {
            // use geotools to get teh distance from the airport reference point
            $geotools = new Geotools();
            $start = new Coordinate([$pirep->dpt_airport->lat, $pirep->dpt_airport->lon]);
            $end = new Coordinate([$telemetry['latitude'], $telemetry['longitude']]);

            $distance = $geotools->distance()->setFrom($start)->setTo($end);

            if ($distance->greatCircle() < 3000 && $telemetry['gs'] < 5) {
                return PirepStatus::BOARDING;
            } else {
                return PirepStatus::TAXI;
            }
        }
        if ($pirep->status === PirepStatus::TAXI) {
            // if the pilot exceeds 40 ground speed, they have taken off
            if ($telemetry['gs'] > 40) {
                return PirepStatus::TAKEOFF;
            }
        }
        if ($pirep->status === PirepStatus::TAKEOFF) {
            // if the pilot is 200ft above the field elevation and above 40 kts, they're enroute.
            if ($telemetry['gs'] > 40 && $telemetry['altitude'] > $pirep->dpt_airport->elevation + 200) {
                return PirepStatus::ENROUTE;
            }
        }

        // if the pilot connected in the air, they're enroute by default.
        if ($pirep->status === PirepStatus::INITIATED) {
            return PirepStatus::ENROUTE;
        }
        // if the pilot is within 50 nautical miles of the airport, and below 10000ft , they're on approach.
        if ($pirep->status === PirepStatus::ENROUTE) {
            $geotools = new Geotools();
            $start = new Coordinate([$pirep->arr_airport->lat, $pirep->arr_airport->lon]);
            $end = new Coordinate([$telemetry['latitude'], $telemetry['longitude']]);

            $distance = $geotools->distance()->setFrom($start)->setTo($end);

            if ($distance->greatCircle() < 62000 && $telemetry['altitude'] < $pirep->dpt_airport->elevation + 10000) {
                return PirepStatus::APPROACH_ICAO;
            }
        }

        // If we can't determine what's going on, just return the previous status.
        return $pirep->status;
    }
}
