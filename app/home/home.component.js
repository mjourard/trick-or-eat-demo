function HomeController($scope, $mdDialog, LOCATION_PATHS, Auth, User) {

    var home = this;
    home.userEvent = null;
    home.userTeam = null;
    $scope.locationPaths = LOCATION_PATHS;
    $scope.isAuthenticated = Auth.isAuthenticated;

    //TODO: The home controller is currently tightly coupled to the User service due to register returning a specific object. Loosen the coupling by making the getEventId function and returning a natively typed variable
    /**
     * checks to see if the user is registered for an event by checking the
     * return value and making sure it is not null
     * @return {boolean} true if the user is registered for an event, false if the user is not.
     */
    home.checkRegister = function () {
        home.userEvent = User.getEvent();
        return home.userEvent.event_id != null;
    };

    /**
     * checks to see if a user is on a team
     * @return {boolean} true if the user is on a team, false if the user is not on a team.
     */
    home.checkTeam = function () {
        home.userTeam = User.getTeam();
        return home.userTeam.team_id != null;
    };

    /**
     * displays the login screen for a user if called.
     */
    home.loginModal = function (event) {
        $mdDialog.show({
            controller: 'LoginCtrl',
            templateUrl: 'login/login.template.html',
            targetEvent: event,
            clickOutsideToClose: true,
            fullscreen: true
        });
    };
}

angular.module('home').component('home', {
    templateUrl: 'home/home.template.html',
    controller: ['$scope', '$mdDialog', 'LOCATION_PATHS', 'Auth', 'User', HomeController]
});