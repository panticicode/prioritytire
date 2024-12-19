@extends('layouts.app')

@section('plugins.Datatables', true)

@section('subtitle', 'Permissions')
@section('content_header_title', 'User Management')
@section('content_header_subtitle', 'Permissions') 

@section('content_body')
<x-adminlte-datatable id="table7" :heads="$config['heads']" head-theme="light" theme="info" :config="$config"
    striped hoverable with-buttons/>
@endsection