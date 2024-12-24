@extends('layouts.app')

@push('css')
<style type="text/css">
#userList {
    max-height: 200px;
    overflow: auto;
    padding-left: .5rem;
}
</style>
@endpush

@section('plugins.Datatables', true)
@section('plugins.Sweetalert2', true)

@section('subtitle', 'Permissions')
@section('content_header_title', 'User Management')
@section('content_header_subtitle', 'Permissions') 

@section('content_body')
<x-adminlte-datatable id="permissionsTable" :heads="$config['heads']" head-theme="light" theme="info" :config="$config"
    striped hoverable :with-buttons="$config['with-buttons']"/>

<x-adminlte-modal id="addEditItemModal" title="Add Permission" v-centered>
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

<x-adminlte-modal id="assignPermissionModal" title="Assign Permissions" size="lg" theme="teal"
    icon="fas fa-user-tag" v-centered static-backdrop scrollable>
    <div style="height:400px;">
        <p class="text-bold">
            Select the users who will be assigned the selected permission(s).
        </p>

        <x-adminlte-datatable id="assignUserPermissionsTable" :heads="$assignPermissions['heads']" :config="$assignPermissions" theme="info" striped hoverable/>

        <x-slot name="footerSlot">
            <x-adminlte-button id="assignUserPermission" theme="primary" label="Assign"/>
        </x-slot>
    </div>
</x-adminlte-modal>

<x-adminlte-modal id="removePermissionModal" title="Remove Permissions" size="lg" theme="warning"
    icon="fas fa-user-times" v-centered static-backdrop scrollable>
    <div style="height:400px;">
        <p class="text-bold">
           Select the users from whom the selected permission(s) will be removed.
        </p>

        <x-adminlte-datatable id="removeUserPermissionsTable" :heads="$removePermissions['heads']" :config="$removePermissions" theme="info" striped hoverable/>

        <x-slot name="footerSlot">
            <x-adminlte-button id="removeUserPermission" theme="danger" label="Remove"/>
        </x-slot>
    </div>
</x-adminlte-modal>

<template id="show-item-template">
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
            
            <button class="btn btn-link p-0 row d-flex" type="button" data-toggle="collapse" data-target="#collapseUserAccordion" aria-expanded="false" aria-controls="collapseUserAccordion">
                Users
            </button>
            
            <div class="collapse" id="collapseUserAccordion">
                <ul id="lists" class="list-group"></ul>
            </div>
        </div>
    </swal-html>
</template>
@endsection

@push('js')
<script>
const checkPermissions = {
    permission_create: {{ json_encode(Gate::check('permission_create')) }},
    permission_assign: {{ json_encode(Gate::check('permission_assign')) }},
    permission_remove: {{ json_encode(Gate::check('permission_remove')) }},
    permission_delete: {{ json_encode(Gate::check('permission_delete')) }}, 
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

const addBtn        = generateButtons('permission_create', 'addItem', null, 'success', 'Add Permission', 'fa fa-user-plus', 'addEditItemModal')
const assignBtn     = generateButtons('permission_assign', 'assignPermission', 'd-none', 'primary', 'Assign Permission', 'fas fa-user-shield', 'assignPermissionModal')
const removeBtn     = generateButtons('permission_remove', 'removePermission', 'd-none', 'warning', 'Remove Permission', 'fas fa-user-lock', 'removePermissionModal')
const deleteBulkBtn = generateButtons('permission_delete', 'deleteBulk', 'd-none', 'danger', 'Delete Permissions', 'fa fa-trash', 'deleteBulkModal')

const buttons = addBtn + assignBtn + removeBtn + deleteBulkBtn

$(() => {
    
    const table = $("#permissionsTable").DataTable()

    const fields = {
        name: "", 
        description: ""
    }

    addItem(fields, "#addItem", "#addEditItemForm", "Add Permission")

    viewItem(".show-item", "#show-item-template")

    editItem(".edit-item", "#addEditItemForm", "Edit Permission")

    submitForm(table, "#addEditItemForm")   

    $("form input").on("keyup", handleInputValidation)    

    handleModalActions("#assignPermissionModal")

    handleModalActions("#removePermissionModal")

    handleBulkCheckBoxes({ id: "#bulk", className: ".bulk" })

    handleBulkCheckBoxes({ id: "#bulkAssignPermission", className: ".bulkAssignPermission" })

    handleBulkCheckBoxes({ id: "#bulkRemovePermission", className: ".bulkRemovePermission" })

    deleteItem(table)

    handleUserPermissions(table, "#assignUserPermission", "attach")

    handleUserPermissions(table, "#removeUserPermission", "detach")

    deleteBulkItem(table)

    $("#permissionsTable_filter label").append(buttons)
})
</script>
@endpush