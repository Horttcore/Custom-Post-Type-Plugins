var cptPlugin;

jQuery(document).ready(function(){

	cptPlugin = {

		/**
		 * Init
		 *
		 * @since v1.0
		 * @author Ralf Hortt
		 */
		init: function(){
			// Cache
			cptPlugin.wordpressRepositorySwitch = jQuery('#wordpress-repository');
			cptPlugin.githubRepositorySwitch = jQuery('#github-repository');

			// Bootstrap
			cptPlugin.bindEvents();
		},

		/**
		 * Bind events
		 *
		 * @since v1.0
		 * @author Ralf Hortt
		 */
		inputAutosize: function( element ) {
			element.attr('size', element.val().length);
		},

		/**
		 * Bind events
		 *
		 * @since v1.0
		 * @author Ralf Hortt
		 */
		bindEvents: function(){
			jQuery('[data-autosize=true]')
				// event handler
				.keyup( function(){
					cptPlugin.inputAutosize( jQuery(this) );
				})
				// resize on page load
				.each( function(){
					cptPlugin.inputAutosize( jQuery(this) );
				});

			cptPlugin.updatePluginRepository();
			jQuery('input[name="plugin-repository[]"]').change(function(){
				cptPlugin.updatePluginRepository();
			});
		},

		/**
		 * Bind events
		 *
		 * @since v1.0
		 * @author Ralf Hortt
		 */
		updatePluginRepository: function( element ){

			if ( true === cptPlugin.wordpressRepositorySwitch.prop('checked') ) {
				cptPlugin.wordpressRepositorySwitch.parent().parent().find('div').show();
			} else {
				cptPlugin.wordpressRepositorySwitch.parent().parent().find('div').hide();
				cptPlugin.wordpressRepositorySwitch.parent().parent().find('input[type="text"]').val('');
			}

			if ( true === cptPlugin.githubRepositorySwitch.prop('checked') ) {
				cptPlugin.githubRepositorySwitch.parent().parent().find('div').show();
			} else {
				cptPlugin.githubRepositorySwitch.parent().parent().find('div').hide();
				cptPlugin.githubRepositorySwitch.parent().parent().find('input[type="text"]').val('');
			}
		}

	};

	cptPlugin.init();

});