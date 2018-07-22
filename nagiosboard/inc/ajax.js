/**
 * Created by bishopj on 6/03/15.
 */

var loadData = {

    init : function() {
        loadData.getNagios("office");
    },

    getNagios : setInterval(function() {

        $.ajax({
            type: 'GET',
            url: 'inc/nagios.connect.php',
            data: 'env=office',
            success: function (data) {
                var json = $.parseJSON(data);
                loadData.fillData(json, 'office');
            }
        });

        $.ajax({
            type: 'GET',
            url: 'inc/nagios.connect.php',
            data: 'env=prod',
            success: function (data) {
                var json = $.parseJSON(data);
                loadData.fillData(json, 'production');
            }
        });

    }, 5000),

    fillData : function(json, environment) {
        $("#" + environment + " div").empty();

        if (json.length > 0) {
            display.showList(environment);
            for (i = 0; i < json.length; i++) {
                console.log(json[i]["Hostname"]);
                display.addEntry(environment, json[i]);
            }
        } else {
            display.hideList(environment);
        }
    },

    capital : function(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
};

var display = {

    addEntry : function(environment, entry) {
        var html = '<div class="container ' + entry["Level"] + '"><div class="hostname">' + entry["Hostname"] + '</div>' + '<div class="info">' + entry["Info"] + '</div></div>';
        console.log(html);
        var updateDiv = $("#" + environment).find(".list");
        $(updateDiv).append(html);

    },

    showList : function(environment) {
        var html = '<div id="' + environment + '" class="errors">' + loadData.capital(environment) + '<div class="list"></div></div>';

        // If the div doesn't exist (.length returns false), create the div
        if(!$("#" + environment).length) {
            $("#errors").append(html);
        }

        // If there are 2 lists, set the width to half.
        if($("#errors").children().size() > 1) {
            $(".errors").css("max-width", "48%");
        } else {
            $(".errors").css("max-width", "");
            $(".errors").css("width", "95%");
        }
    },

    hideList : function(environment) {
        $("#" + environment).remove();
        if($("#errors").children().size() == 0) {
            $("body").css("background-color", "#167B16");
        } else {
            $("body").css("background-color", "black");
        }
    }
};