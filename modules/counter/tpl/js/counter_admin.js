// Go to date from admin page
function changeSelectedDate(selected_date) {
    var fo_obj = jQuery('#fo_counter').get(0);
    fo_obj.selected_date.value = selected_date;
    fo_obj.submit();
}
