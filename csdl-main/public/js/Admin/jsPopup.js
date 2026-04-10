function openForm() {
    document.getElementById('formOverlay').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeForm() {
    document.getElementById('formOverlay').style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('categoryForm').reset();
}
function openDetailsForm(productID) {
    document.querySelectorAll('.form-overlay').forEach(form => {
        form.style.display = 'none';
    });

    const form = document.getElementById('formDetailsOverlay-' + productID);
    if (form) {
        form.style.display = 'block';
    }
}
function closeDetailsForm(productID) {
    const form = document.getElementById('formDetailsOverlay-' + productID);
    if (form) {
        form.style.display = 'none';
    }
}
function openExcelDetailsForm(productID){
    document.querySelectorAll('.form-overlay').forEach(form => {
        form.style.display = 'none';
    });

    const form = document.getElementById('formExcelDetailsOverlay-' + productID);
    if (form) {
        form.style.display = 'block';
    }
}
function closeExcelDetailsForm(productID) {
    const form = document.getElementById('formExcelDetailsOverlay-' + productID);
    if (form) {
        form.style.display = 'none';
    }
}


function openEditForm(categoryID) {
    document.querySelectorAll('.form-overlay').forEach(form => {
        form.style.display = 'none';
    });

    const form = document.getElementById('editFormOverlay-' + categoryID);
    if (form) {
        form.style.display = 'block';
    }
}

function closeEditForm(categoryID) {
    const form = document.getElementById('editFormOverlay-' + categoryID);
    if (form) {
        form.style.display = 'none';
    }
}

function openImageUploadForm(productID) {
    document.getElementById('imageUploadFormOverlay-' + productID).style.display = 'flex';
}

function closeImageUploadForm(productID) {
    document.getElementById('imageUploadFormOverlay-' + productID).style.display = 'none';
}


let currentForm = null;

document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function () {
        currentForm = this.closest('form');
        document.getElementById('deleteModal').style.display = 'flex';
    });
});

function closeModal() {
    document.getElementById('deleteModal').style.display = 'none';
    currentForm = null;
}



