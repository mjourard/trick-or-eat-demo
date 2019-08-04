function LogoutController($location, $mdToast, User, LOCATION_PATHS) {
    console.log("Inside the LogoutCtrl");

    User.logout();

    //FIXME: cannot scroll after $mdToast for logging out is shown.
    /*
     $mdToast.show(
     $mdToast.simple()
     .textContent('Logged Out')
     .position('top')
     .hideDelay(3000)
     );
     */

    //redirects to the home page after it completes
    $location.path(LOCATION_PATHS.home);

}

angular.module('logout').component('logout', {
    templateUrl: 'logout/logout.template.html',
    controller: ['$location', '$mdToast', 'User', 'LOCATION_PATHS', LogoutController]
});
