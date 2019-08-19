/**
 * Converts a datetime string that follows the ISO 8601 standard to displaying the time in a 12-hour format
 *
 * Created by LENOVO-T430 on 10/22/2017.
 */
angular.module('core').
filter('timeDisplay', function() {
	return function(input) {
		var dateFormat;
		dateFormat = /^\d{1,4}-\d{1,2}-\d{1,2}(\s*((([0-1][0-9]|2[0-3]):([0-5][0-9])):[0-5][0-9]))/;
		if (dateFormat.test(input) !== true) {
			return input;
		}
		var matches = input.match(dateFormat);
		if (matches.length < 6) {
			console.log(matches);
			return input;
		}
		var hours;
		if ((hours = parseInt(matches[4])) > 11) {
			hours = hours === 12 ? hours : hours - 12;
			return hours + ":" + matches[5] + " PM";
		}

		return matches[3] + " AM";
	};
});