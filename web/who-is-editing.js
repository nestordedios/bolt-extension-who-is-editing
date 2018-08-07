$(document).ready(function(){

    if(typeof whoiseditingdata !== 'undefined') {
        window.who_is_editing_interval = window.setInterval(updateWidget, whoiseditingdata["whoiseditingTimeInterval"]);

        $( window ).on('beforeunload', function() {
            updateWidget('close');
        });

        $( window ).load(function() {
            updateWidget('editcontent');
        });
    }

    function updateWidget(action){
        if(typeof action === 'undefined') {
            action = 'editcontent';
        }
        var token = $('#content_edit__token').val();

        var requestData = {
            // whoiseditingdata array is populated in actions_widget.twig
            recordID: whoiseditingdata["recordID"],
            contenttype: whoiseditingdata["contenttype"],
            userID: whoiseditingdata["userID"],
            token: token,
            action: action
        };

        var request = $.ajax({
            url: whoiseditingdata["url"],
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
