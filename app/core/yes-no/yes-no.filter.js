/**
 * Created by LENOVO-T430 on 7/19/2017.
 */
angular.module('core').
	filter('yesNo', function() {
	return function(input) {
		return input ? 'yes' : 'no';
	};
});