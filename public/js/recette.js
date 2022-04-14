
jQuery(document).ready(function () {
    jQuery('.add-another-collection-widget').click(function (e) {
        
        var list = jQuery(jQuery(this).attr('data-list-selector'));
        var counter = list.data('widget-counter') || list.children().length;

        var newWidget = list.attr('data-prototype');

        newWidget = newWidget.replace(/__name__/g, counter);
        counter++;

        list.data('widget-counter', counter);
       

        var newElem = jQuery(list.attr('data-widget-tags')).html(newWidget);
        newElem.appendTo(list);
    });

    jQuery('.remove-another-collection-widget').click(function (e) {
        var list = jQuery(jQuery(this).attr('data-list-selector'));
        var counter = list.data('widget-counter') || list.children().length;

        if(counter > 0){
            counter--;
        }
    

        list.data('widget-counter', counter);
        list.children().last().remove();
    });
});
