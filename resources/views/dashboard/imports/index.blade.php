@extends('layouts.app')

@push('css')
<style type="text/css">
.swal2-popup.swal2-modal.swal2-view {
	width: 70%;
}
.dataTables_length {
	text-align: left;
}
</style>
@endpush

@section('plugins.Datatables', true)
@section('plugins.Sweetalert2', true)

@section('subtitle', 'Imports')
@section('content_header_subtitle', 'Imports') 

@section('content_body')
<x-adminlte-datatable id="importDataTable" :heads="$config['heads']" head-theme="light" theme="info" :config="$config"
    striped hoverable />

<template id="show-item-template">
    <swal-title>
        Logs Details
    </swal-title>
    <swal-icon type="warning" color="info"></swal-icon>
    <swal-html>
        <div id="view-container">
            <x-adminlte-datatable id="logsDataTable" :heads="$heads" />
        </div>
    </swal-html>
</template>
@endsection

@push('js')
<script>
	viewDetails(".show-item", "#show-item-template", "#logsDataTable")
</script>
@endpush