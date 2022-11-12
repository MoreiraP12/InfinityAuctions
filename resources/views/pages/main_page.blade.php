@extends('layouts.app')

@section('title', 'Home Page')

@section('content')
    @include('partials.selected_auctions', ['selected_auctions' => $selectedAuctions])
    @include('partials.most_active', ['most_active' => $mostActive])
    @include('partials.categories', ['categories' => $categories])
    @include('partials.new', ['new' => $new])
@endsection