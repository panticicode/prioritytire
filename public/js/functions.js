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

const addItem = (button, form, text) => {
    $(button).on("click", () => {
        setValue({name: "", email: "", password: ""}, "create")
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
            setValue({name: res.name, email: res.email}, "view")
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

            setValue({name: res.name, email: res.email, password: "", _method: "PUT"}, "edit")

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

                var recordId = res.data.id
  
                var row = table.row(function(idx, data, node) {
                    return data[1] == recordId 
                })

                showAlert(res.theme, res.message)
              
                if (state.mode === "edit") 
                {
                    row.data([
                        res.data.checkbox,
                        res.data.id,
                        res.data.name,
                        res.data.email,
                        res.data.action,
                    ])
                }
                else
                {
                    table.row.add([
                        res.data.checkbox,
                        res.data.id,
                        res.data.name,
                        res.data.email,
                        res.data.action,
                    ]).draw(false)

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

const deleteBulkItem = (table) => {
    $("#deleteBulk").on("click", () => {
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
                        isChecked()
                    })
                })
            }
            else
            {
                $(".bulk:checked").each((i, e) => {
                    $(e).prop("checked", false)
                })
                isChecked()
            }
        })
    })
}

const handleBulkCheckBoxes = () => {
    $("#bulk").on("click", (evt) => {
        $this = evt.currentTarget
        
        $(".bulk").each((i, e) => {
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
        
        isChecked()
    })

    $(document).on("change", ".bulk", (evt) => {
        $this = evt.currentTarget

        if($($this).prop("checked"))
        {
            $($this).parents().closest("tr").addClass("selected")
        }
        else
        {
            $($this).parents().closest("tr").removeClass("selected")
        }
        isChecked()
    })
}
const isChecked = () => {
    const isChecked = $(".bulk:checked").length > 0

    if(isChecked)
    {
        $("#deleteBulk").removeClass("d-none")
    }
    else
    {
        $("#deleteBulk").addClass("d-none")
        $("#bulk").prop("checked", false)
    }

    if($(".bulk").length > 0)
    {
        $("#bulk").removeAttr("disabled")
    }
    else
    {
        $("#bulk").attr("disabled", true)
    }
}

const showAlert = (theme, message) => {
	const Toast = Swal.mixin({
	  	toast: true,
	  	position: "top",
	  	showConfirmButton: false,
	  	showCloseButton: true,
	  	background: "#51a351",
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

