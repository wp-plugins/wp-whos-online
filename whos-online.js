jQuery(function($) {
	/*
	* Update author "last online" timestamps
	*/
	function getWhosonline(){
		toggleUpdates();
		var queryString = whosonline.ajaxUrl +'?action=whosonline_ajax_update&load_time=' + whosonline.whosonlineLoadTime + '&frontpage=' + whosonline.isFirstFrontPage;
		ajaxCheckAuthors = $.getJSON(queryString, function(response){
			if(typeof response.latestupdate != 'undefined') {
				whosonline.whosonlineLoadTime = response.latestupdate;
				for(var i = 0; i < response.authors.length; i++) {
					$('#whosonline-'+response.authors[i].user_id).
						text( response.authors[i].whosonline ).
						data('whosonline_timestamp', response.authors[i].whosonline_unix);
				}
			}
		});

		toggleUpdates();
		updateRecents();
	}

	function updateRecents(){
		var now = Math.round(new Date().getTime()/1000.0);

		var old = 600; // 10 minutes
		var ancient = 7200; // 2 hours

		$('.widget_whosonline span').each(function(){
			var $o = $(this);
			var since, oclass;

			var last = $o.data('whosonline_timestamp');
			if( typeof last == 'undefined' ) {
				last = Date.parse($o.text()) / 1000;
			}

			since = now - last;

			if(since > ancient) {
				oclass = "ancient";
			} else if(since > old) {
				oclass = "recent";
			} else {
				oclass = "active";
			}

			$o.attr('class', oclass);
		});
	}

	function toggleUpdates() {
		if (0 == whosonline.getWhosonlineUpdate) {
			whosonline.getWhosonlineUpdate = setInterval(getWhosonline, 30000);
		}
		else {
			clearInterval(whosonline.getWhosonlineUpdate);
			whosonline.getWhosonlineUpdate = '0';
		}
	}

	toggleUpdates();
	updateRecents();
});
