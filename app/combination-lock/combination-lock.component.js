/**
 * Created by LENOVO-T430 on 10/15/2017.
 */
function CombinationLockController($location, $mdDialog, $scope) {
	var lock = this;
	lock.digitsArray = {};

	// lock.digitsArray = Array.apply(null, new Array(lock.digits)).map(function(_, i) {return i;});
	lock.$onInit = function () {
		var temp = parseInt(lock.combo);
		var digitValues = new Array(lock.digits);
		var i;
		for (i = 0; i < lock.digits; i++) {
			lock.digitsArray['d' + i] = 0;
			digitValues[i] = temp % 10;
			temp = parseInt(temp / 10);
		}
		for (i = 0; i < lock.digits; i++) {
			lock.digitsArray['d' + i] = digitValues[lock.digits - i - 1];
		}
	};

	lock.increment = function (id) {
		lock.digitsArray[id]++;
		if (lock.digitsArray[id] > 9) {
			lock.digitsArray[id] = 0;
		}
		lock.onUpdate({value: lock.digitsArray[id], index: id.substr(1)});
	};

	lock.decrement = function(id) {
		lock.digitsArray[id]--;
		if (lock.digitsArray[id] < 0) {
			lock.digitsArray[id] = 9;
		}
		lock.onUpdate({value: lock.digitsArray[id], index: id.substr(1)});
	};
}

angular.module('combinationLock').component('combinationLock', {
	templateUrl: 'combination-lock/combination-lock.template.html',
	controller: ['$location', '$mdDialog', '$scope', CombinationLockController],
	bindings: {
		digits: '<',
		combo: '<',
		onUpdate: '&'
	}
});