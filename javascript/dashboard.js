$(document).on('click', '.selectallornone-userselection', function () {
    var $checkboxes = this.parentElement.getElementsByClassName("userselectioncheckbox");
    debugger
    if($checkboxes != undefined){
        if($checkboxes[0].checked == true){
            $checkboxes.forEach($checkbox => {
                $checkbox.checked = false;
            });
        }else{
            $checkboxes.forEach($checkbox => {
                $checkbox.checked = true;
            });
        }
    }
});

// This is the hacky but simple solution to getting the button to where it belongs. The buttion is rendered using functions from moodle-core which use echo.
// This cannot be included nicely into mustache ==> after rendering, put the button to the correct position with javascript.
$("#createnewquestion_button").appendTo("#dashboard_create_questions_div");