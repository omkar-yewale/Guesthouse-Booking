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
    });
})(jQuery);
