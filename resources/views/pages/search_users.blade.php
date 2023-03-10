@extends('layouts.app')

@section('title', 'About Page')

@section('content')
    <div class="search_user">
        @foreach ($users as $user)
            @include('partials.user_card', compact('user'))
        @endforeach
    </div>
    {{$users->links("partials.pagination_sequence",['paginator' => $users])}}

@endsection
