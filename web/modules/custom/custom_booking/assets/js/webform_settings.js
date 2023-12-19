(function ($) {
    $(document).ready(function () {
        if(('#edit-employee-details--2 #edit-employee-status').length) {
            //hides employee name and email field when the default selected option is old - existing employee
           var currentValue = $("#edit-employee-details--2 #edit-employee-status option:selected").val();
            //var currentValue = $("#edit-employee-details #edit-employee-status").val();
            if(currentValue == 'old') {
                $('.form-item-employee-name').addClass('field-display-none');
                $('.form-item-employee-email').addClass('field-display-none');
            }
            else {
                $('.form-item-employee-name').removeClass('field-display-none');
                $('.form-item-employee-email').removeClass('field-display-none');
            }
            $('#edit-employee-status').on('change', function () {
                var selectedValue = $(this).val();
                if(selectedValue == 'old') {
                    $('.form-item-employee-name').addClass('field-display-none');
                    $('.form-item-employee-email').addClass('field-display-none');
                }
                else {
                    $('.form-item-employee-name').removeClass('field-display-none');
                    $('.form-item-employee-email').removeClass('field-display-none');
                }
            });
        }

        // Room Avaiblity.
        $('td.views-field-available-rooms').each(function () {
            if ($(this).find('.occupancy-green').length > 0 || $(this).find('.occupancy-yellow').length > 0) {
                // Find the corresponding anchor tag in the next td
                var anchorTag = $(this).next('td').find('.add-booking');
                // Remove the 'disabled' class to the anchor tag
                anchorTag.removeClass('disabled');
            }
            if ($(this).find('.occupancy-green').length > 0) {
                // Find the corresponding anchor tag in the next td
                var anchorTag = $(this).next('td').find('.view-booking');
                // Remove the 'disabled' class to the anchor tag
                anchorTag.addClass('disabled');
            }
        });
    });
})(jQuery);
