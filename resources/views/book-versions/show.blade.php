@extends($embedMode ? 'layouts.plain' : 'layouts.simple')

@section($embedMode ? 'content' : 'body')
    <div class="container" @if($embedMode) style="padding: 1rem;" @endif>

        @if($embedMode)
            {{-- Embed navigation bar --}}
            <nav class="breadcrumbs text-center mb-m" aria-label="Navegación">
                <a href="{{ url($urlBase . $embedQuery) }}" class="icon-list-item outline-hover text-book">
                    <span>@icon('book')</span>
                    <span>{{ $version->book_name }} <small class="text-muted">(v{{ $version->version_label }})</small></span>
                </a>
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
                    <a href="{{ $book->getUrl() }}" class="ml-auto button outline small">
                        {{ trans('entities.book_version_back_to_current') }}
                    </a>
                </div>
            </div>
        @endif

        <main class="content-wrap card fill-width">
            <h1 class="break-text">{{ $version->book_name }}</h1>
            <div class="book-content">
                @if($version->book_description_html)
                    <div class="text-muted break-text">{!! $version->book_description_html !!}</div>
                @elseif($version->book_description)
                    <div class="text-muted break-text">{{ $version->book_description }}</div>
                @endif

                @if($chapters->count() > 0 || $directPages->count() > 0)
                    <div class="entity-list book-contents mt-m">
                        @php
                            $allChildren = $directPages->concat($chapters)->sortBy('priority');
                        @endphp
                        @foreach($allChildren as $child)
                            @if($child instanceof \BookStack\Entities\Models\BookVersionChapter)
                                <a href="{{ url($urlBase . '/chapter/' . urlencode($child->slug) . $embedQuery) }}" class="chapter entity-list-item @if($child->pages->count() > 0) has-children @endif">
                                    <span class="icon text-chapter">@icon('chapter')</span>
                                    <div class="content">
                                        <h4 class="entity-list-item-name break-text">{{ $child->name }}</h4>
                                        @if($child->description)
                                            <div class="entity-item-snippet">
                                                <p class="text-muted break-text">{{ Str::limit($child->description, 150) }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </a>
                                @if($child->pages->count() > 0)
                                    <div class="chapter chapter-expansion">
                                        <span class="icon text-chapter">@icon('page')</span>
                                        <div component="chapter-contents" class="content">
                                            <button type="button"
                                                    refs="chapter-contents@toggle"
                                                    aria-expanded="false"
                                                    class="text-muted chapter-contents-toggle">@icon('caret-right') <span>{{ trans_choice('entities.x_pages', $child->pages->count()) }}</span></button>
                                            <div refs="chapter-contents@list" class="inset-list chapter-contents-list">
                                                <div class="entity-list-item-children">
                                                    <div class="entity-list">
                                                        @foreach($child->pages as $page)
                                                            <a href="{{ url($urlBase . '/page/' . urlencode($page->slug) . $embedQuery) }}" class="page entity-list-item">
                                                                <span role="presentation" class="icon text-page">@icon('page')</span>
                                                                <div class="content">
                                                                    <h4 class="entity-list-item-name break-text">{{ $page->name }}</h4>
                                                                    @if($page->html)
                                                                        <div class="entity-item-snippet">
                                                                            <p class="text-muted break-text">{{ Str::limit(strip_tags($page->html), 150) }}</p>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <a href="{{ url($urlBase . '/page/' . urlencode($child->slug) . $embedQuery) }}" class="page entity-list-item">
                                    <span role="presentation" class="icon text-page">@icon('page')</span>
                                    <div class="content">
                                        <h4 class="entity-list-item-name break-text">{{ $child->name }}</h4>
                                        @if($child->html)
                                            <div class="entity-item-snippet">
                                                <p class="text-muted break-text">{{ Str::limit(strip_tags($child->html), 150) }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-muted italic mt-l">{{ trans('entities.books_empty_contents') }}</p>
                @endif
            </div>
        </main>

    </div>
@stop
