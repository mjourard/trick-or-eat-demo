function PageFooterController($mdDialog, $mdSidenav, $scope, LOCATION_PATHS) {
    var foot = this;
    $scope.locationPaths = LOCATION_PATHS;
    $scope.info = {
        charitable_reg_num: CHARITABLE_REG_NUM,
        contact_addr_1: CONTACT_ADDR_1,
        contact_addr_2: CONTACT_ADDR_2,
        contact_phone: CONTACT_PHONE,
        contact_email: CONTACT_EMAIL
    };

    foot.links = [
        {href: 'https://mealexchange.com/', display: 'Meal Exchange Home'},
        {href: 'https://mealexchange.com/team-info', display: 'About MX'},
        {href: 'https://mealexchange.com/get-involved.html', display: 'Get Involved'},
        {href: 'https://mealexchange.com/resource-page.html', display: 'Resources'},
        {href: 'https://mealexchange.com/info-bites.html', display: 'Blog'}
    ];

}

angular.module('pageFooter').component('pageFooter', {
    templateUrl: 'page-footer/page-footer.template.html',
    controller: ['$mdDialog', '$mdSidenav', '$scope', 'LOCATION_PATHS', PageFooterController]
});
