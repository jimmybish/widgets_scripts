var jsonString =
    '{' +
	'"Hobart": {"Latitude": -42.8822927,"Longitude": 147.33007880000002,"Address": "Hobart GPO - 9 Elizabeth St, Hobart"},' + 
	'"Burnie": {"Latitude": -41.0530873,"Longitude": 145.9067139,"Address": "Burnie Post Shop - 87/91 Wilson St, Burnie"},' + 
	'"Launceston": {"Latitude": -41.43536779999999,"Longitude": 147.13772100000006,"Address": "Launceston GPO - 68 Cameron St, Launceston"}' +
	'}';

var depotArray = JSON.parse(jsonString);

$(document).ready(function () {

	google.maps.event.addDomListener(window, 'load', autocomplete());	
	
    function buildList(depotArray) {
		$('#depotList').empty();
		var distanceObj = [],
        i = 0;

		// A is the depot location, b is the coordinates from depotArray
		// Get value of calcDistance by running the haversineDistance function, passing the values from the invisible
		// DOM elements that hold the Lat and Long values created by the search box.
		// Then pass that distance value to the regionCalc function to get the costing value (Metro, Regional, Rural).
		// Finally, build the distanceObj array with the distance, location and region values, ready to sort and build in the DOM.
		$.each(depotArray, function (a, b) {
			var calcDistance = haversineDistance($('#addLat').val(), $('#addLng').val(), b.Latitude, b.Longitude);
			var calcRate = regionCalc(calcDistance);
			distanceObj[i] = { distance: calcDistance, location: a, region: calcRate, depAddress: b.Address };
			++i;
		});

		// Sort the distanceObj array so the shortest distance is at the top
		// https://www.w3schools.com/jsref/jsref_sort.asp
		distanceObj.sort(function(a, b) {
			return parseInt(a.distance) - parseInt(b.distance)
		});
	
		// Add each entry to the DOM as a list item.
		// A is the distanceObj index, b holds the values
		$.each(distanceObj, function(a, b) {
			$('#depotList').append('<tr><td><span class="bold">' + b.location + '</span><br><span class="depoAddr">' + b.depAddress + '</span></td><td><span class="bold">' + b.distance + 'km</span></td><td><span class="bold">' + b.region + '</span></td>');
		});
	};

	// Fancy maths formula that calculates the distance between points on a globe (in KM)
	// Google the Haversine formula for detailed info
	function haversineDistance(addrLat, addrLong, depLat, depLong) {
		function toRad(x) {
			return x * Math.PI / 180;
		}
		
		var R = 6371; // Earth's radius in KM
		
		var x1 = depLat - addrLat;
		var dLat = toRad(x1);
		var x2 = depLong - addrLong;
		var dLon = toRad(x2)
		var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
			Math.cos(toRad(addrLat)) * Math.cos(toRad(depLat)) *
			Math.sin(dLon / 2) * Math.sin(dLon / 2);
		var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
		var d = R * c;
		
		return Math.round(d);
	}
		
	// Drives the Google Maps autocomplete dropdown
	function autocomplete() {
		var input = document.getElementById('searchTextField');
		var options = {
			types:  ['geocode'],
			componentRestrictions: {country: 'Aus'}
		};
		var autocomplete = new google.maps.places.Autocomplete(input, options);
		
		google.maps.event.addListener(autocomplete, 'place_changed', function () {
            var place = autocomplete.getPlace();
			$('#addLat').val(place.geometry.location.lat());
			$('#addLng').val(place.geometry.location.lng());
			// console.log('Lat = ' + $('#addLat').val() + ', lng = ' + $('#addLng').val());
			buildList(depotArray);
		});
	};
	
	// Works out the region rate/schedule
	function regionCalc(distance){
		var region;
		if (distance <= 50) { region = "Metro"} else
		if (distance <= 150) { region = "Regional"} 
		else {region = "Rural"};
		return region;
	};
});