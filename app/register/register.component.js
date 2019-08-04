function RegisterController($location, $mdDialog, $scope, Event, User, LOCATION_PATHS) {

	var reg = this;
	console.log("in the register controller");
	//default values for radio fields
	reg.blind = false;
	reg.mobility = false;
	reg.deaf = false;
	reg.drive = false;
	reg.terms = false;
	reg.event = null;
	$scope.none = false;

	$scope.$watch('none', function() {
		reg.blind = false;
		reg.mobility = false;
		reg.deaf = false;
	});


	reg.countryName = User.getCountryName();
	reg.region = User.getRegion();
	Event.getEvents(reg.region.region_id).then(function(events) {
		reg.events = events;
	});
	reg.regionName = reg.region.region_name;

	/**
	 * checks to make sure all fields have been filled in if needed. Makes sure a event as been selected,
	 * make sure the terms have been agreed to. If all is well then the user attempts to register
	 * for an event given the set of information they entered.
	 * @return {boolean} only retrns false if anything bad happens.
	 */
	reg.registerEvent = function () {

		//Makes sure a drive option has been selected
		if (reg.drive === null) {
			showDialog('Missing Field', 'You must select a drive option of yes/no.', 'Alert: Missing drive option');
			return false;
		}

		//Makes sure an event has been selected
		if (reg.event === null) {
			showDialog('Missing Field', 'You have not selected an event.', 'Alert: Missing event selection');
			return false;
		}

		//Makes sure the terms have been agreed to.
		if (reg.terms == false) {
			showDialog('Terms and Conditions', 'You must agree to the terms to register for the event.', 'Alert: Must agree to the terms');
			return false;
		}

		Event.register(reg.event, reg.drive, reg.blind, reg.mobility, reg.deaf).then(function(response) {
			if (response.success) {
				showDialog('Success', 'You are now registered for the event', 'Alert: registration successful', function(){$location.path(LOCATION_PATHS.home)});
			} else {
				//TODO: make response.messgae stand out more in the error dialog
				showDialog('Error', 'There was an error registering for the event: ' + response.message, 'Alert: registration failed');
			}
		});
	};

	var showDialog = function(title, text, label, finalFunction) {
		finalFunction = finalFunction || function(){};

		$mdDialog.show(
			$mdDialog.alert()
				.clickOutsideToClose(false)
				.title(title)
				.textContent(text)
				.ariaLabel(label)
				.ok('Okay')
		).finally(finalFunction);
	};

	var event = User.getEvent();
	if (event !== null && event.event_id !== null) {
		showDialog('Already Registered', "You're already registered for an event. Jumping to team selection.", 'Alert: already registered', function(){$location.path('/event/' + event.event_id + '/team')});
	}

}

angular.module('register').component('register', {
	templateUrl: 'register/register.template.html',
	controller: ['$location', '$mdDialog', '$scope', 'Event', 'User', 'LOCATION_PATHS', RegisterController]
});
