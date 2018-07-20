//
// Reads the status from statuspage.json and updates the widget accordingly
//
// v0.1 / 02/11/17 - James Bishop - Initial Build
// v0.2 / 03/11/17 - James Bishop - Populate component list, add animation
// v0.3 / 06/11/17 - James Bishop - Add the ability to close/ hide the widget, further formatting, and handling of "Under Maintenance"
//                   Initial release to Production and commit to SVN
// v0.4 / 08/11/17 - James Bishop - Almost complete rewrite to move the widget to the left of screen. Show all statuses at all times.
// v1.0 / 18/12/17 - James Bishop - Reverted back to 0.3 codebase to move widget back to the bottom. Simplified logic and removed the ability to
//						close and hide the widget. Also moved slideup/slidedown functionality to pure CSS. Coincides with the movement of the 
//						feedback widget to the same location (edited style.css .innovation classes)
// v1.1 / 20/12/17 - James Bishop - Add transparent div, like a modal background, when either widget is expanded. Click anywhere (on the div) to shrink the widgets back down.
// v1.2 / 20/12/17 - James Bishop - Display number of active incidents in the List Header

var statusPage = {
	
	// Function to initialise the widget (Load CSS, create DOM elements, start the refresh timer)
	// The page I added this widget to consists of compiled PHP, which I can't access to modify the DOM, but it does call Javascript.
	// That's where I'll inject my statuspage.init()!
	init : function() {
		// Add the CSS and HTML
		$('head').append('<link href="statuspage.css" rel="stylesheet" type="text/css" />');
		$('body').append(
		'<div id="statuscontainer">'
			+ '<div id="statusheader">'
				+ '<h4><span id="heading-dot"></span><span id="heading-description"></span></h4>'
			+ '</div>'
			+ '<div id="statuslist">'
			+ '</div>'
		+ '</div>');
		
		// Get the StatusPage data
		statusPage.refresh();
		var start = setInterval(function(){ statusPage.refresh() }, 10000);
		
		$('#statusheader').on('click', function() {statusPage.toggleList();});
	},
	
	refresh : function() {
		$.ajax({
				type: 'GET',
				
				url: 'statuspage.json',
				
				
				data: { get_param: 'value' },
				dataType: 'json',
				success: function (JSONObject) {
					// Update the text description
					$('#heading-description').text(JSONObject.status.description);
					// Remove any previously added classes (severity level) and add the current one
					$('#heading-dot').removeClass('critical major minor maintenance none');
					$('#heading-dot').addClass(JSONObject.status.indicator);
					
					// Populate the list
					var header = '<div id="listheading">Current Systems Status</div>';
					$("#statuslist").html(header);
					for (var component in JSONObject.components) {
							statusPage.addToList(JSONObject.components[component]);
					}
					$(".listitem").on('click', function() {var win = window.open(JSONObject.page.url, '_blank');});
					
					// Are there any incidents?
					var incidents = JSONObject.incidents;
					if (incidents.length > 0) {
						statusPage.activeIncidents(incidents);
					};
			}
		});
	},
	
	// Add each Statuspage component as a list item (div element with sub elements)
	addToList : function(data) {
		// Build the html elements	
		spanId = data.id + '_dot';		
		var item = '<div class="listitem">'
			+ '<span id="' + spanId + '"></span>' // Component status dot
			+ '<span class="component-heading">' + data.name + '</span>'
			+ '<span class="component-body"><strong>Status: </strong>' + statusPage.capitalise(data.status) + '</span>'
			+ '<span class="component-body"><strong>Updated: </strong>'
			+ statusPage.fixDate(data.updated_at) + '</span>' 
			+ '</div>';
		$("#statuslist").append(item);
		$("#" + spanId).addClass("component-dot " + data.status);
	},
	
	// When the title is clicked, show or hide the list
	toggleList : function() {
		if($("#statuscontainer").hasClass('active')) {
			$("#statuscontainer, #statusheader").removeClass('active');
			$(".transparentback").remove();
		} else {
			$('body').append('<div class="transparentback"></div>');
			$("#statuscontainer, #statusheader").addClass('active');
			
			
			$('body').on('click','.transparentback', function() {
				$(".transparentback").remove();
				$("#statuscontainer, #statusheader").removeClass('active');
			}); 
		
		}
	},
	
	// Change provided_status to Provided Status
	capitalise : function(status) {
		status = status.toLowerCase().split('_');
		for (var i = 0; i < status.length; i++) {
			status[i] = status[i].charAt(0).toUpperCase() + status[i].slice(1);
		}
		return status.join(' ');
	},
	
	// Time is provided as 2017-11-03T16:06:19.424+11:00, which sucks in Javascript!
	// (Yeah, I could fix it in PHP, but meh...)
	fixDate : function(datetime) {
		datetime = datetime.split('T');
		
		// Change 2017-11-03 to 11/03/2017
		date = datetime[0];
		date = date.split('-');
		date.reverse();
		date = date.join("/");
		
		// Change time from 16:06:19.424+11:00 to 16:06
		time = datetime[1];
		time = time.split('+');
		time = time[0];
		time = time.split('.');
		time = time[0];
		
		datetime = date + " @ " + time;
		return datetime;
	},
	
	// Incident Handling
	activeIncidents : function(incidents) {
		var inc_count = incidents.length;
		console.log("Currently " + inc_count + " active incidents");
		if (inc_count = 1) {
			$("#listheading").text("There is " + inc_count + " active incident!").addClass("incident");
		} 
		else if (inc_count > 1) {
			$("#listheading").text("There are " + inc_count + " active incidents!").addClass("incident");
		}
	}
	
	
}