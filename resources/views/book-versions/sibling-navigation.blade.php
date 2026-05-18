@php
    $eq = $embedQuery ?? '';
@endphp
<div class="grid half collapse-xs items-center mb-m px-m no-row-gap print-hidden">
    <div>
        @if($previous)
            @php
                $prevUrl = $previous['type'] === 'chapter'
                    ? url($urlBase . '/chapter/' . urlencode($previous['slug']) . $eq)
                    : url($urlBase . '/page/' . urlencode($previous['slug']) . $eq);
                $prevIcon = $previous['type'] === 'chapter' ? 'chapter' : 'page';
            @endphp
            <a href="{{ $prevUrl }}" class="outline-hover no-link-style block rounded">
                <div class="px-m pt-xs text-muted">{{ trans('common.previous') }}</div>
                <div class="inline-block">
                    <div class="icon-list-item no-hover">
                        <span class="text-{{ $prevIcon }}">@icon($prevIcon)</span>
                        <span>{{ Str::limit($previous['name'], 48) }}</span>
                    </div>
                </div>
            </a>
        @endif
    </div>
    <div>
        @if($next)
            @php
                $nextUrl = $next['type'] === 'chapter'
                    ? url($urlBase . '/chapter/' . urlencode($next['slug']) . $eq)
                    : url($urlBase . '/page/' . urlencode($next['slug']) . $eq);
                $nextIcon = $next['type'] === 'chapter' ? 'chapter' : 'page';
            @endphp
            <a href="{{ $nextUrl }}" class="outline-hover no-link-style block rounded text-xs-right">
                <div class="px-m pt-xs text-muted text-xs-right">{{ trans('common.next') }}</div>
                <div class="inline block">
                    <div class="icon-list-item no-hover">
                        <span class="text-{{ $nextIcon }}">@icon($nextIcon)</span>
                        <span>{{ Str::limit($next['name'], 48) }}</span>
                    </div>
                </div>
            </a>
        @endif
    </div>
</div>
