/* ToPersia Ads — انتخاب تصویر از کتابخانۀ رسانۀ وردپرس در فرم کمپین (data-tp-ads-pick). */
( function ( $ ) {
	'use strict';
	if ( ! $ || ! window.wp || ! wp.media ) {
		return;
	}
	$( document ).on( 'click', '[data-tp-ads-pick]', function ( ev ) {
		ev.preventDefault();
		var box   = $( this ).closest( '[data-tp-ads-img]' );
		var input = box.find( 'input[type="number"]' );
		var frame = wp.media( {
			title: ( window.TopersiaAdsAdmin && TopersiaAdsAdmin.pick ) || '',
			library: { type: 'image' },
			multiple: false
		} );
		frame.on( 'select', function () {
			var a   = frame.state().get( 'selection' ).first().toJSON();
			var src = a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url;
			input.val( a.id ).trigger( 'change' );
			box.find( 'img' ).remove();
			$( '<img alt="" width="60" height="60">' ).attr( 'src', src ).appendTo( box );
		} );
		frame.open();
	} );
}( window.jQuery ) );
