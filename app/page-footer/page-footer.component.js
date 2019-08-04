function PageFooterController($mdDialog, $mdSidenav, $scope, LOCATION_PATHS) {
    var foot = this;
    $scope.locationPaths = LOCATION_PATHS;

    foot.links = [
        {href: 'http://mealexchange.com/', display: 'Meal Exchange Home'},
        {href: 'http://mealexchange.com/about-us.html', display: 'About MX'},
        {href: 'http://mealexchange.com/get-involved.html', display: 'Get Involved'},
        {href: 'http://mealexchange.com/resource-page.html', display: 'Resources'},
        {href: 'http://mealexchange.com/info-bites.html', display: 'Blog'}
    ];

}

angular.module('pageFooter').component('pageFooter', {
    templateUrl: 'page-footer/page-footer.template.html',
    controller: ['$mdDialog', '$mdSidenav', '$scope', 'LOCATION_PATHS', PageFooterController]
});
