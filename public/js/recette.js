
jQuery(document).ready(function () {
    jQuery('.add-another-collection-widget').click(function (e) {
        var list = jQuery(jQuery(this).attr('data-list-selector'));
        console.log(list);
        var counter = list.data('widget-counter') || list.children().length;

        var newWidget = list.attr('data-prototype');

        newWidget = newWidget.replace(/__name__/g, counter);
        console.log
        counter++;

        list.data('widget-counter', counter);
       

        var newElem = jQuery(list.attr('data-widget-tags')).html(newWidget);
        newElem.appendTo(list);
    });

    jQuery('.remove-another-collection-widget').click(function (e) {
        var list = jQuery(jQuery(this).attr('data-list-selector'));

        var counter = list.data('widget-counter') || list.children().length;


        counter--;

        list.data('widget-counter', counter);
        list.children().last().remove();
    });
});