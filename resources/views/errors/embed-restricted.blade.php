@extends('layouts.plain')

@section('content')
    <div class="container very-small" style="padding: 1rem;">
        <div class="card content-wrap auto-height">
            <h1 class="list-heading">{{ trans('errors.embed_restricted') }}</h1>
            <p class="text-muted">{{ trans('errors.embed_restricted_desc') }}</p>

            @if($versionUrl)
                {{-- Within the iframe: the reader just wandered out of their manual. --}}
                <a href="{{ $versionUrl }}" class="button outline">{{ trans('errors.embed_restricted_return') }}</a>
            @else
                {{-- A top-level navigation, so somebody reached the site outside the backoffice.
                     Without a way out they are walled in until the session expires: /login bounces
                     authenticated users back to "/", which is this same 403. --}}
                <form action="{{ url('/logout') }}" method="POST">
                    {!! csrf_field() !!}
                    <button type="submit" class="button outline">{{ trans('errors.embed_restricted_logout') }}</button>
                </form>
            @endif
        </div>
    </div>
@stop
