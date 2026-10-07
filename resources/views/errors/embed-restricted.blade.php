@extends('layouts.plain')

@section('content')
    <div class="container very-small" style="padding: 1rem;">
        <div class="card content-wrap auto-height">
            <h1 class="list-heading">{{ trans('errors.embed_restricted') }}</h1>
            <p class="text-muted">{{ trans('errors.embed_restricted_desc') }}</p>
            @if($versionUrl)
                <a href="{{ $versionUrl }}" class="button outline">{{ trans('errors.embed_restricted_return') }}</a>
            @endif
        </div>
    </div>
@stop
