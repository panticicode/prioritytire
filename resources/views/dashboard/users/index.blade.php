@extends('layouts.app')

@section('plugins.Datatables', true)
@section('plugins.Sweetalert2', true)

@section('subtitle', 'Users')
@section('content_header_title', 'User Management')
@section('content_header_subtitle', 'Users') 

@section('content_body')

<x-adminlte-datatable id="usersTable" :heads="$config['heads']" head-theme="light" theme="info" :config="$config"
    striped hoverable :with-buttons="$config['with-buttons']" />

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

<template id="show-item-template">
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

            <button class="btn btn-link p-0 row d-flex" type="button" data-toggle="collapse" data-target="#collapsePermissionAccordion" aria-expanded="false" aria-controls="collapsePermissionAccordion">
                Permissions
            </button>
            
            <div class="collapse" id="collapsePermissionAccordion">
                <ul id="lists" class="list-group"></ul>
            </div>
        </div>
    </swal-html>
</template>
@endsection

@push('js')
<script>
const checkPermissions = {
    user_create: {{ json_encode(Gate::check('user_create')) }},
    user_delete: {{ json_encode(Gate::check('user_delete')) }}, 
}      
const generateButtons = (permission, id, hidden, theme, title, icon, modalId) => {

    if(checkPermissions[permission])
    {
        return `    
            <x-adminlte-button 
                id="${id}" 
                class="btn-sm mb-1 ml-1 ${hidden}" 
                theme="${theme}" 
                title="${title}"
                icon="${icon}"
                data-toggle="modal" 
                data-target="#${modalId}" 
            />
        `
    }

    return ''
} 

const addBtn        = generateButtons('user_create', 'addItem', null,  'success', 'Add User', 'fa fa-user-plus', 'addEditItemModal')

const deleteBulkBtn = generateButtons('user_delete', 'deleteBulk', 'd-none', 'danger', 'Delete Users', 'fa fa-trash', 'deleteBulkModal')

const buttons = addBtn + deleteBulkBtn

$(() => {
    
    const table = $("#usersTable").DataTable()

    const fields = {
        name: "", 
        email: "", 
        password: ""
    }

    addItem(fields, "#addItem", "#addEditItemForm", "Add User")

    viewItem(".show-item", "#show-item-template")

    editItem(".edit-item", "#addEditItemForm", "Edit User")

    submitForm(table, "#addEditItemForm")   

    $("form input").on("keyup", handleInputValidation)    

    handleBulkCheckBoxes({ id: '#bulk', className: '.bulk' })

    deleteItem(table)

    deleteBulkItem(table)

    $('#usersTable_filter label').append(buttons)
})
</script>
@endpush