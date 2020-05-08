function TeamController($location, $mdDialog, Team, LOCATION_PATHS) {
	var team = this;
	team.teamName = "";
	team.teammates = [];
	team.teamId = null;
	team.joinCode = "";
	team.imagePath = LOCATION_PATHS.images + '/trick-or-treat-placeholder.jpg';

	team.kickTeammate = function (teammateId, teamId, teammateFirstName, teammateLastName) {
		confirmDialog('Kick Teammate', "Are you sure you want to kick " + teammateFirstName + ' ' + teammateLastName + '?', 'Kick Teammate', 'Kick', function() {
			Team.kickTeammate(teammateId, teamId).then(function (response) {
				if (response.data.success) {
					team.refreshTeam()
				} else {
					showDialog('Error', response.data.message, 'error');
				}
			});
		});

	};

	team.leaveTeam = function() {
		confirmDialog('Leave Team', "Are you sure you want to leave the team?", 'Leave Team', 'Leave', function() {
			Team.leaveTeam().then(function (response) {
				if (response.data.success) {
					$location.path(LOCATION_PATHS.home);
				} else {
					showDialog('Error', response.data.message, 'error');
				}
			});
		});
	};


	team.refreshTeam = function () {
		Team.getTeam().then(function (response) {
			if (response.hasOwnProperty('name') && response.name) {
				team.teamId = response.id;
				team.teamName = response.name;
				team.teammates = response.teammates;
				team.joinCode = response.code;
			}
		});
	};

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

	var confirmDialog = function(title, textContent, label, confirmText, confirmFunction) {
		$mdDialog.show(
			$mdDialog.confirm()
				.title(title)
				.textContent(textContent)
				.ariaLabel(label)
				.ok(confirmText)
				.cancel('Cancel')
		).then(confirmFunction);
	};

	team.refreshTeam();
}


angular.module('viewTeam').component('viewTeam', {
	templateUrl: 'view-team/view-team.template.html',
	controller: ['$location', '$mdDialog', 'Team', 'LOCATION_PATHS', TeamController]
});