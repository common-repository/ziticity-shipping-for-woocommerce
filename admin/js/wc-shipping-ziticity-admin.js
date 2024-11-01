(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$(function () {
		$('.ziticity_warehouses .insert').click( function() {
			var $tbody = $('.ziticity_warehouses').find('tbody');
			var size = $tbody.find('tr').length;
			var code = '<tr class="new">\
				<td class="check-column"><input type="checkbox" /></td>\
				<td><input type="text" size="45" name="warehouses_address[' + size + ']" /></td>\
				<td><input type="text" size="35" name="warehouses_contact_person[' + size + ']" /></td>\
				<td><input type="text" size="20" name="warehouses_contact_phone[' + size + ']" /></td>\
				<td><input type="text" size="30" name="warehouses_comment[' + size + ']" /></td>\
			</tr>';

			$tbody.append( code );

			return false;
		} );

		$('.ziticity_warehouses .remove').click(function() {
			var $tbody = $('.ziticity_warehouses').find('tbody');

			$tbody.find('.check-column input:checked').each(function() {
				$(this).closest('tr').hide().find('input[type=checkbox]').val('');
			});

			return false;
		});
	});

})( jQuery );
