function JoinTeamController($location, $mdDialog, $routeParams, $scope, Team, User, LOCATION_PATHS) {
    console.log("Inside the JoinTeamCtrl");

    var join = this;
    var selectedTeam = null;

    join.currentRow = null;
    join.event = $routeParams.location;
    join.teamObj = [];
    join.displayOrderProp = 'name';
	join.code = '123';

    $scope.teamDisplayLimit = 5;
    $scope.showAllTeams = false;
	$scope.joinClicked = false;

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

    /**
     * gets a list of teams from the teams service and saves them for local use.
     */
    Team.getTeams().then(function(teams) {
		if (typeof(teams) == String) {
			showDialog('Teams Unavailable', teams, 'Alert: no teams');
		} else {
			join.teamObj = teams;
		}
	});

    /**
     * sets the currently selected row in a table to the selected team for local use.
     * @param {int} index denotes which row is currently selected
     * @param {Object} team  contains an ID and name associated with the given selected team.
     */
    join.setSelectedRow = function (index, team) {
        join.currentRow = index;
        selectedTeam = team;
    };

    /**
     * checks to make sure there is a team selected that the user can join and then attempts to join that team.
     * @return {boolean}       only returns false if there is no team selected.
     */
    join.joinTeam = function () {
        //TODO: disable the joinTeam button once clicked to prevent the user from double requesting to join the team. Could screw up the team member counts
		$scope.joinClicked = true;

        if (selectedTeam == null) {
			showDialog('Team Selection', 'Please select a team to join first', 'Alert: Select a team to join');
		} else {
			Team.joinTeam(selectedTeam.team_id, selectedTeam.name, join.code).then(function(response) {
				if (response.success) {
					showDialog('Team Selection', response.message, 'Alert: joined team', function() { $location.path(LOCATION_PATHS.home); });
				} else {
					showDialog('Team Selection', response.message, 'Alert: failed to join team');
				}
			});
		}
		$scope.joinClicked = false;
	};

	//listener to the combination-lock component for when its code updates
	join.updateCode = function(value, index) {
		var str = join.code;
		index = parseInt(index);
		if (index > str.length - 1 || index < 0) {
			alert('busted index: "' + index + '"');
			return;
		}
		join.code = str.substr(0, index) + value + str.substr(index+1);
	};

    /**
     * if joining a team succeeds then a alert is given to the user indicating that they have joined a team
     * then directs the user to the team page.
     * @param  {??} event   default value needed for $on
     * @param  {Object} data contains no information
     */
    $scope.$on('joinTeam:success', function (event, data) {
        $mdDialog.show(
            $mdDialog.alert()
                .clickOutsideToClose(false)
                .title('Joined Team')
                .textContent('You have joined the team.')
                .ariaLabel('Alert: You have joined the team')
                .ok('Close')
        );
        $location.path('/event/' + join.event + '/team/routes');
    });

    $scope.$on("joinTeam:failure", function (event, data) {
        $mdDialog.show(
            $mdDialog.alert()
                .clickOutsideToClose(false)
                .title('Error')
                .textContent('An error occurred when you attempted to join the team.')
                .ariaLabel('Alert: Failure to join the team')
                .ok('Close')
        );
        console.log('event:');
        console.log(event);
        console.log("data:");
        console.log(data);
    });

    $scope.setTeamDisplayCount = function () {
        if ($scope.showAllTeams === false) {
            $scope.teamDisplayLimit = 5;
        } else {
            $scope.teamDisplayLimit = join.teamObj.length;
        }
    };

    $scope.$watch('showAllTeams', $scope.setTeamDisplayCount);

}

angular.module('joinTeam').component('joinTeam', {
    templateUrl: 'join-team/join-team.template.html',
    controller: ['$location', '$mdDialog', '$routeParams', '$scope', 'Team', 'User', 'LOCATION_PATHS', JoinTeamController]
});