$(document).ready(function(){
        window.setInterval(function(){
            //console.log("here I go");
            updateWidget();
        }, 3000);

        $( window ).on('beforeunload', function() {
            updateWidget('close');
        });

        $( window ).load(function() {
            updateWidget('editcontent');
        });

        function updateWidget(action = null){

            var recordID = $("")

            var request = $.ajax({
                url: "/bolt/editorsActions",
                method: "GET",
                data: {
                    // editorstrackdata array is populated in actions_widget.twig
                    recordID: editorstrackdata["recordID"],
                    contenttype: editorstrackdata["contenttype"],
                    userID: editorstrackdata["userID"],
                    performedAction: action,
                },
                dataType: "html"
            });

            request.done(function( msg ) {
                var widgetContainer = $(".widget-editors-actions-widget");

                widgetContainer.empty();

                widgetContainer.append(msg);

                //console.log("widgetContainer", widgetContainer);
                $( "#log" ).html( msg );
            });

            request.fail(function( jqXHR, textStatus, errorThrown ) {
                //console.log( "Request failed: " + textStatus );
                //console.log($("#error").html(jqXHr.responseText));
            });
        }

    });