@extends('chnettrak::layouts.frontend')

@section('title', 'CHNetTRAK')

@section('content')
  <h1>VATSIM ID Entry</h1>
  <p>Enter your VATSIM ID below to get started.</p>
  <form action="{{ route('chnettrak.frontend.profile.vatsim') }}" method="post">
    @csrf
    <div class="form-group">
      <label for="vatsim_id">VATSIM ID</label>
      <input type="text" class="form-control" id="vatsim_id" name="vatsim_id" required>
    </div>
    <button type="submit" class="btn btn-primary">Submit</button>
  </form>
@endsection
