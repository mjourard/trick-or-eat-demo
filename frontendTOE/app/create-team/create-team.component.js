function CreateTeamController($location, $mdDialog, $scope, $routeParams, Team, LOCATION_PATHS) {
    console.log("Inside the CreateTeamCtrl");

    var generateCode = function(len) {
        var str = "";
        for (var i = 0; i < len; i++) {
            str += Math.round(Math.random() * 10);
        }
        return str;
    };

    var cTeam = this;
    cTeam.eventName = $routeParams.location;
    cTeam.blind = false;
    cTeam.mobility = false;
    cTeam.deaf = false;
    cTeam.drive = false;
    cTeam.codeLength = 3;
	cTeam.code = generateCode(cTeam.codeLength);

	$scope.none = false;

	$scope.$watch('none', function() {
		cTeam.blind = false;
		cTeam.mobility = false;
		cTeam.deaf = false;
	});

    var showDialog = function(title, textContent, label, finalFunction) {
		finalFunction = finalFunction || function(){};
        $mdDialog.show(
            $mdDialog.alert()
                .clickOutsideToClose(false)
                .title(title)
                .textContent(textContent)
                .ariaLabel(label)
                .ok('Okay')
        ).finally(finalFunction);
    };

    var clearTeamName = function() {
		$scope.teamName = "";
	};

	//listener to the combination-lock component for when its code updates
	cTeam.updateCode = function(value, index) {
		var str = cTeam.code;
		index = parseInt(index);
		if (index > str.length - 1 || index < 0) {
			alert('busted index: "' + index + '"');
			return;
		}
		cTeam.code = str.substr(0, index) + value + str.substr(index+1);
	};


    /**
     * trys to add the team into the current set of teams
     * if fails updates teams and tells the user the name has already been used
     * if successful adds the team and updates the list of teams
     * @param  {String} teamName Holds the value of a given team name
     * @param  {int} memberCount The number of people the team is going to have on it.
     * @return {boolean}          if function returns it returns false meaning the function has failed.
     */
    cTeam.createTeam = function (teamName, memberCount) {

        console.log(teamName);
        console.log(memberCount);
        //checks to make sure there is an actual team name entered
        if (teamName == null || teamName.match(/[^\s]+/) == null) {
            showDialog('Create Team', 'Please fill in a team name.', 'Alert: Fill in team name');
			clearTeamName();
            return false;
        }

        if (memberCount == null) {
            showDialog('Create Team', 'Please select the number of people on your team.', 'Alert: Select Number of People');
            return false;
        }

		//TODO: remove this and make memberCount get passed in as an integer from the template...yuck
		memberCount = parseInt(memberCount);

		if (memberCount < 1 || memberCount > 5) {
			return false;
		}

		if (!Team.checkAvailability(teamName)) {
			showDialog('Create Team', "The team name '" + teamName + "' has already been used for this event.", 'Alert: Team name already used');
			return false;
		}

        Team.createTeam(teamName, memberCount, cTeam.code, cTeam.drive, cTeam.blind, cTeam.deaf, cTeam.mobility).then(function(response) {
			if (response.message) {
				showDialog('Create Team', response.message, 'Alert: ' + response.message)
			} else {
				showDialog('Create Team', 'You have successfully created ' + response.name, 'Alert: Team Creation Successful', function(){	$location.path(LOCATION_PATHS.home);});
			}
		});
    };

    /**
     * if called informs the user that their team has been created and redirects the user to the team page.
     * @param  {??} event   default value needed for $on
     * @param  {Object} data contains no information
     */
    $scope.$on('createTeam:success', function (event, data) {
        $mdDialog.show(
            $mdDialog.alert()
                .clickOutsideToClose(false)
                .title('Create Team')
                .textContent('Your team has been created.')
                .ariaLabel('Alert: Team has been created')
                .ok('Close')
        );
        $location.path('/event/' + cTeam.eventName + '/team/routes');
    });

    /**
     * if called informs the user that their team name has already been used.
     * @param  {??} event   default value needed for $on
     * @param  {Object} data contains the team name that counld not be created.
     */
    $scope.$on('createTeam:failure', function (event, data) {
        $mdDialog.show(
            $mdDialog.alert()
                .clickOutsideToClose(false)
                .title('Create Team')
                .textContent('An error occured when creating the team.')
                .ariaLabel('Alert: Team name already used')
                .ok('Close')
        );
    });

}

angular.module('createTeam').component('createTeam', {
    templateUrl: 'create-team/create-team.template.html',
    controller: ['$location', '$mdDialog', '$scope', '$routeParams', 'Team', 'LOCATION_PATHS', CreateTeamController]
});