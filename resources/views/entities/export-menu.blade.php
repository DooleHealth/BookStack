<div class="dropdown-container" id="export-menu">
    <a href="{{ $entity->getUrl('/export/pdf-email') }}"
         class="icon-list-item text-link"
         aria-label="{{ trans('entities.export_pdf_file') }}"
         data-shortcut="export">
        <span>@icon('export')</span>
        <span>{{ trans('entities.export_pdf_file') }}</span>
    </a>
</div>
