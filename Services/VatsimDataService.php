<?php

namespace Modules\CHNetTRAK\Services;

use App\Models\Aircraft;
use App\Models\Enums\PirepSource;
use App\Models\Enums\PirepState;
use App\Models\Flight;
use App\Models\Pirep;
use App\Models\User;
use App\Services\PirepService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class VatsimDataService
{
    public string $url = 'http://data.vatsim.net/v3/vatsim-data.json';
    public function __construct(public PirepService $pirepService)
    {
        //
    }
    public function getVatsimData(): \Illuminate\Support\Collection
    {
        // Get VATSIM data
        return collect(json_decode(Http::get('https://data.vatsim.net/v3/vatsim-data.json')->body(), true)['pilots']);
    }
    public function checkFlightActive(User $user, Flight $flight): bool
    {
        // if user has no VATSIM ID, return false
        if (is_null($user->vatsim_id)) {
            return false;
        }
        // Check if flight is active
        $data = $this->getVatsimData();
        $v_flight = $data->firstWhere('cid', '=', $user->vatsim_id);

        if (!is_null($v_flight)) {
            if ($flight->airline->icao.$flight->flight_number == $v_flight['callsign']) {
                return true;
            }
        }
        return false;
    }
    public function startFlightTracking(User $user, Flight $flight, Aircraft $aircraft, $fares = []): void
    {
        // prefile the pirep
        $attrs = [
            'flight_number'    => $flight->flight_number,
            'airline_id'       => $flight->airline_id,
            'route_code'       => $flight->route_code,
            'route_leg'        => $flight->route_leg,
            'flight_type'      => $flight->flight_type,
            'dpt_airport_id'   => $flight->dpt_airport_id,
            'arr_airport_id'   => $flight->arr_airport_id,
            'planned_distance' => $flight->distance,
            'aircraft_id'      => $aircraft->id,
            'flight_id'        => $flight->id,
            'source'           => PirepSource::ACARS,
            'source_name'      => "CHNetTRAK: VATSIM"
        ];
        $this->pirepService->prefile(user: $user, attrs: $attrs, fares: $fares);
    }
}
