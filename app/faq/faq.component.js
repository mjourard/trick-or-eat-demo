function FAQController() {
    console.log("Inside the FaqCtrl");
    var faq = this;
    //Public Variable Declarations.

    //Public Function Declarations.
    faq.init = function () {
        //Basic init function.
    };

    //Private Variable Declarations.

    //Private Function Declarations.

}

angular.module('faq').component('FaqCtrl', {
    templateUrl: 'faq/faq.template.html',
    controller: FAQController
});
