@extends($embedMode ? 'layouts.plain' : 'layouts.simple')

@section($embedMode ? 'content' : 'body')
    <div class="container" @if($embedMode) style="padding: 1rem;" @endif>

        @if($embedMode)
            {{-- Back button --}}
            <div class="mb-s">
                @if($chapter)
                    <a href="{{ url($urlBase . '/chapter/' . urlencode($chapter->slug) . $embedQuery) }}" class="text-muted icon-list-item outline-hover">
                        <span>@icon('back')</span>
                        <span>{{ trans('common.back') }}</span>
                    </a>
                @else
                    <a href="{{ url($urlBase . $embedQuery) }}" class="text-muted icon-list-item outline-hover">
                        <span>@icon('back')</span>
                        <span>{{ trans('common.back') }}</span>
                    </a>
                @endif
            </div>

            {{-- Embed navigation --}}
            <nav class="breadcrumbs text-center mb-m" aria-label="Navegación">
                <a href="{{ url($urlBase . $embedQuery) }}" class="icon-list-item outline-hover text-book">
                    <span>@icon('book')</span>
                    <span>{{ $version->book_name }} <small class="text-muted">(v{{ $version->version_label }})</small></span>
                </a>
                @if($chapter)
                    <div class="separator">@icon('chevron-right')</div>
                    <a href="{{ url($urlBase . '/chapter/' . urlencode($chapter->slug) . $embedQuery) }}" class="icon-list-item outline-hover text-chapter">
                        <span>@icon('chapter')</span>
                        <span>{{ $chapter->name }}</span>
                    </a>
                @endif
                <div class="separator">@icon('chevron-right')</div>
                <span class="icon-list-item text-page">
                    <span>@icon('page')</span>
                    <span>{{ $page->name }}</span>
                </span>
            </nav>
        @else
            <div class="my-s">
                @include('entities.breadcrumbs', ['crumbs' => array_filter([
                    $book,
                    $book->getUrl('/versions') => [
                        'text' => trans('entities.book_versions'),
                        'icon' => 'history',
                    ],
                    $version->getUrl() => [
                        'text' => 'v' . $version->version_label,
                        'icon' => 'history',
                    ],
                    $chapter ? $chapter->getUrl($version) : 'skip' => $chapter ? [
                        'text' => $chapter->name,
                        'icon' => 'chapter',
                    ] : null,
                    $page->getUrl($version) => [
                        'text' => $page->name,
                        'icon' => 'page',
                    ]
                ])])
            </div>

            {{-- Read-only banner --}}
            <div class="notification mb-m">
                <div class="flex-container-row items-center gap-xs">
                    @icon('lock')
                    <span>
                        {!! trans('entities.book_version_readonly_banner', [
                            'label' => e($version->version_label),
                            'date' => $version->created_at->isoFormat('D MMM YYYY, HH:mm'),
                        ]) !!}
                    </span>
                    <a href="{{ $version->getUrl() }}" class="ml-auto button outline small">
                        {{ trans('entities.book_version_back_to_book') }}
                    </a>
                </div>
            </div>
        @endif

        <main class="content-wrap card fill-width">
            <h1 class="break-text">{{ $page->name }}</h1>

            <div class="page-content" style="overflow-x: auto;">
                {!! $pageHtml !!}
            </div>
        </main>

        @include('book-versions.sibling-navigation', ['previous' => $previous, 'next' => $next, 'urlBase' => $urlBase, 'embedQuery' => $embedQuery])

    </div>
@stop
