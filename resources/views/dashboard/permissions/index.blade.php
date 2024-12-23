@extends('layouts.app')

@section('plugins.Datatables', true)
@section('plugins.Sweetalert2', true)

@section('subtitle', 'Permissions')
@section('content_header_title', 'User Management')
@section('content_header_subtitle', 'Permissions') 

@section('content_body')
<x-adminlte-datatable id="permissionTable" :heads="$config['heads']" head-theme="light" theme="info" :config="$config"
    striped hoverable with-buttons/>

<x-adminlte-modal id="addEditItemModal" title="Add Permission">
    <form id="addEditItemForm" method="POST">
        @csrf 
        <x-adminlte-input name="name" label="Name" placeholder="Permission name" label-class="text-lightblue">
            <x-slot name="prependSlot">
                <div class="input-group-text">
                    <i class="fas fa-key text-lightblue"></i>
                </div>
            </x-slot>
        </x-adminlte-input>

        <x-adminlte-textarea name="description" label="Description" rows=5 label-class="text-lightblue"
            igroup-size="sm" placeholder="Permission description">
            <x-slot name="prependSlot">
                <div class="input-group-text">
                    <i class="fas fa-lg fa-info-circle text-lightblue"></i>
                </div>
            </x-slot>
        </x-adminlte-textarea>
    </form> 
    <x-slot name="footerSlot">
        <x-adminlte-button class="btn-flat" type="submit" title="Save" theme="success" icon="fas fa-lg fa-save" form="addEditItemForm" />
    </x-slot>
</x-adminlte-modal> 

<template id="view-item-template">
    <swal-title>
        Permission details
    </swal-title>
    <swal-icon type="warning" color="info"></swal-icon>
    <swal-html>
        <div id="view-container">
            <div class="form-group row">
                <label for="name" class="col-form-label">Name</label>
                <input type="name" class="form-control" id="name" readonly>
            </div>
            <div class="form-group row">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" rows="3" readonly></textarea>
            </div>
        </div>
    </swal-html>
</template>
@endsection

@push('js')
<script>
  
$(() => {
    
    const table = $("#permissionTable").DataTable()

    const fields = {
        name: "", 
        description: ""
    }

    addItem(fields, "#addItem", "#addEditItemForm", "Add Permission")

    viewItem(".view-item", "#view-item-template")

    editItem(".edit-item", "#addEditItemForm", "Edit Permission")

    submitForm(table, "#addEditItemForm")   

    $("form input").on("keyup", handleInputValidation)    

    handleBulkCheckBoxes()

    deleteItem(table)

    deleteBulkItem(table)
})
</script>
@endpush