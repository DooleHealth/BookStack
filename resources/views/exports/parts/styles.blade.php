{{-- Fetch in our standard export styles --}}
<style>
    @if (!app()->runningUnitTests())
        {!! file_get_contents(public_path('/dist/export-styles.css')) !!}
    @endif
</style>

{{-- Apply any additional styles that can't be applied via our standard SCSS export styles --}}
@if ($format === 'pdf')
    <style>
        @page {
            margin: 2cm 1.5cm 2.5cm 1.5cm;
        }

        /* Patches for CSS variable colors within PDF exports */
        a {
            color: {{ setting('app-link') }};
        }

        blockquote {
            border-left-color: {{ setting('app-color') }};
        }

        img {
            max-width: 100%;
            height: auto;
        }


    </style>
@endif