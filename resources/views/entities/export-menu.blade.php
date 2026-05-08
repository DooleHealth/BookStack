<div component="dropdown"
     class="dropdown-container"
     id="export-menu">

    <button refs="dropdown@toggle"
         class="icon-list-item text-link"
         aria-haspopup="true"
         aria-expanded="false"
         aria-label="{{ trans('entities.export') }}"
         data-shortcut="export">
        <span>@icon('export')</span>
        <span>{{ trans('entities.export') }}</span>
    </button>

    <ul refs="dropdown@menu" class="wide dropdown-menu" role="menu">
        <li><a href="{{ $entity->getUrl('/export/pdf-email') }}" role="menuitem" class="label-item"><span>{{ trans('entities.export_pdf') }}</span><span>.pdf</span></a></li>
    </ul>

</div>
