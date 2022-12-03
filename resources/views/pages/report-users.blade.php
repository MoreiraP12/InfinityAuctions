@extends('layouts.app')

@section('content')

    @php 
        $to_use = array();
        $pageTitle = 'User Report';
        $to_use['route'] = route('report');
        $to_use['reported_user'] = $user->id;
    @endphp

    @if (!Auth::user())
        <p class="not-auth">Please <a href="{{ route('login') }}">login</a> to proceed.</p>
    @else
        <form method="POST" class="sell" action="{{$to_use['route']}}" enctype="multipart/form-data">
            {{ csrf_field() }}

            <input id="title" placeholder="Name your auction" type="text" name="reported_user" value="{{ $to_use['reported_user'] }}">
            <div class="option">
                <legend>Options</legend>
                <br/>
                @foreach ($options as $option)
                    @php $id = str_replace(' ', '', $option->name);
                    @endphp
                    <input type="checkbox" id="{{$id}}" name="{{$id}}">
                    <label for="{{$id}}">{{$option->name}}</label><br>
                @endforeach
            </div>

            <button type="submit">SUBMIT</button>
        </form>
    @endif
@endsection
