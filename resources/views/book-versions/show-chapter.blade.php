@extends($embedMode ? 'layouts.plain' : 'layouts.simple')

@section($embedMode ? 'content' : 'body')
    <div class="container" @if($embedMode) style="padding: 1rem;" @endif>

        @if($embedMode)
            {{-- Back button --}}
            <div class="mb-s">
                <a href="{{ url($urlBase . $embedQuery) }}" class="text-muted icon-list-item outline-hover">
                    <span>@icon('back')</span>
                    <span>{{ trans('common.back') }}</span>
                </a>
            </div>

            {{-- Embed navigation --}}
            <nav class="breadcrumbs text-center mb-m" aria-label="Navegación">
                <a href="{{ url($urlBase . $embedQuery) }}" class="icon-list-item outline-hover text-book">
                    <span>@icon('book')</span>
                    <span>{{ $version->book_name }} <small class="text-muted">(v{{ $version->version_label }})</small></span>
                </a>
                <div class="separator">@icon('chevron-right')</div>
                <span class="icon-list-item text-chapter">
                    <span>@icon('chapter')</span>
                    <span>{{ $chapter->name }}</span>
                </span>
            </nav>
        @else
            <div class="my-s">
                @include('entities.breadcrumbs', ['crumbs' => [
                    $book,
                    $book->getUrl('/versions') => [
                        'text' => trans('entities.book_versions'),
                        'icon' => 'history',
                    ],
                    $version->getUrl() => [
                        'text' => 'v' . $version->version_label,
                        'icon' => 'history',
                    ],
                    $chapter->getUrl($version) => [
                        'text' => $chapter->name,
                        'icon' => 'chapter',
                    ]
                ]])
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

        <main class="content-wrap card">
            <h1 class="break-text">{{ $chapter->name }}</h1>

            @if($chapter->description_html)
                <div class="text-muted break-text mb-m">{!! $chapter->description_html !!}</div>
            @elseif($chapter->description)
                <div class="text-muted break-text mb-m">{{ $chapter->description }}</div>
            @endif

            @if($pages->count() > 0)
                <div class="entity-list">
                    @foreach($pages as $page)
                        <a href="{{ url($urlBase . '/page/' . urlencode($page->slug) . $embedQuery) }}" class="entity-list-item">
                            <span class="icon text-page">@icon('page')</span>
                            <div class="content">
                                <h4 class="entity-list-item-name text-page break-text">{{ $page->name }}</h4>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-muted italic">{{ trans('entities.chapters_empty') }}</p>
            @endif
        </main>

        @include('book-versions.sibling-navigation', ['previous' => $previous, 'next' => $next, 'urlBase' => $urlBase, 'embedQuery' => $embedQuery])

    </div>
@stop
