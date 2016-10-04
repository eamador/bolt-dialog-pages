jQuery(document).ready(function($) {

    $('#dialog-pages-list').DataTable({
        "language": {
            "lengthMenu": "Display _MENU_ records per page",
            "zeroRecords": "Nothing found - sorry",
            "info": "Showing page _PAGE_ of _PAGES_",
            "infoEmpty": "No records available",
            "infoFiltered": "(filtered from _MAX_ total records)"
        }
    });

    //$('#dialog-pages-list').nestedSortable({
    //    handle: 'div',
    //    items: 'li',
    //    toleranceElement: '> div'
    //});

});