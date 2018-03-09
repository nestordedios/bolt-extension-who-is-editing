$(document).ready(function(){

    if(typeof whoiseditingdata != 'undefined') {
        window.setInterval(function(){
            updateWidget();
        }, 3000);

        $( window ).on('beforeunload', function() {
            updateWidget('close');
        });

        $( window ).load(function() {
            updateWidget('editcontent');
        });
    }

    function updateWidget(action = 'editcontent'){

        var url = $('.actions-container').attr('data-actions-url');

        var requestData = {
            // whoiseditingdata array is populated in actions_widget.twig
            recordID: whoiseditingdata["recordID"],
            contenttype: whoiseditingdata["contenttype"],
            userID: whoiseditingdata["userID"],
            action: action,
        }

        var request = $.ajax({
            url: url,
            method: "GET",
            data: requestData,
            dataType: "html"
        });

        request.done(function( msg ) {
            var widgetContainer = $(".widget-who-is-editing-widget");

            widgetContainer.empty();

            widgetContainer.append(msg);
        });

        request.fail(function( jqXHR, textStatus, errorThrown ) {
        });
    }

});