jQuery(window).on('load',function () {
	function check_custon_font_styles( container ) {
		var default_params = {
			width: 'resolve',
			triggerChange: true,
			allowClear: true
		};
		var the_fam = jQuery( container ).find('.redux-typography-font-family');
		var mainID = jQuery( the_fam ).closest( '.redux-container-typography' ).attr( 'data-id' );
		var family = jQuery( '#' + mainID + ' .redux-typography-font-family' ).val();
			if( family ) {
				jQuery.each( KCFO.font_familes, function( index, font ) {
					if ( font.name == family ) {
						var style = jQuery( '#' + mainID + ' select.redux-typography-style' ).val();
						var html = '<option value=""></option>';
						var selected = '';
						jQuery.each( font.styles, function( fontstyle ) {
							if ( fontstyle === style ) {
								selected = ' selected="selected"';
							} else {
								selected = '';
							}
							html += '<option value="' + fontstyle + '"' + selected + '>' + fontstyle + '</option>';

						});
						// destroy select2
						jQuery( '#' + mainID + ' .redux-typography-style' ).select2( "destroy" );

						// Instert new HTML
						jQuery( '#' + mainID + ' .redux-typography-style' ).html( html );

						// Init select2
						jQuery( '#' + mainID + ' .redux-typography-style' ).select2( default_params );

						jQuery( '#' + mainID + ' .redux-typography-subsets' ).parent().fadeOut( 'fast' );
					}
				});
			}
		
	}
	function checkoninit() {
		jQuery('.redux-typography-container.typography-initialized').each( function() {check_custon_font_styles( this );});
	}
	setTimeout(function(){ checkoninit() }, 210 );


	jQuery('.redux-typography-container .redux-typography-family').on( 'change', function() {
		var selector = jQuery(this).closest('.redux-typography-container');
		setTimeout(function(){ check_custon_font_styles( selector ) }, 210 );
	});
	jQuery( '.redux-group-tab-link-a' ).click(function() {
		var number = jQuery(this).data('key');
		setTimeout(function(){
		jQuery('#'+number+'_section_group .redux-typography-container.typography-initialized').each( function() {check_custon_font_styles( this );}) }, 210 );
	});
});
