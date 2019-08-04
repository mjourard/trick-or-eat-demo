// FILENAME: locationService.js
// AUTHOR(S): Devin Dagg, Brad Gethke
// DATE: 2016-08-25
// DESCRIPTION: Service for handling all information about the country and regions drop down menus
angular.module('core.location')
    .factory('Location', ['$location', '$rootScope', 'Request', function ($location, $rootScope, Request) {
        /**
         * Hashmap of country names mapped to their ids. Example object:
         * {
         *      Canada: 1,
         *      USA: 2
         * }
         * @type {{}}
         */
        var countryIds = {};

        /**
         * Hashmap of the country ids mapped to their names. Example object:
         * {
         *      1: 'Canada',
         *      2: 'USA'
         * }
         * @type {{}}
         */
        var countryNames = {};

        /**
         * Hashmap of region names to their ids. First key is the country id that the region belongs to.
         * Example object:
         * {
         *      1: {
         *          Ontario: 1,
         *          Quebec: 2
         *      },
         *      2: {
         *          Pensyvalnia: 1,
         *          Washington: 2,
         *      }
         * }
         *
         * @type {{}}
         */
        var regionIds = {};

		/**
		 * Hashmap of regionIds to their coordinates. Key is the regionId, value is an object with structure:
		 * {
		 * 	lat: 122.134156,
		 * 	long: -13.123156
		 * }
		 * @type {{}}
		 */
		var regionCoords = {};

        /**
         * Hashmap of the regoin ids to their names. First key is the country id that the region belongs to.
         * Example object:
         * {
         *      1: {
         *          1: 'Ontario',
         *          2: 'Quebec'
         *      },
         *      2: {
         *          1: 'Pensylvania',
         *          2: 'Washington'
         *      }
         * }
         * @type {{}}
         */
        var regionNames = {};

        this.getCountryNames = function() {
            return countryNames;
        };

        this.getRegionNames = function() {
            return regionNames;
        };

        /**
         * Given a Country Name, return the Country's corresponding ID
         * @param  {string} countryName - the Name of the country you need the ID of
         * @return {int} - On success, returns the ID of the country searched, on failure returns -1
         */
        this.getCountryId = function (countryName) {
            if (!(countryName in countryIds)) {
                return -1;
            }
            return countryIds[countryName];
        };

        /**
         * Given a Country ID, return the Country's corresponding name
         * @param  {int} countryId - The ID of the country you need the name of
         * @return {string} - On success, returns the name of the country searched, on failure returns the empty string
         */
        this.getCountryName = function (countryId) {
            if (!(countryId in countryNames)) {
                return String.empty();
            }
            return countryNames[countryId];
        };

        /**
         * Given a Region Name and it's associated country ID return the region's ID
         * @param  {int} countryId - the country ID of the region you need the ID of
         * @param  {string} regionName - the name of the region you need the ID of
         * @return {int} - On success, returns the ID of the region searched, on failure returns -1
         */
        this.getRegionId = function (countryId, regionName) {
            if (!(countryId in regionIds)) {
                return -1;
            }

            if (!(regionName in regionIds[countryId])) {
                return -1;
            }

            return regionIds[countryId][regionName];
        };

        /**
         * Given a Region ID and it's associated country ID return the region's name
         * @param  {int} countryId - the country ID of the region you need the name of
         * @param  {int} regionId - the ID of the region you need the name of
         * @return {string} - On success, returns the Name of the region searched, on failure, returns the empty string
         */
        this.getRegionName = function (countryId, regionId) {
            if (!(countryId in regionIds)) {
                return String.empty();
            }

            if (!(regionId in regionIds[countryId])) {
                return String.empty();
            }

            return regionIds[countryId][regionId];
        };

		/**
		 * Returns the coordinates of the region you pass in an ID for. Returns null if the regionId does not exist
		 * @param regionId
		 * @returns {*}
		 */
		this.getRegionCoordinates = function(regionId) {
            if (!(regionId in regionCoords)) {
                return null;
            }

            return regionCoords[regionId];
        };



        /**
         * returns the countryIds hashmap
         * @return {Object} - The list of Countries with their names as the keys.
         */
        this.getCountryIds = function () {
            return countryIds;
        };

        /**
         * returns a list of ,
         * @param  {int} countryId - The ID of the country you need to regions of
         * @return {Object} - A JSON of Regions in specified country. Returns null if countryId doesn't exist.
         */
        this.getRegionIds = function (countryId) {
            if (!countryId in regionIds) {
                return null;
            }
            return regionIds[countryId];
        };



        /**
         * Loads the list of countries in from the database. On success it fills
         * the Country list. On failure, redirects to 403 page.
         * TODO: add in a logging feature for the error
         */
        var loadCountries = function () {
            Request.get("/countries", {})
                .then(function success(response) {

                    var body = response.data;

                    if (response.status == 200 && body.success == true && body.hasOwnProperty("countries")) {
                        body.countries.forEach(function(countryData) {
                            countryIds[countryData.country_name] = countryData.country_id;
                            countryNames[countryData.country_id] = countryData.country_name;
                            loadRegions(countryData.country_id);
                        })

                    } else {
						console.log('something went wrong');
						console.log(response);
					}
                }, function failure(response) {
                    //infoReset();
                    $location.path('/403');
                });
        };

        /**
         * Loads the list of regions for a particular country from the database.
         * On success adds the regions to the hashmaps.
         * On failure, redirects to 403 page.
         */
        var loadRegions = function (countryId) {
            regionIds[countryId] = {};
            regionNames[countryId] = {};
            Request.get("/regions/" + countryId, {})
                .then(function success(response) {
                    var body = response.data;
                    if (response.status == 200 && body.success == true && body.hasOwnProperty("regions")) {
                        var regions = body.regions;
                        regions.forEach(function(regionData) {
                            regionIds[countryId][regionData.region_name] = regionData.region_id;
                            regionNames[countryId][regionData.region_id] = regionData.region_name;
							regionCoords[regionData.region_id] = {
								lat: regionData.latitude,
								long: regionData.longitude
							};
                        });
                    }
                }, function failure(response) {
                    //infoReset();
                    $location.path('/403');
                })
        };

        loadCountries();

        return this;
    }]);