



$(document).on('click', '.selectallornone', function () {
    $(this).trigger('rg2.open');
    debugger
    var $children = get_selectables(this);
    debugger
    $children.find(':checkbox').prop('checked', $children.find(':checkbox:not(:checked)').length > 0);
});


function get_selectables(item, deep) {

    return $selectables;
}