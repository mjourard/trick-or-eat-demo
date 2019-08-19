angular.module('core.event', [])
	.service('Event', ['Request', 'User', function (Request, User) {
		var events = null;

		/**
		 * gets the list of events available
		 * @return {Array} list of events. Each event is of the format: {event_id: <int>, event_name: "event_name"}
		 */
		this.getEvents = function (userRegion) {
			return Request.get("/events/" + userRegion, {})
				.then(function success(response) {
					if (response.data.success == true) {
						return response.data.events;
					} else {
						return null;
					}
				}, function failure(response) {
					console.log(response.data.message);
					return null;
				});
		};

		/**
		 * if called calls the loadRegisterEvent function to register a user in a given event by the ID
		 * also sets up their information regarding event participation.
		 * @param  {int} eventId  holds an event ID and event name
		 * @param  {boolean} driver   if true user has, if false user does not have.
		 * @param  {boolean} blind    if true user has, if false user does not have.
		 * @param  {boolean} mobility if true user has, if false user does not have.
		 * @param  {boolean} deaf     if true user has, if false user does not have.
		 */
		this.register = function (eventId, driver, blind, mobility, deaf) {
			return Request.post("/events/register", {
				"event_id": eventId,
				"can_drive": driver,
				"visual": blind,
				"mobility": mobility,
				"hearing": deaf
			})
				.then(function success(response) {
					if (response.data.success == true) {
						User.setEvent(response.data.event_id, response.data.event_name);
					}
					return response.data;
				}, function failure(response) {
					return response.data;
				});
		};
	}]);