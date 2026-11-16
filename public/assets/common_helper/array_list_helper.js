function handleApiResponse111({ list, response, action = 'create', targetItem = null, currentId = 0, onSuccess, onError }) {
    if (response?.data?.errorcode === 0) {
        if (action === 'delete' && targetItem) {
            list = list.filter(item => item.id !== targetItem.id);
        } else {
            const newData = response.data.data;
            if (currentId > 0) {
                const index = list.findIndex(item => item.id === currentId);
                if (index !== -1) {
                    //list[index] = { ...list[index], ...newData };
                    list.splice(index, 1, { ...newData });
                }else {
                    list.push({
                        id: currentId,
                        ...newData
                    });
                }
            } else {
                list.push(newData);
            }
            if (typeof onSuccess === 'function') onSuccess(response);
        }
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: response.data.errormessage,
            showConfirmButton: false,
            timer: 1500
        });
    } else {
        Swal.fire({
            position: 'center',
            icon: 'error',
            title: response.data.errormessage,
            showConfirmButton: false,
            timer: 1500
        });
        if (typeof onError === 'function') onError(response);
    }
    return list;
}
function handleApiResponse({ list, response, action = 'create', targetItem = null, currentId = 0, onSuccess, onError }) {
    if (response?.data?.errorcode === 0) {
        const newData = response.data.data;

        // If action is 'delete', remove the target item
        if (action === 'delete' && targetItem) {
            list = list.filter(item => item.id !== targetItem.id);
        } else {
            // Update existing item if currentId is present
            if (currentId > 0) {
                const index = list.findIndex(item => item.id === currentId);
                if (index !== -1) {
                    // If item exists, replace it with the new data
                    list.splice(index, 1, { ...list[index], ...newData });
                } else {
                    // If item doesn't exist, push new data with currentId
                    list.push({
                        id: currentId,
                        ...newData
                    });
                }
            } else {
                // If no currentId, push new item (i.e., on creation)
                list.push(newData);
            }
            // Trigger success callback
            if (typeof onSuccess === 'function') onSuccess(response);
        }

        // Show success alert
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: response.data.errormessage,
            showConfirmButton: false,
            timer: 1500
        });
    } else {
        // Show error alert if there's an issue with the response
        Swal.fire({
            position: 'center',
            icon: 'error',
            title: response.data.errormessage,
            showConfirmButton: false,
            timer: 1500
        });
        // Trigger error callback if present
        if (typeof onError === 'function') onError(response);
    }

    return list;
}


function manageResponse(response,onSucess,onError){
    if (response?.data?.errorcode === 0) {
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: response.data.errormessage,
            showConfirmButton: false,
            timer: 1500
        });
        if (typeof onSucess === 'function') onSucess(response);
    } else {
        Swal.fire({
            position: 'center',
            icon: 'error',
            title: response.data.errormessage,
            showConfirmButton: false,
            timer: 1500
        });
        if (typeof onError === 'function') onError(response);
    }
    return response;
}
function showSuccessToast(message){
    Swal.fire({
        position: 'center',
        icon: 'success',
        title: message,
        showConfirmButton: false,
        timer: 1500
    });
}
function showErrorToast(message){
    Swal.fire({
        position: 'center',
        icon: 'error',
        title: message,
        showConfirmButton: false,
        timer: 1500
    });
}
function formatDate(dateString) {
    const date = new Date(dateString);
    
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();

    return `${day}-${month}-${year}`; 
}
