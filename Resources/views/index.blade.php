@extends('chnettrak::layouts.frontend')

@section('title', 'CHNetTRAK')

@section('content')
  <h1>CHNetTRAK Bids</h1>
  <table class="table table-striped">
    <thead>
    <tr>
      <th scope="col">Flight</th>
      <th scope="col">Aircraft</th>
      <th scope="col">Action</th>
    </tr>
    </thead>
    <tbody>
    @foreach($bids as $bid)
      <tr>
        <td>{{ $bid->flight->ident }}</td>
        <!-- if the aircraft is null, display a dropdown to select an aircraft -->
        <td>{{ optional($bid->aircraft)->registration }}</td>
        <td>
          <form action="{{ route('chnettrak.frontend.bids.start', [$bid]) }}" method="POST">
            @csrf
            @if($bid->aircraft_id === null)
              <select name="aircraft_id" class="form-control select2">
                @foreach($aircraft as $ac)
                  <option value="{{ $ac->id }}">[{{ $ac->icao }}] {{ $ac->registration }} @if($ac->registration != $ac->name)'{{ $ac->name }}'@endif</option>
                @endforeach
              </select>
            @else
              <input type="hidden" name="aircraft_id" value="{{ $bid->aircraft_id }}">
            @endif
            <button type="submit" class="btn btn-primary">Start Bid</button>
          </form>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
  <p>
    This view is loaded from module: {{ config('chnettrak.name') }}
  </p>
@endsection
