@extends('adminlte::page')

{{-- Extend and customize the browser title --}}

@section('title')
    {{ config('adminlte.title') }}
    @hasSection('subtitle') | @yield('subtitle') @endif
@stop

{{-- Add common CSS customizations --}}

@push('css')
    <style type="text/css">
    .nav-sidebar ul.nav-treeview li.nav-item a.nav-link {
        width: 96%;
    }
    .dataTables_filter label button#deleteBulk {
        padding: .25rem .7rem;
    }
    .swal2-custom-title {
        padding: 5px .9em 0;
        text-align: left;
    }
    .swal2-custom-label {
        display: flex;
        justify-content: start;
    }
    .swal2-custom-input {
        display: flex;
        margin: .5em 0 3px;
        width: 100%;
    }
    </style>
@endpush

{{-- Extend and customize the page content header --}}

@section('content_header')
    @hasSection('alerts')
        @yield('alerts')
    @endif
    @hasSection('content_header_title')
        <h1 class="text-muted">
            @yield('content_header_title')

            @hasSection('content_header_subtitle')
                <small class="text-dark">
                    <i class="fas fa-xs fa-angle-right text-muted"></i>
                    @yield('content_header_subtitle')
                    @hasSection('content_header_subtitle_item')
                        <i class="fas fa-xs fa-angle-right text-muted"></i>
                        @yield('content_header_subtitle_item')
                    @endif
                </small>
            @endif
        </h1>
    @endif
@stop

{{-- Rename section content to content_body --}}

@section('content')
    @yield('content_body')
@stop

{{-- Create a common footer --}}

@section('footer')
    <div class="float-right">
        Version: {{ config('app.version', '1.0.0') }}
    </div>

    <strong>
        <a href="{{ config('app.company_url', '#') }}">
            {{ config('app.company_name', 'PriorityTire') }}
        </a>
    </strong>
@stop

{{-- Add common Javascript/Jquery code --}}

@push('js')
<script src="{{ asset('js/functions.js') }}"></script>
@endpush
