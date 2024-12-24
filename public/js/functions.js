const state = {
    value: null,
    mode: null
}

const setValue = (newValue, newMode) => {
    if (newValue) state.value = newValue
    if (newMode) state.mode = newMode
    $(document).trigger("valueChanged", [newValue, newMode])
}

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
const csrf = () => {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $("meta[name='csrf-token']").attr("content")
        }
    })
} 

const addItem = (fields, button, form, text) => {
    $(document).on("click", button, () => {
        setValue(fields, "create")
        $("#addEditItemForm").attr("action", window.location.href)
        $("#addEditItemModal .modal-title").text(text)
    })
}

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

            let lists = res[eloquent].length
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

const handleValidationErrors = (err) => {
    const validator = err.responseJSON.errors
    
    cleanAlerts()

    Object.keys(validator).forEach((key) => {
        let errorMessages = validator[key]

        errorMessages.forEach((message) => {
            let input = $(`#${key}`)
        
            input.addClass("is-invalid")

            input.parent().append(`
                <span class="invalid-feedback d-block" role="alert">
                    <strong>${message}</strong>
                </span>
            `)  
        })
    })
}

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
            confirmButtonText: "Delete All"
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

const uncheckElements = (selector) => {
    $(selector).prop("checked", false)
}

const toggleElement = (selector, show) => {
    $(selector).toggleClass("d-none", !show)
}

const toggleBulkElement = (selector, show) => {
    $(selector).prop("checked", show).attr("disabled", !show)
}

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

const showAlert = (theme, message) => {
    let background
    switch(theme)
    {
        case("warning"):
                background = "#f1ba15"
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
	  icon: theme,
	  title: message
	})
}

const cleanAlerts = () => {
    $(".is-invalid").removeClass("is-invalid")
    $(".invalid-feedback").remove()
}
