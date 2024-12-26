const state = {
    value: null,
    mode: null
}

/**
 * Sets the value and mode in the state, then triggers a custom event.
 *
 * This function is used to update the state with new values and mode,
 * and it triggers the 'valueChanged' event on the document with the 
 * new values and mode as parameters.
 *
 * @param {object} newValue - The new data to be set in the state.
 * @param {string} newMode - The mode to be set in the state, e.g., "create", "view", or "edit".
 */

const setValue = (newValue, newMode) => {
    if (newValue) state.value = newValue
    if (newMode) state.mode = newMode
    $(document).trigger("valueChanged", [newValue, newMode])
}

/**
 * Event listener for the 'valueChanged' event.
 *
 * This function listens for the custom 'valueChanged' event and updates
 * the form fields based on the current mode ('view' or 'edit'). It assigns
 * the values to the appropriate input fields in the DOM based on the state.
 *
 * @param {object} e - The event object.
 * @param {object} newValue - The new value passed when the 'valueChanged' event is triggered.
 */

$(document).on("valueChanged", (e, newValue) => {
    $.each(newValue, (key, value) => {

        if(state.mode === "view")
        {
            $(`#view-container #${key}`).val(value)
        }
        else
        {
            $(`#addEditItemModal #${key}`).val(value)
        }
    })
})

/**
 * Sets up CSRF token for AJAX requests.
 *
 * This function configures the global AJAX setup to include the CSRF token 
 * in the request headers, ensuring that all requests are secured.
 */

const csrf = () => {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $("meta[name='csrf-token']").attr("content")
        }
    })
} 

/**
 * Prepares the modal for adding a new item.
 *
 * This function is triggered when the button for adding a new item is clicked.
 * It sets the form values and updates the modal title, as well as changes 
 * the form action to the current URL for item creation.
 *
 * @param {object} fields - The fields to be set in the state.
 * @param {string} button - The button element that triggers the modal.
 * @param {string} form - The form element to be used in the modal.
 * @param {string} text - The text to be set in the modal title.
 */

const addItem = (fields, button, form, text) => {
    $(document).on("click", button, () => {
        setValue(fields, "create")
        $("#addEditItemForm").attr("action", window.location.href)
        $("#addEditItemModal .modal-title").text(text)
    })
}

/**
 * Prepares the modal for viewing an item.
 *
 * This function is triggered when the button to view an item is clicked.
 * It sends an AJAX request to fetch the item data and opens a SweetAlert modal
 * to display the details.
 *
 * @param {string} button - The button element that triggers the view action.
 * @param {string} template - The template to be used for displaying the modal.
 */

const viewItem = (button, template) => {
    $(document).on("click", button, (evt) => {
        $this = evt.currentTarget

        Swal.fire({
          template: template
        })
        
        $(".swal2-icon").removeClass("swal2-warning").addClass("swal2-info")
        const $id = $($this).data("id")
        const url = `${window.location.href}/${$id}`

        csrf()
        
        $.get(url, (res) => {
            setValue(res.data, "view")

            const eloquent = Object.keys(res)[1]
            const model    = window.location.href.split("/").pop().slice(0, -1)

            let lists = res[eloquent]?.length
                ? res[eloquent].map(list => `
                    <li class="list-group-item text-left px-0 pb-0">
                        <i class="fas fa-circle-notch fa-xs mr-1"></i>
                        <strong>${list.name}</strong> ${eloquent === 'users' ? `(${list.email})` : ''}
                    </li>
                `).join('')
                : `<li class="list-group-item text-center">No ${ eloquent } for the given ${ model }</li>`
            

            $('#lists').html(lists)
        })
    })
}

/**
 * Prepares the modal for editing an item.
 *
 * This function is triggered when the button to edit an item is clicked.
 * It sends an AJAX request to fetch the item data, sets the form values 
 * for editing, and opens the modal with the appropriate title.
 *
 * @param {string} button - The button element that triggers the edit action.
 * @param {string} form - The form element to be used in the modal.
 * @param {string} text - The text to be set in the modal title.
 */

const editItem = (button, form, text) => {
    $(document).on("click", button, (evt) => {
        $this = evt.currentTarget

        const $id = $($this).data("id")
        const url = `${window.location.href}/${$id}`

        csrf()
        
        $.get(url, (res) => {
            const response = { ...res.data, _method: "PUT" }
            setValue(response, "edit")
            
            $("#addEditItemModal .modal-title").text(text)

            $("#addEditItemForm").attr("action", `${url}`)

            $("#addEditItemModal").modal("show")
        })
    })
}

/**
 * Handles the form submission for adding or editing an item.
 *
 * This function listens for the form submission event, prevents the default behavior, 
 * and prepares the data to be sent via an AJAX POST request. If the current mode is 
 * "edit", it includes an ID and sets the _method to "PUT" for updating the item. 
 * The function then sends the data to the server, handles the response by either 
 * updating the table or adding a new row, and displays the corresponding alert. 
 * Additionally, it resets the form and cleans up validation errors if necessary.
 *
 * @param {object} table - The table object where the data will be added or updated.
 * @param {string} form - The form element that will be submitted.
 */

const submitForm = (table, form) => {
	$(form).submit((evt) => {
        evt.preventDefault()
        $this = evt.currentTarget

        url = $($this).attr("action")

        const formData = new FormData($(form)[0])
        let formObject = Object.fromEntries(formData.entries()) 
 
        csrf()

        if(state.mode === "edit")
        {
            const id = url.split("/").pop()
            formObject = { ...formObject, id: id, _method: "PUT" }
        }
        
        setValue(formObject)

        const params = state.value

        $.post(url, params, (res) => {
            if(res.status === 200)
            {
                $("#addEditItemModal").modal("hide")

                const recordId = res.data[1]
               
                const row = table.row(function(idx, data, node) {
                    return data[1] == recordId 
                })

                showAlert(res.theme, res.message)
              
                if (state.mode === "edit") 
                {
                    row.data(res.data)
                }
                else
                {
                    table.row.add(res.data).draw(false)
              
                    if($("#bulk").prop("disabled"))
                    {
                        $("#bulk").removeAttr("disabled")
                    }
                }

                $this.reset()
                cleanAlerts()
            }
        })
        .fail((err) => {
            handleValidationErrors(err)
        })
    })
}

/**
 * Handles validation errors from server response.
 *
 * This function processes the validation errors returned from the server. It adds 
 * an "is-invalid" class to the corresponding form fields and appends error messages 
 * below them. Each error message is displayed inside a `span` element with the 
 * class `invalid-feedback` to indicate an invalid input field. It processes all 
 * validation errors for each input field.
 *
 * @param {object} err - The error object returned from the server containing validation errors.
 */

const handleValidationErrors = (err) => {
    const validator = err.responseJSON.errors
    
    cleanAlerts()

    Object.keys(validator).forEach((key) => {
        let errorMessages = validator[key]
        
        errorMessages.forEach((message) => {
            let input = $(`#${key}`)

            if(!input.length)
            {   
                input = $("#files")
            }
            
            input.addClass("is-invalid")

            input.parent().after(`
                <span class="invalid-feedback d-block" role="alert">
                    <strong>${message}</strong>
                </span>
            `)
        })
    })
}

/**
 * Handles the input field validation on the client-side.
 *
 * This function checks if the input field has any value. If the input field is 
 * not empty, it removes the "is-invalid" class and hides the error message. If the 
 * input field is empty, it adds the "is-invalid" class and displays the error message 
 * next to the input field. This function is triggered by the input event to perform 
 * live validation.
 *
 * @param {Event} evt - The input event triggered by the user interacting with the input field.
 */

const handleInputValidation = (evt) => {
	const $this = evt.currentTarget

    if($this.value.length) 
    {
        $($this).removeClass("is-invalid")
        $($this).next(".invalid-feedback").removeClass("d-block").addClass("d-none")
    } 
    else 
    {
        $($this).addClass("is-invalid")
        $($this).next(".invalid-feedback").removeClass("d-none").addClass("d-block")
    }
}

/**
 * Handles item deletion with confirmation.
 *
 * This function listens for a click event on the delete button. When triggered, 
 * it shows a confirmation dialog using SweetAlert to ensure that the user wants 
 * to proceed with the deletion. If the user confirms, it sends a DELETE request 
 * to the server with the item's ID and removes the corresponding row from the table 
 * upon successful deletion. The deletion result is displayed in a SweetAlert popup.
 *
 * @param {object} table - The DataTable instance where the item will be removed.
 */

const deleteItem = (table) => {
	$(document).on("click", ".delete-item", (evt) => {
        $this = evt.currentTarget

        Swal.fire({
          	title: "Are you sure ?",
          	text: "You won\"t be able to revert this!",
          	icon: "warning",
          	showCancelButton: true,
          	confirmButtonColor: "#3085d6",
          	cancelButtonColor: "#d33",
            cancelButtonText: "No",
          	confirmButtonText: "Yes"
        })
        .then((result) => {
	        if (result.value) 
	        {
	            const $id = $($this).data("id")
	            const url = `${window.location.href}/${$id}`

	            const params = {
	                id:$id, 
	                _method:"delete"
	            }

	            csrf()
	         
	            $.post(url, params, (res) => {
	                Swal.fire({
		              title: "Deleted!",
		              text: res.message,
		              icon: res.theme
		            }) 
		            .then(() => {
		            	table.row($($this).parents().closest("tr")).remove().draw(false)  
		            })
	            })
	        }
        })
    })
}

/**
 * Handles the bulk assignment or removal of permissions for selected users.
 *
 * This function listens for a click event on a specific selector and handles the 
 * bulk assignment or removal of permissions based on the provided action ('attach' or 'detach'). 
 * It collects the selected permission and user IDs, sends a request to the server to perform the 
 * operation, and updates the table accordingly. If the operation is successful, the table rows 
 * are updated, reflecting the changes in the number of users associated with a particular permission.
 * The function also manages the display of appropriate modal windows and alerts, ensuring the 
 * user is informed about the result of the operation.
 *
 * @param {object} table - The DataTable instance that will be updated after the operation.
 * @param {string} selector - The CSS selector for the element that triggers the action (e.g., button).
 * @param {string} action - The action to perform ('attach' or 'detach') indicating whether to assign or remove the permission.
 */

const handleUserPermissions = (table, selector, action) => {
    $(selector).on("click", (evt) => {

        let checkboxes, modal

        if(action === 'attach')
        {
            checkboxes   = "bulkAssignPermission:checked"
            modal        = "#assignPermissionModal"
            errorMessage = "You must select at least one record to successfully assign permission to selected users."
        }
        else
        {
            checkboxes = "bulkRemovePermission:checked"
            modal      = "#removePermissionModal"
            errorMessage = "You must select at least one record to successfully remove permission from selected users."
        }

        const permissionIds = $(".bulk:checked").map((i, e) => $(e).data("id")).get().join(",")
        const userIds       = $(`.${checkboxes}`).map((i, e) => $(e).data("id")).get().join(",")

        const url = `${window.location.origin}/dashboard/user/${userIds}/permission/${permissionIds}/${action}`
        
        csrf()
        $.post(url, (res) => {
            $(modal).modal("hide")
            showAlert(res.theme, res.message)
            
            if(res.status === 200)
            {
                const permission_ids = permissionIds.split(",").map(Number)
                const user_ids       = userIds.split(",").map(Number)

                const items  = {  
                    permission_ids:permission_ids, 
                    user_ids:user_ids
                }

                items.permission_ids.forEach((permission_id, index) => {
                    const userCount = items.user_ids.length
                    
                    const row = table.row(function(idx, data, node) {
                        return data[1] == permission_id
                    })

                    if (row.length) 
                    {
                        const rowData = row.data()

                        rowData[4] = eval(`${rowData[4]} ${res.operation} ${userCount}`)
                        row.data(rowData).draw(false)
                    }
                })
                
            }

            uncheckElements(".bulk:checked")
            uncheckElements("#bulk:checked")
            uncheckElements(`.${checkboxes}`)
            uncheckElements(`#${checkboxes}`)

            toggleElement("#assignPermission", false)
            toggleElement("#removePermission", false)
            toggleElement("#deleteBulk", false)
        })
        .fail((err) => {
            
            if( err.status === 404 )
            {
                const theme   = "warning"
                showAlert(theme, errorMessage)
            }
        })
    })
}

/**
 * Handles the bulk deletion of selected items.
 *
 * This function listens for a click event on the delete button for bulk deletion. 
 * It collects the IDs of all selected items and presents the user with a confirmation dialog 
 * to ensure the action is intentional. If the user confirms the deletion, a `DELETE` request is 
 * sent to the server to delete the selected items. Upon successful deletion, the corresponding 
 * rows are removed from the table, and a success message is displayed. If the deletion is cancelled, 
 * the selection state is reset.
 *
 * @param {object} table - The DataTable instance that will have rows deleted after successful action.
 */

const deleteBulkItem = (table) => {
    $(document).on("click", "#deleteBulk", () => {
        let dataIds = []
        
        $(".bulk:checked").each((i, e) => {
            dataIds.push($(e).data("id"))
        })

        const ids = dataIds.join(",")
        
        Swal.fire({
            title: "Delete selected items?",  
            text: "This action cannot be undone!", 
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes",
            cancelButtonText: "No"
        })
        .then((result) => {
            if (result.value) 
            {
                const url = `${window.location.href}/${ids}/bulk`

                const params = {
                    ids:ids, 
                    _method:"delete"
                }

                csrf()
                
                $.post(url, params, (res) => {
                    Swal.fire({
                      title: "Deleted!",
                      text: res.message,
                      icon: res.theme
                    }) 
                    .then(() => {
                        table.rows(".selected").remove().draw(false)
                        isChecked({ id: '#bulk', className: '.bulk' })
                    })
                })
            }
            else
            {
                uncheckElements(".bulk:checked")
                isChecked({ id: '#bulk', className: '.bulk' })
            }
        })
    })
}

/**
 * Handles the actions that occur when a modal is shown or hidden.
 *
 * This function is triggered when a modal is either shown or hidden. When the modal is shown, 
 * the `aria-hidden` attribute is removed to indicate that it is visible. When the modal is hidden, 
 * the `aria-hidden` attribute is set to `true`, and any checkboxes related to bulk selection and permissions 
 * are unchecked. Additionally, bulk action buttons like "assign permission," "remove permission," and "delete" 
 * are hidden by default to ensure they are not available when the modal is closed.
 *
 * @param {string} selector - The CSS selector for the modal element.
 */

const handleModalActions = (selector) => {
    $(selector).on("show.bs.modal", () => {
        $(selector).removeAttr('aria-hidden')
    })
    $(selector).on("hidden.bs.modal", () => {
        $(selector).attr('aria-hidden', 'true')

        uncheckElements(".bulk:checked")
        uncheckElements("#bulk:checked")
        uncheckElements(".bulkAssignPermission:checked")
        uncheckElements("#bulkAssignPermission:checked")

        toggleElement("#assignPermission", false)
        toggleElement("#removePermission", false)
        toggleElement("#deleteBulk", false)
    })
}
/**
 * Handles the bulk checkbox functionality for selecting and deselecting table rows.
 *
 * This function manages the selection and deselection of all items when the bulk checkbox is clicked. 
 * It ensures that the state of each checkbox is synchronized with the master checkbox (select all). 
 * Additionally, it adds/removes the `selected` class on each row based on whether the checkbox is checked or unchecked. 
 * It also ensures that the bulk action buttons' visibility is correctly updated based on the selection state.
 *
 * @param {object} selector - An object containing `id` for the master checkbox and `className` for the individual checkboxes.
 */

const handleBulkCheckBoxes = (selector) => {
    $(selector.id).on("click", (evt) => {
        $this = evt.currentTarget
        
        $(selector.className).each((i, e) => {
            $(e).prop("checked", $($this).prop("checked"))

            if($(e).prop("checked"))
            {
                $(e).parents().closest("tr").addClass("selected")
            }
            else
            {
                $(e).parents().closest("tr").removeClass("selected")
            }
        })
        
        isChecked(selector)
    })

    $(document).on("change", selector.className, (evt) => {
        $this = evt.currentTarget

        if($($this).prop("checked"))
        {
            $($this).parents().closest("tr").addClass("selected")
        }
        else
        {
            $($this).parents().closest("tr").removeClass("selected")
        }
        isChecked(selector)
    })
}

/**
 * Unchecks all checkboxes matching the provided selector.
 *
 * This function unchecks all checkboxes that match the given selector, resetting the bulk selection state.
 *
 * @param {string} selector - The CSS selector for the checkboxes to uncheck.
 */

const uncheckElements = (selector) => {
    $(selector).prop("checked", false)
}

/**
 * Toggles the visibility of an element based on a boolean value.
 *
 * This function adds or removes the `d-none` class to an element based on the `show` parameter, which controls 
 * whether the element should be shown or hidden.
 *
 * @param {string} selector - The CSS selector for the element to toggle.
 * @param {boolean} show - A boolean indicating whether to show or hide the element.
 */

const toggleElement = (selector, show) => {
    $(selector).toggleClass("d-none", !show)
}

/**
 * Toggles the checked state and disabled state of a bulk checkbox.
 *
 * This function sets the checked state of a bulk checkbox based on the `show` parameter. It also enables 
 * or disables the checkbox based on the value of `show`.
 *
 * @param {string} selector - The CSS selector for the bulk checkbox to toggle.
 * @param {boolean} show - A boolean indicating whether to check or uncheck and enable or disable the checkbox.
 */

const toggleBulkElement = (selector, show) => {
    $(selector).prop("checked", show).attr("disabled", !show)
}

/**
 * Checks whether any checkboxes are selected and updates bulk action button states.
 *
 * This function checks if any individual checkboxes in the table are checked and updates the visibility 
 * of the bulk action buttons (such as "assign permission", "remove permission", and "delete") accordingly. 
 * It also ensures the main "select all" checkbox is correctly checked or unchecked based on the individual selections.
 *
 * @param {object} selector - An object containing `id` for the main checkbox and `className` for the individual checkboxes.
 */

const isChecked = (selector) => {
 
    const hasCheckedItems = $(`${selector.className}:checked`).length > 0
    const hasBulkItems = $(selector.className).length > 0

    const checkObj = { id: '#bulk', className: '.bulk' }

    const containsSelector = () => {
        return selector.id === checkObj.id && selector.className === checkObj.className
    }
    if(containsSelector())
    {
        toggleElement("#assignPermission", hasCheckedItems && $("#assignPermission").length)
        toggleElement("#removePermission", hasCheckedItems && $("#removePermission").length)
        toggleElement("#deleteBulk", hasCheckedItems)
    }
    
    toggleBulkElement(selector.id, hasBulkItems)

    if (!hasCheckedItems) 
    {
        $(selector.id).prop("checked", false)
    }
}

/**
 * Displays an alert message using SweetAlert2.
 *
 * This function shows a customizable alert message based on the provided theme and message. 
 * It uses the SweetAlert2 library to display the alert as a toast notification at the top of the page. 
 * The background color of the alert depends on the theme (e.g., warning or success).
 *
 * @param {string} theme - The theme of the alert (e.g., 'warning', 'success').
 * @param {string} message - The message to display in the alert.
 */

const showAlert = (theme, message) => {
    let background
    let icon = theme

    switch(theme)
    {
        case("warning"):
                background = "#f1ba15"
            break;
        case("danger"):
                background = "#dc3545"
                icon       = "error"
            break;   
        default:
                background = "#51a351"
            break;    
    }
    
	const Toast = Swal.mixin({
	  	toast: true,
	  	position: "top",
	  	showConfirmButton: false,
	  	showCloseButton: true,
	  	background: background,
	  	iconColor: "#fff",
	  	timer: 5000,
	  	timerProgressBar: true,
		didOpen: (toast) => {
		    toast.onmouseenter = Swal.stopTimer
		    toast.onmouseleave = Swal.resumeTimer
		},
		customClass: {
	       closeButton: "text-white",
	       title: "text-white"
	    }
	})
	Toast.fire({
	  icon: icon,
	  title: message
	})
}

/**
 * Cleans up any validation error messages and invalid styles.
 *
 * This function removes the `is-invalid` class and the associated error messages from form fields, 
 * ensuring that the form is reset and no validation errors are displayed.
 */

const cleanAlerts = () => {
    $(".is-invalid").removeClass("is-invalid")
    $(".invalid-feedback").remove()
}
