/**
 * Created by bishopj on 27/02/15.
 */


var loadData = {

    init : function() {
        loadData.refreshJira()
    },

    refreshJira : function() {
        $.ajax({
            type: 'GET',
            url: 'inc/jira.connect.php',
            data: { get_param: 'value' },
            dataType: 'json',
            success: function (JSONObject) {
                // JSONObject is simply the JSON item provided by jira.connect.php. Match the tag to get the value.

                // console.log(JSONObject);

                loadData.updateFooter(JSONObject["Newest"], JSONObject["NewestTime"]);
                loadData.updateNewCount(JSONObject["Unhandled"]);
                loadData.listNew(JSONObject["List"]);

                // This line pauses the AJAX function for 5 seconds, then calls itself again.
                setTimeout(loadData.refreshJira(), 5000);
            }
        });
    },

    updateFooter : function(ticket, time) {

        // console.log("Ticket = " + ticket + ", time = " + time);
        $("#footer").html("Latest ticket raised: " + ticket + " at " + time);
    },

    updateNewCount : function (unhandledCount) {

        // console.log("unhandledCount = " + unhandledCount)
        $("#unhandled").html(unhandledCount);
        if (unhandledCount > 0) {
            $("body").css("background-color", "#9e1412");
        } else {
            $("body").css("background-color", "#167B16");
        }

    },

    listNew : function (list) {

        $("#list ul").empty();
        for (var i in list) {
            console.log(list[i])
            $("#list ul").append(list[i]);
        }

    }
}