$(document).ready(function () {
    $('#submit').click(onSubmitClick);
});

function onSubmitClick(e) {
    if ($('#user').val() == "") {
        e.stopImmediatePropagation();
        alert('User is empty! Fill it, please.');
        return false;
    } else {
        return true;
    }
}

