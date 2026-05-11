@extends('layouts.export')

@section('title', $chapter->name)

@section('content')

    <h1 style="font-size: 2.2em">{{$chapter->name}}</h1>
    <div>{!! $chapter->descriptionInfo()->getHtml() !!}</div>

    @include('exports.parts.chapter-contents-menu', ['pages' => $pages])

    @foreach($pages as $page)
        @include('exports.parts.page-item', ['page' => $page, 'chapter' => null])
    @endforeach

@endsection