// FILENAME: userService.js
// AUTHOR(S): Devin Dagg, Brad Gethke
// DATE: 2016-08-25
// DESCRIPTION: The Service for handling all account information and state of the current user
angular.module('core.user')
    .factory('User', ['$cookies', function ($cookies) {
        this.create = function (email, userId, fName, lName, countryId, countryName, regionId, regionName, teamId, teamName, eventId, eventName, roles) {
            this.userId = userId;
            this.email = email;
            this.fName = fName;
            this.lName = lName;
            this.country = {
                country_id: countryId,
                country_name: countryName
            };
            this.region = {
                region_id: regionId,
                region_name: regionName
            };
            this.team = {
                team_id: teamId,
                team_name: teamName
            };
            this.event = {
                event_id: eventId,
                event_name: eventName
            };
            this.roles = roles;
        };

        this.destroy = function () {
            this.userId = null;
            this.email = null;
            this.fName = null;
            this.lName = null;
            this.country = null;
            this.region = null;
            this.team = null;
            this.event = null;
            this.roles = null;
            
        };

        this.getUser = function () {
            return this;
        };

        this.getUserId = function () {
            if (this.userId === undefined) {
                return null;
            }
            return this.userId;
        };

        this.getUserRoles = function () {
            return this.roles;
        };

        /**
         * Checks to see if the Users first name has been loaded and if not,
         * returns null
         * @return {string|null} the first name of the Current User
         */
        this.getfName = function () {
            return this.fName;
        };

        /**
         * Checks to see if the Users last name has been loaded and if not,
         * returns null
         * @return {string|null} the last name of the Current User
         */
        this.getlName = function () {
            return this.lName;
        };

        /**
         * Checks to see if the Users email has been loaded and if not,
         * returns null
         * @return {string|null} the email of the Current User
         */
        this.getEmail = function () {
            return this.email;
        };

        /**
         * Checks to see if the Users country has been loaded and if not,
         * returns null
         * @return {Object} the country of the Current User containing country_id, and country_name
         */
        this.getCountry = function () {
            return this.country;
        };

        this.getCountryId = function() {
            if (this.country) {
                return this.country.country_id;
            }
            return null;
        };

        this.getCountryName = function() {
			if (this.country) {
				return this.country.country_name;
			}
			return null;
		};

        /**
         * Checks to see if the Users region has been loaded and if not,
         * returns null
         * @return {Object} the region of the Current User containing region_id, and region_name
         */
        this.getRegion = function () {
            return this.region;
        };

        this.getRegionId = function() {
            if (this.region) {
                return this.region.region_id;
            }
            return null;
        };

        this.getRegionName = function() {
            if (this.region) {
				return this.region.region_name;
			}
			return null;
        };

        /**
         * returns a event object with ID and name inside of it if it exits in the this.
         * @return {Object} format {"event_id":[id here],"event_name":[event title here]}
         */
        this.getEvent = function () {
            return this.event;
        };

        this.getEventId = function() {
            if (this.event) {
                return this.event.event_id;
            }
            return null;
        };

        /**
         * Gets and returns the current team info of ID and name if the object team exits in this.
         * * @return {Object} format {"team_id":[id here],"name":[team name here]}
         */
        this.getTeam = function () {
            return this.team;
        };

        this.getTeamName = function() {
			if (this.team) {
				return this.team.team_name;
			}

			return null;
		};

		this.getTeamId = function() {
			if (this.team) {
				return this.team.team_id;
			}

			return null;
		};

        this.logout = function () {
            $cookies.remove('authToken');
            this.destroy();
        };

        this.setTeam = function (teamId, teamName) {
            this.team = {
                team_id : teamId,
                team_name : teamName
            };
        };

        /**
         *
         * @param eventId {int} The id of the event
         * @param eventName {String} The name of the event
         */
        this.setEvent = function (eventId, eventName) {
            this.event = {
                event_id: eventId,
                event_name: eventName
            };
        };

        //
        //SETTERS
        //

        /**
         * Sets the first name of the current user in the Service
         * @param {string} fName - The new first name of the user
         */
        this.setfName = function (fName) {
            this.fName = fName;
        };

        /**
         * Sets the last name of the current user in the Service
         * @param {string} lName - The new last name of the user
         */
        this.setlName = function (lName) {
            this.lName = lName;
        };

        /**
         * Sets the email of the current user in the Service
         * @param {string} email - The new email of the user
         */
        this.setEmail = function (email) {
            this.email = email;
        };

        //FIXME: need to update once getting country_id as an int
        /**
         * Sets the country of the current user in the Service
         * @param {int} countryId - The new country of the user
         * @param {string} countryName
         */
        this.setCountry = function (countryId, countryName) {
            this.country = {
                countryId: countryId,
                countryName: countryName
            };
        };

        /**
         * Sets the region of the current user in the Service
         * @param {int} regionId - The new region of the user
         * @param {string} regionName - The name of the region of the user
         */
        this.setRegion = function (regionId, regionName) {
            this.region = {
                regionId: regionId,
                regionName: regionName
            };
        };

        //
        //CHECKERS
        //

        /**
         * Checks to see if there is a token to use for user info.
         * @return {boolean} True if the user is logged in, else false
         */
        this.loggedIn = function () {
            return $cookies.get('authToken');
        };

        return this;
    }]);
