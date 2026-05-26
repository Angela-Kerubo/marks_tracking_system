$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Form validation helper
    window.validateForm = function(formId) {
        let isValid = true;
        $(`#${formId} input[required], #${formId} select[required], #${formId} textarea[required]`).each(function() {
            if (!$(this).val()) {
                $(this).css('border-color', '#dc3545');
                isValid = false;
            } else {
                $(this).css('border-color', '#e0e0e0');
            }
        });
        return isValid;
    };
    
    // Mark validation (0-100)
    window.validateMarks = function(value) {
        let num = parseFloat(value);
        return !isNaN(num) && num >= 0 && num <= 100;
    };
});

// Function to show notification
function showNotification(message, type = 'info') {
    let alertClass = '';
    switch(type) {
        case 'success': alertClass = 'alert-success'; break;
        case 'error': alertClass = 'alert-danger'; break;
        case 'warning': alertClass = 'alert-warning'; break;
        default: alertClass = 'alert-info';
    }
    
    let notification = $(`<div class="alert ${alertClass}" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">${message}</div>`);
    $('body').append(notification);
    
    setTimeout(function() {
        notification.fadeOut('slow', function() { $(this).remove(); });
    }, 5000);
}