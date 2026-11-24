(function () {
	'use strict';

	var form = document.querySelector('.wp-log-watch__filters');

	if (!form) {
		return;
	}

	Array.prototype.forEach.call(form.querySelectorAll('select'), function (select) {
		select.addEventListener('change', function () {
			form.submit();
		});
	});
})();
