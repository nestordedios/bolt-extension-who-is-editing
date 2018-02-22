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
                // whoiseditingdata array is populated in actions_widget.twig
                recordID: whoiseditingdata["recordID"],
                contenttype: whoiseditingdata["contenttype"],
                userID: whoiseditingdata["userID"],
                action: action,
            },
            dataType: "html"
        });

        request.done(function( msg ) {
            var widgetContainer = $(".widget-who-is-editing-widget");

            widgetContainer.empty();

            widgetContainer.append(msg);
        });

        request.fail(function( jqXHR, textStatus, errorThrown ) {
            alert("The request has failed!");
        });
    }

});