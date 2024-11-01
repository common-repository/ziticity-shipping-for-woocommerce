(function( $, window, document ) {
	'use strict';

	function parcelLockerChange() {
		$(document.body).on('change', "[name='wc_shipping_ziticity_parcel_locker']", function() {
			$(document.body).trigger('update_checkout');
		});
	}

	$(function() {
		parcelLockerChange();
	});

})( window.jQuery, window, document );
