angular.module('core.team', [])
	.service('Team', ['$routeParams', 'Request', 'User', function ($routeParams, Request, User) {

		var Team = this;


		//gets full listing of teams
		Team.getTeams = function () {
			return Request.get("/team/teams", {}).then(function success(response) {
				return response.data.teams;
			}, function failure(response) {
				return response.data.message;
			});
		};

		//Gets information about the team of the logged in user.
		Team.getTeam = function() {
			return Request.get("/team/team", {}).then(function success(response) {
				if (response.data.success) {
					return response.data.team;
				} else {
					return response.data.message;
				}

			}, function failure(response) {
				return response.data.message;
			});
		};

		/**
		 * calls the loadCreateTeam function to connect to the api and add the team.
		 * broadcasts the results to the controller to update the view.
		 * @param {String} teamName holds a team name
		 * @param {int} memberCount
		 * @param {String} code The ocde used for joining the team
		 * @param {boolean} drive
		 * @param {boolean} blind
		 * @param {boolean} deaf
		 * @param {boolean} mobility
		 */
		Team.createTeam = function (teamName, memberCount, code, drive, blind, deaf, mobility) {
			var pushObj = {
				"Name": teamName,
				"memberCount": memberCount,
				'join_code': code,
				"can_drive": drive,
				"visual": blind,
				"hearing": deaf,
				"mobility": mobility
			};

			return Request.post('/team/create', pushObj)
				.then(function success(response) {
					//added the new team
					if (response.data.success == true) {
						User.setTeam(response.data.team_id, response.data.name);
						return {team_id: response.data.team_id, name: response.data.name};
					} else {
						console.log(response);
						return {message: response.data.message};
					}
				}, function failure(response) {
					console.log(response);
					return {message: response.data.message};
				});
		};

		/**
		 * when called is given a team object of which contains a team ID and name which is used to
		 * query the database and tell it that a specific user wants to join the given team.
		 * @param  {int} teamId The id of the team wanting to be joined
		 * @param {string} teamName The name of the team which will be joined.
		 * @param {string} joinCode The code specified to join the team
		 */
		Team.joinTeam = function (teamId, teamName, joinCode) {
			return Request.post('/team/join', {team_id: teamId, event_id: User.getEventId(), join_code: joinCode})
				.then(function success(response) {
					console.log(response);
					User.setTeam(teamId, teamName);
					return {
						success: true,
						message: "Successfully joined the team " + teamName + ". Welcome aboard!"
					};
				}, function failure(response) {
					//added the new team
					console.log(response);
					return response.data;
				})
		};

		Team.leaveTeam = function() {
			return Request.post('/team/leave').then(function success(response) {
				return response;
			}, function failure(response) {
				return response.data;
			});
		};

		Team.kickTeammate = function(teammateId) {
			return Request.post('/team/kick', {teammate_id: parseInt(teammateId)})
				.then(function success(response) {
					return response;
				}, function failure(response) {
					return response.data;
				});
		};

		/**
		 * Checks to see if the current teamName is already being used.
		 * @param  {String}        teamName contains a team name
		 * @return {boolean|String}           If team name is not in use returns true.
		 *                                        If team name is in use returns false.
		 */
		Team.checkAvailability = function (teamName) {
			var eventId = User.getEventId();
			if (eventId === null) {
				return "Sign up for an event before creating a team.";
			}

			return Request.get('/team/exists/' + teamName)
				.then(function success(response) {
					if (response.data.success == true) {
						return response.data.available;
					} else {
						return "Event does not exist";
					}
				}, function failure(response) {
					console.log(response.data.message);
					return "Event does not exist";
				});
		};

	}]);