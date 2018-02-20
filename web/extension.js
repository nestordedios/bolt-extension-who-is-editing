$(document).ready(function(){

    window.setInterval(function(){
        updateWidget();
    }, 3000);

    $( window ).on('beforeunload', function() {
        updateWidget('close');
    });

    $( window ).load(function() {
        updateWidget('editcontent');
    });

    function updateWidget(action = null){

        var url = $('.actions-container').attr('data-actions-url');

        var request = $.ajax({
            url: url,
            method: "GET",
            data: {
                // editorstrackdata array is populated in actions_widget.twig
                recordID: editorstrackdata["recordID"],
                contenttype: editorstrackdata["contenttype"],
                userID: editorstrackdata["userID"],
                action: action,
            },
            dataType: "html"
        });

        request.done(function( msg ) {
            var widgetContainer = $(".widget-editors-actions-widget");

            widgetContainer.empty();

            widgetContainer.append(msg);
        });

        request.fail(function( jqXHR, textStatus, errorThrown ) {
            alert("The request has failed!");
        });
    }

});