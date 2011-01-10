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
	};

	// from http://snippets.dzone.com/posts/show/5925
	function formatDate(formatDate, formatString) {
		if(formatDate instanceof Date) {
			var months = new Array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
			var yyyy = formatDate.getFullYear();
			var yy = yyyy.toString().substring(2);
			var m = formatDate.getMonth();
			var mm = m < 10 ? "0" + m : m;
			var mmm = months[m];
			var d = formatDate.getDate();
			var dd = d < 10 ? "0" + d : d;

			var h = formatDate.getHours();
			var hh = h < 10 ? "0" + h : h;
			var n = formatDate.getMinutes();
			var nn = n < 10 ? "0" + n : n;
			var s = formatDate.getSeconds();
			var ss = s < 10 ? "0" + s : s;

			formatString = formatString.replace(/yyyy/i, yyyy);
			formatString = formatString.replace(/yy/i, yy);
			formatString = formatString.replace(/mmm/i, mmm);
			formatString = formatString.replace(/mm/i, mm);
			formatString = formatString.replace(/m/i, m);
			formatString = formatString.replace(/dd/i, dd);
			formatString = formatString.replace(/d/i, d);
			formatString = formatString.replace(/hh/i, hh);
			formatString = formatString.replace(/h/i, h);
			formatString = formatString.replace(/nn/i, nn);
			formatString = formatString.replace(/n/i, n);
			formatString = formatString.replace(/ss/i, ss);
			formatString = formatString.replace(/s/i, s);

			return formatString;
		} else {
			return "";
		}
	}

	function updateRecents(){
		var now = Math.round(new Date().getTime()/1000.0);

		var fresh = 120; // 2 minutes
		var old = 600; // 10 minutes
		var ancient = 7200; // 2 hours

		$('.widget_wpwhosonline span').each(function(){
			var $o = $(this);
			var since, oclass;

			var last = $o.data('wpwhosonline_timestamp');

			// last will be undefined the first time through
			if( last == null ) {
				var theText = $o.text();

				if( theText == 'Online now!' ) {
					last = now;
				} else {
					last = Date.parse($o.text()) / 1000;
				}
			}

			since = now - last;

			if(since > ancient) {
				oclass = "ancient";
			} else if(since > old) {
				oclass = "recent";
			} else {
				oclass = "active";

				// no longer fresh; remove "Online now!' text
				if(since > fresh && $o.text() == 'Online now!' ) {
					var theDate = new Date(last * 1000);
					$o.text( formatDate(theDate, 'dd mmm yyyy HH:MM:ss') );
					console.log( $o.text() );
				}
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
