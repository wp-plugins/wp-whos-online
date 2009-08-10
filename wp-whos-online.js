jQuery(function($) {
	/*
	* Update author "last online" timestamps
	*/
	function getwpwhosonline(){
		toggleUpdates();
		var queryString = wpwhosonline.ajaxUrl +'?action=wpwhosonline_ajax_update&load_time=' + wpwhosonline.wpwhosonlineLoadTime + '&frontpage=' + wpwhosonline.isFirstFrontPage;
		ajaxCheckAuthors = $.getJSON(queryString, function(response){
			if(typeof response.latestupdate != 'undefined') {
				wpwhosonline.wpwhosonlineLoadTime = response.latestupdate;
				for(var i = 0; i < response.authors.length; i++) {
					$('#wpwhosonline-'+response.authors[i].user_id).
						text( response.authors[i].wpwhosonline ).
						data('wpwhosonline_timestamp', response.authors[i].wpwhosonline_unix);
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

		$('.widget_wpwhosonline span').each(function(){
			var $o = $(this);
			var since, oclass;

			var last = $o.data('wpwhosonline_timestamp');
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
		if (0 == wpwhosonline.getwpwhosonlineUpdate) {
			wpwhosonline.getwpwhosonlineUpdate = setInterval(getwpwhosonline, 30000);
		}
		else {
			clearInterval(wpwhosonline.getwpwhosonlineUpdate);
			wpwhosonline.getwpwhosonlineUpdate = '0';
		}
	}

	toggleUpdates();
	updateRecents();
});
