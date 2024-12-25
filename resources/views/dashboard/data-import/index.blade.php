@extends('layouts.app')

@push('css')
<style type="text/css">
.alert-success h5 {
	margin: 0;
}
.input-group-prepend {
    width: 125px;
}
.invalid-feedback {
	padding-left: .5rem;
	padding-right: .5rem;
}
</style>
@endpush

@section('plugins.BsCustomFileInput', true)
@section('plugins.Sweetalert2', true)

@section('alerts')
@if (session('success'))
<x-adminlte-alert 
	theme="success" 
	class="bg-teal py-1 w-75 mx-auto" 
	icon="fas fa-sm fa-check-circle" 
	title="Import Completed" 
	dismissable
>
    <i class="text-dark">{{ session('success') }}!</i>
</x-adminlte-alert>
@endif
@endsection

@section('subtitle', 'Data Import')
@section('content_header_subtitle', 'Data Import') 

@section('content_body')
<div class="container">
    <h1>Data Import</h1>
    <form action="{{ route('dashboard.data-import.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
   
		<div class="my-2">&nbsp;</div>

		<x-adminlte-select id="type" name="type" class="col">
		    <x-slot name="prependSlot">
		        <label for="type" class="col col-form-label">
		            Import Type
		        </label>
		    </x-slot>
		    <option disabled selected>{{ $config['label'] }}</option>
		    @foreach($config['files'] as $key => $type)
				<option value="{{ $key }}">{{ $type['label'] }}</option>
			@endforeach
		</x-adminlte-select>
		
        <x-adminlte-input-file id="files" name="files[]" placeholder="Choose File" class="col" aria-describedby="importHelp" multiple>
		    <x-slot name="prependSlot">
		        <label for="files" class="col col-form-label">
		            DS Sheet
		        </label>
		    </x-slot>
		</x-adminlte-input-file>
		
	    @if ($errors->any())
		    @foreach ($errors->all() as $error)
		    	@continue(str_contains($error, 'The type field') || str_contains($error, 'The files field is required'))
                <span class="invalid-feedback d-block" role="alert">
		        	<strong>{{ $error }}</strong>
		        </span>
            @endforeach
		@endif

		<div class="form-group">
			<div class="input-group">
				<div class="input-group-prepend"></div>
				<small id="importHelp" class="form-text text-muted">
					Required Headers: Order Data, Channel, SKU, Item Description, Origin, SO#, Total Price, Cost, Shipping Cost, Profit
				</small>
			</div>
		</div>

		<div class="form-group">
			<div class="input-group">
				<div class="input-group-prepend"></div>
				<x-adminlte-button label="Import" theme="primary" type="submit" />
			</div>
		</div>
    </form>
</div>
@endsection
