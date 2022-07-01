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
