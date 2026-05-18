@extends('layouts.simple')

@section('body')
    <div class="container small">

        <div class="my-s">
            @include('entities.breadcrumbs', ['crumbs' => [
                $book,
                $book->getUrl('/versions') => [
                    'text' => trans('entities.book_versions'),
                    'icon' => 'history',
                ],
                $book->getUrl('/versions/create') => [
                    'text' => trans('entities.book_version_create'),
                    'icon' => 'add',
                ]
            ]])
        </div>

        <main class="card content-wrap">
            <h1 class="list-heading">{{ trans('entities.book_version_create') }}</h1>

            <p class="text-muted mb-m">{{ trans('entities.book_version_create_desc', ['name' => $book->name]) }}</p>

            <form action="{{ $book->getUrl('/versions') }}" method="POST">
                {{ csrf_field() }}

                <div class="form-group">
                    <label for="version_label">{{ trans('entities.book_version_label_field') }}</label>
                    <input type="text"
                           id="version_label"
                           name="version_label"
                           value="{{ old('version_label') }}"
                           placeholder="{{ trans('entities.book_version_label_placeholder') }}"
                           class="input-base"
                           required
                           maxlength="100"
                           autofocus>
                    @if($errors->has('version_label'))
                        <div class="text-neg text-small mt-xs">{{ $errors->first('version_label') }}</div>
                    @endif
                </div>

                <div class="form-group text-muted mt-m">
                    <p><strong>{{ trans('entities.book_version_summary') }}:</strong></p>
                    <ul class="mt-xs">
                        <li>{{ trans('entities.book_version_book_label') }}: <strong>{{ $book->name }}</strong></li>
                        <li>{{ trans('entities.book_version_chapters_count') }}: <strong>{{ $book->chapters()->count() }}</strong></li>
                        <li>{{ trans('entities.book_version_pages_count') }}: <strong>{{ $book->pages()->count() }}</strong></li>
                    </ul>
                </div>

                <div class="form-group text-right mt-l">
                    <a href="{{ $book->getUrl('/versions') }}" class="button outline">{{ trans('common.cancel') }}</a>
                    <button type="submit" class="button">{{ trans('entities.book_version_create_button') }}</button>
                </div>
            </form>
        </main>

    </div>
@stop
