$(document).foundation();
$(document).on('click',".comForm", function(event) {
    event.preventDefault();
    $(this).next(".showActionComment").show();
});