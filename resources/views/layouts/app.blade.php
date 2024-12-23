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
            {{ config('app.company_name', 'My company') }}
        </a>
    </strong>
@stop

{{-- Add common Javascript/Jquery code --}}

@push('js')
<script src="{{ asset('js/functions.js') }}"></script>
<script>
$(() => {
    const button = `
        <x-adminlte-button 
            id="addItem" 
            class="btn-sm mb-1" 
            theme="success" 
            title="Add Item"
            icon="fa fa-plus"
            data-toggle="modal" 
            data-target="#addEditItemModal" 
        />

        <x-adminlte-button 
            id="deleteBulk" 
            class="btn-sm mb-1 d-none" 
            theme="danger" 
            title="Delete Items"
            icon="fa fa-trash"
        />
    `.replace(/\s+/g, ' ').trim()
    
    $('.dataTables_filter label').append(button)
})
</script>
@endpush
