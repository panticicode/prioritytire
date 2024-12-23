@extends('layouts.app')

@section('plugins.Datatables', true)
@section('plugins.Sweetalert2', true)

@section('subtitle', 'Users')
@section('content_header_title', 'User Management')
@section('content_header_subtitle', 'Users') 

@section('content_body')
<x-adminlte-datatable id="userTable" :heads="$config['heads']" head-theme="light" theme="info" :config="$config"
    striped hoverable with-buttons/>

<x-adminlte-modal id="addEditItemModal" title="Add User">
    <form id="addEditItemForm" method="POST">
        @csrf 
        <x-adminlte-input name="name" label="Name" placeholder="{{ __('adminlte::adminlte.full_name') }}" label-class="text-lightblue">
            <x-slot name="prependSlot">
                <div class="input-group-text">
                    <i class="fas fa-user text-lightblue"></i>
                </div>
            </x-slot>
        </x-adminlte-input>

        <x-adminlte-input name="email" label="Email" type="email" placeholder="{{ __('adminlte::adminlte.email') }}" label-class="text-lightblue">
            <x-slot name="prependSlot">
                <div class="input-group-text">
                    <i class="fas fa-envelope text-lightblue"></i>
                </div>
            </x-slot>
        </x-adminlte-input>

        <x-adminlte-input name="password" label="Password" placeholder="{{ __('adminlte::adminlte.password') }}" type="password" label-class="text-lightblue">
            <x-slot name="prependSlot">
                <div class="input-group-text">
                    <i class="fas fa-lock text-lightblue"></i>
                </div>
            </x-slot>
        </x-adminlte-input>
    </form> 
    <x-slot name="footerSlot">
        <x-adminlte-button class="btn-flat" type="submit" title="Save" theme="success" icon="fas fa-lg fa-save" form="addEditItemForm" />
    </x-slot>
</x-adminlte-modal> 

<template id="view-item-template">
    <swal-title>
        User details
    </swal-title>
    <swal-icon type="warning" color="info"></swal-icon>
    <swal-html>
        <div id="view-container">
            <div class="form-group row">
                <label for="name" class="col-sm-2 col-form-label">Name</label>
                <div class="col-sm-10">
                  <input type="name" class="form-control" id="name">
                </div>
            </div>
            <div class="form-group row">
                <label for="email" class="col-sm-2 col-form-label">Email</label>
                <div class="col-sm-10">
                  <input type="email" class="form-control" id="email">
                </div>
            </div>
        </div>
    </swal-html>
</template>
@endsection

@push('js')
<script>
  
$(() => {
    
    const table = $("#userTable").DataTable()

    addItem("#addItem", "#addEditItemForm", "Add User")

    viewItem(".view-item", "#view-item-template")

    editItem(".edit-item", "#addEditItemForm", "Edit User")

    submitForm(table, "#addEditItemForm")   

    $("form input").on("keyup", handleInputValidation)    

    handleBulkCheckBoxes()

    deleteItem(table)

    deleteBulkItem(table)
})
</script>
@endpush