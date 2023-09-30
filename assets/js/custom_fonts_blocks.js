function kadence_add_custom_fonts( options ) {
	const { __ } = wp.i18n;
	if ( kadence_custom_fonts.font_families ) {
		const newOptions = [];
		Object.keys( kadence_custom_fonts.font_families ).forEach(function ( font ) {
		    const name = kadence_custom_fonts.font_families[font].name;
			const weights = [];
			Object.keys( kadence_custom_fonts.font_families[font].weights ).forEach(function ( weight ) {
				weights.push( {
					value: weight,
					label: weight,
				} ); 
			} );
			const styles = [];
			Object.keys( kadence_custom_fonts.font_families[font].styles ).forEach(function ( style ) {
				styles.push( {
					value: style,
					label: style,
				} ); 
			} );
			newOptions.push( {
				label: name,
				value: name,
				google: false,
				weights: weights,
				styles: styles,
			} );
		} );
		const custom_fonts = [
			{
				type: 'group',
				label: __( 'Custom Fonts', 'kadence-custom-fonts' ),
				options: newOptions,
			},
		];
		options = custom_fonts.concat( options );
	}
	return options;
}
wp.hooks.addFilter( 'kadence.typography_options', 'kadence_custom_fonts/add_fonts', kadence_add_custom_fonts );