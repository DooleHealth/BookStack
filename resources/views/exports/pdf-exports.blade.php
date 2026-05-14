@extends('layouts.simple')

@section('body')

    <div class="container small">

        <main class="card content-wrap auto-height mt-xxl">
            <h1 class="list-heading">{{ trans('entities.pdf_exports_title') }}</h1>
            <p class="text-muted">{{ trans('entities.pdf_exports_desc') }}</p>

            @if($exports->isEmpty())
                <p class="text-muted italic">{{ trans('entities.pdf_exports_empty') }}</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ trans('common.name') }}</th>
                            <th>{{ trans('entities.pdf_export_col_type') }}</th>
                            <th>{{ trans('common.status') }}</th>
                            <th>{{ trans('entities.pdf_export_col_date') }}</th>
                            <th>{{ trans('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($exports as $export)
                            <tr>
                                <td>{{ $export->entity_name }}</td>
                                <td>
                                    <span class="text-{{ $export->entity_type }}">
                                        @icon($export->entity_type)
                                        {{ ucfirst($export->entity_type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($export->status === 'pending')
                                        <span class="text-muted">@icon('time') {{ trans('entities.pdf_export_status_pending') }}</span>
                                    @elseif($export->status === 'processing')
                                        <span class="text-warning">@icon('sync') {{ trans('entities.pdf_export_status_processing') }}</span>
                                    @elseif($export->status === 'completed' && !$export->isExpired())
                                        <span class="text-pos">@icon('check-circle') {{ trans('entities.pdf_export_status_completed') }}</span>
                                    @elseif($export->status === 'completed' && $export->isExpired())
                                        <span class="text-muted">@icon('close') {{ trans('entities.pdf_export_status_expired') }}</span>
                                    @elseif($export->status === 'failed')
                                        <span class="text-neg">@icon('warning') {{ trans('entities.pdf_export_status_failed') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <small title="{{ $export->created_at }}">{{ $export->created_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    @if($export->isAvailable() && $export->getDownloadUrl() !== null)
                                        <a href="{{ $export->getDownloadUrl() }}" target="_blank" class="button outline small">
                                            @icon('download') {{ trans('common.download') }}
                                        </a>
                                    @elseif($export->status === 'failed' && $export->error_message)
                                        <small class="text-neg" title="{{ $export->error_message }}">{{ Str::limit($export->error_message, 50) }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </main>

    </div>

@stop
