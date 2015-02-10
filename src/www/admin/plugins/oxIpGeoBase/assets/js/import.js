if (typeof window['console'] === 'undefined') {
	window.console = {};
	window.console.log = function() {};
	window.console.info = function() {};
	window.console.error = function() {};
	window.console.info = function() {};
}

if (typeof window['jQuery'] !== 'undefined') (function($, undef) {
	var STATUS = 'run';

	var ns = {};
	var timer;
	var lock = false;
	var count = 0;

	ns.onsuccess = function($row, data) {
		$row.find('[data-role=status]').text(data['status']);
		$row.find('[data-role=percent]').text(data['percent'] + '%');
		$row.find('[data-role=task.created]').text(data['created']);
		$row.find('[data-role=task.opened]').text(data['opened']);
		$row.find('[data-role=task.closed]').text(data['closed']);
	};

	ns.oncomplete = function($row, data) {
		$row.removeClass().addClass(data['status']);
		$row.find('[data-role=percent]').hide();
	};

	ns.process = function($row) {
		var index = $row.attr('data-bind-for');

		if (lock) {
			window.console.error('OxIpGeoBase: ajax-process already running');
			return;
		}
		lock = true;
		count++;

		if (timer) {
			clearTimeout(timer);
		}

		timer = setTimeout(function () {
			window.console.info('OxIpGeoBase: next [' + count +  '] ajax-process started for job ' + index + ' on ' + (new Date()));

			$.ajax({
				'type': 'POST',
				'url': '/www/admin/plugins/oxIpGeoBase/geo-import-ajax.php',
				'dataType': 'json',
				'data': {
					'id': index,
					'action': 'progress'
				},
				'cache': false,
				'success': function(response) {
					lock = false;
					try {
						ns.onsuccess($row, response);
						if (STATUS === response['status']) {
							ns.process($row);
						} else {
							ns.oncomplete($row, response);
						}
					} catch (e) {
						window.console.error('OxIpGeoBase: error detected, ' + e.message);
					}
				},
				'error': function(response, status) {
					window.console.error('OxIpGeoBase: unexpected error detected, ' + response.statusText);
					lock = false;
				}
			});
		}, 1000);

		window.console.info('OxIpGeoBase: next [' + count +  '] ajax-process scheduled for job ' + index + ' on ' + (new Date()));
	};

	$(function() {
		$('.status_' + STATUS).each(function(i, element) {
			var $row = $(element).parents('[data-role=task]');

			if (!lock) {
				window.console.info('OxIpGeoBase: initial ajax-process started for job ' + $row.attr('data-bind-for'));
				ns.process($row);
			}
		});

		$('.button-cancel').click(function() {
			var $button = $(this);

			return window.confirm(
				$button.attr('data-confirm-text')
			);
		});
	});
})(window['jQuery']);