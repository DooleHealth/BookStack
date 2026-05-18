@extends('layouts.simple')

@section('body')
    <div class="container">

        <div class="my-s">
            @include('entities.breadcrumbs', ['crumbs' => [
                $book,
                $book->getUrl('/versions') => [
                    'text' => trans('entities.book_versions'),
                    'icon' => 'history',
                ]
            ]])
        </div>

        <main class="card content-wrap">
            <div class="flex-container-row items-center justify-space-between wrap mb-m">
                <h1 class="list-heading">{{ trans('entities.book_versions') }}</h1>
                @if(userCan('settings-manage'))
                    <a href="{{ $book->getUrl('/versions/create') }}" class="button outline">
                        @icon('add')
                        {{ trans('entities.book_version_create') }}
                    </a>
                @endif
            </div>

            <p class="text-muted">{{ trans('entities.book_versions_desc') }}</p>

            @if(count($versions) > 0)
                <div class="item-list mt-m">
                    <div class="item-list-row flex-container-row items-center strong hide-under-l">
                        <div class="flex-2 px-m py-xs">{{ trans('entities.book_version_label_header') }}</div>
                        <div class="flex-3 px-m py-xs">{{ trans('entities.book_version_book_name') }}</div>
                        <div class="flex-2 px-m py-xs">{{ trans('entities.book_version_created_by') }}</div>
                        <div class="flex-2 px-m py-xs">{{ trans('entities.book_version_date') }}</div>
                        @if(userCan('settings-manage'))
                            <div class="flex fit-content px-m py-xs text-right">{{ trans('common.actions') }}</div>
                        @endif
                    </div>
                    @foreach($versions as $version)
                        <div class="item-list-row flex-container-row items-center wrap py-xs">
                            <div class="flex-2 px-m py-xs">
                                <a href="{{ $version->getUrl() }}" class="text-book bold">
                                    {{ $version->version_label }}
                                </a>
                            </div>
                            <div class="flex-3 px-m py-xs">
                                {{ $version->book_name }}
                            </div>
                            <div class="flex-2 px-m py-xs">
                                @if($version->createdBy)
                                    <a href="{{ $version->createdBy->getProfileUrl() }}">{{ $version->createdBy->name }}</a>
                                @else
                                    {{ trans('common.unknown') }}
                                @endif
                            </div>
                            <div class="flex-2 px-m py-xs text-muted">
                                <small>{{ $version->created_at->isoFormat('D MMM YYYY, HH:mm') }}</small>
                            </div>
                            @if(userCan('settings-manage'))
                                <div class="flex fit-content px-m py-xs text-right">
                                    <form action="{{ $book->getUrl('/versions/' . $version->version_slug) }}" method="POST" class="inline">
                                        {{ csrf_field() }}
                                        {{ method_field('DELETE') }}
                                        <button type="submit" class="text-neg icon-list-item" onclick="return confirm('{{ trans('entities.book_version_delete_confirm') }}')">
                                            @icon('delete')
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted italic mt-l">{{ trans('entities.book_versions_none') }}</p>
            @endif
        </main>

    </div>
@stop
