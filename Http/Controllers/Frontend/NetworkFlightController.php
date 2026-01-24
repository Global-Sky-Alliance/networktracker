<?php

namespace Modules\CHNetTRAK\Http\Controllers\Frontend;

use App\Contracts\Controller;
use App\Models\Aircraft;
use App\Models\Bid;
use App\Models\Flight;
use App\Repositories\AircraftRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laracasts\Flash\Flash;
use Modules\CHNetTRAK\Services\VatsimDataService;

/**
 * Class $CLASS$
 * @package
 */
class NetworkFlightController extends Controller
{
    public function __construct(public VatsimDataService $vatsimDataService, public AircraftRepository $aircraftRepository)
    {
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        // check if the logged in user has a vatsim_id. If not, redirect them.
        if (!auth()->user()->vatsim_id) {
            Flash::error('You need to have a VATSIM ID to access this page');
            return view('chnettrak::vatsimentry');
        }
        // Retrieve the user bids
        $bids = Bid::where('user_id', Auth::id())->with('flight', 'aircraft')->get();
        // retrieve all the aircraft the user can fly
        $aircraft = Aircraft::all();
        return view('chnettrak::index', ['bids' => $bids, 'aircraft' => $aircraft]);
    }

    public function storeVatsimId(Request $request)
    {
        $request->validate([
            'vatsim_id' => 'required|numeric'
        ]);
        $user = Auth::user();
        $user->vatsim_id = $request->vatsim_id;
        $user->save();
        Flash::success('VATSIM ID saved');
        return redirect(route('chnettrak.frontend.index'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function start_flight(Request $request, Flight $flight)
    {
        $this->vatsimDataService->startFlightTracking($request->user(), $flight, $request->aircraft_id);
    }

    public function startBid(Request $request, Bid $bid)
    {
        // if bid is not owned by the user, then don't allow it
        if ($bid->user_id !== Auth::id()) {
            return abort(404);
        }
        $bid->load('flight');
        // first, check if the flight is on the network
        if($this->vatsimDataService->checkFlightActive($request->user(), $bid->flight)) {
            $this->vatsimDataService->startFlightTracking($request->user(), $bid->flight, Aircraft::find($request->aircraft_id));
            Flash::success('Flight started');
            return back();
        }
        Flash::warning('Not Started. Flight not active on network');
        return back();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function store(Request $request)
    {
    }

    /**
     * Show the specified resource.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function show(Request $request)
    {
        return view('chnettrak::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function edit(Request $request)
    {
        return view('chnettrak::edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     */
    public function update(Request $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     */
    public function destroy(Request $request)
    {
    }
}
