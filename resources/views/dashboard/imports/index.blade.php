@extends('layouts.app')

@section('plugins.BsCustomFileInput', true)
@section('plugins.Sweetalert2', true)

@section('subtitle', 'Imports')
@section('content_header_subtitle', 'Imports') 

@section('content_body')
Imports
@endsection

@push('js')
<script>
console.log('works')
</script>
@endpush