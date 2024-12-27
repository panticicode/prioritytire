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

@section('subtitle', $model . ' | ' . $type)
@section('content_header_title', 'Imported Data')
@section('content_header_subtitle', $model) 
@section('content_header_subtitle_item', $type)

@section('content_body')
<x-adminlte-datatable id="importedDataDataTable" :heads="$config['heads']" head-theme="light" theme="info" :config="$config"
    striped hoverable />

<template id="show-item-template">
    <swal-title>
        Audits Details
    </swal-title>
    <swal-icon type="warning" color="info"></swal-icon>
    <swal-html>
        <div id="view-container">
            <x-adminlte-datatable id="auditLogsDataTable" :heads="$heads" />
        </div>
    </swal-html>
</template>
@endsection

@push('js')
<script>
$(() => {
	const table = $("#importedDataDataTable").DataTable()

	viewDetails(".show-item", "#show-item-template", "#auditLogsDataTable")

	deleteItem(table)
})
</script>
@endpush