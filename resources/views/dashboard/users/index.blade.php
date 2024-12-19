@extends('layouts.app')

@section('plugins.Datatables', true)

@section('subtitle', 'Users')
@section('content_header_title', 'User Management')
@section('content_header_subtitle', 'Users') 

@section('content_body')
<x-adminlte-datatable id="table7" :heads="$config['heads']" head-theme="light" theme="info" :config="$config"
    striped hoverable with-buttons/>
@endsection