// Sharetronix Services namespace
var Services = function () {

	function invoke(args, onsuccess, onfail, context) {
		$.ajax({
			type:		args.type ? args.type : "POST",
			url:		siteurl + 'services/' + args.module + '/' + args.action,
			data:		args.data,
			success:	function(response) {
							if (context) context.data('status', 'enabled').removeClass('disabled');
							// An empty or unexpected answer, e.g. after a server error, is a failure.
							if (!response || !response.data) {
								onfail(failure(), context);
								return;
							}
							onsuccess(response.data, context);
						},
			error:		function(response) {
							if (context) context.data('status', 'enabled').removeClass('disabled');
							onfail(failure(), context) 
						}
		});
	}

	function failure() {
		return { status: 'ERROR', message: stxText('js_request_failed', 'The request failed. Please try again.'), html: '' };
	}

	function _init() {
		$('[data-role="services"]').live('click', function (e) {
			if ($(this).is('a')) e.preventDefault();
			if ($(this).data('status') != 'disabled') {
				serviceCallHandler($(this), e);
			}
		});
	}

	function serviceCallHandler(el, event) {
		var requiredNamespace = $(el).data('namespace');
		var namespace = requiredNamespace.charAt(0).toUpperCase() + requiredNamespace.slice(1);
		var action = $(el).data('action');
		var value = $(el).data('value');
		var exec = window[namespace][action];
		el.data('status', 'disabled').addClass('disabled');
		exec(el, value, event);
	}

    return {
    	invoke: invoke,
    	init: _init
    }
} ();

$(document).ready(function () {
	Services.init();
});