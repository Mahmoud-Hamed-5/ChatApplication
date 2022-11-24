@extends('layout')

@section('content')

<div class="card">
	<div class="card-header">Chat Box</div>
	<div class="card-body">

		You are Logged in Chat Application.

	</div>
</div>

@endsection('content')


<script>

var connection = new WebSocket('ws://127.0.0.1:8090/?token={{ auth()->user()->token }}');

connection.onopen = function(e) {
    console.log("Connection established");
};

connection.onmessage = function(e){

};

</script>
