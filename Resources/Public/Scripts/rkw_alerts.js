var RkwAlerts = RkwAlerts || {};

RkwAlerts.handle = (function ($) {

	var $alertContainer;

	var _init = function(){
		$(document).ready(_onReady);
	};

	var _onReady = function(){
		$alertContainer = $('#rkw-alerts-container');
		_getContent();

	};

	var _getContent = function(e){

        var $url = '/?type=1446640418&v=' + jQuery.now();
        if ($alertContainer.attr('data-url')) {

            var url = $alertContainer.attr('data-url');
            if ($alertContainer.attr('data-url')
                    .indexOf('?') === -1) {
                url += '?v=' + jQuery.now();
            } else {
                url += '&v=' + jQuery.now();
            }
        }

        jQuery.ajax({
            url: url,
            data: {
                'tx_rkwalerts_rkwalerts[controller]': 'Alerts',
                'tx_rkwalerts_rkwalerts[action]': 'newAjax'
            },
            success: function (json) {

                try {
                    if (json) {
                        for (var property in json) {

                            if (property === 'html') {

                                var htmlObject = json[property];
                                for (parent in htmlObject) {

                                    targetObject = jQuery('#' + parent);
                                    if (targetObject.length) {
                                        for (var method in htmlObject[parent]) {
                                            if (method === 'append') {
                                                jQuery(htmlObject[parent][method]).appendTo(targetObject);
                                            } else
                                            if (method === 'prepend') {
                                                jQuery(htmlObject[parent][method]).prependTo(targetObject);
                                            } else
                                            if (method === 'replace') {
                                                targetObject.empty();
                                                jQuery(htmlObject[parent][method]).prependTo(targetObject);

                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch (error) {}
            },
            dataType: 'json'
        });

	};

	/**
	 * Public interface
	 * @public
	 */
	return {
		init: _init,
	}

})(jQuery);

RkwAlerts.handle.init();
