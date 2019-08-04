/**
 * Created by LENOVO-T430 on 12/16/2016.
 */
'use strict';

describe('HomeCtrl', function() {

    beforeEach(module('phonecatApp'));

    it('should create a `phones` model with 3 phones', inject(function($controller) {
        var scope = {};
        var ctrl = $controller('PhoneListController', {$scope: scope});

        expect(scope.phones.length).toBe(3);
    }));

});
