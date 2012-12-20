
/**
* Override the ACF method to add widget relationships.
*/
acf.relationship_update_results = function( div ) {
	
	// add loading class, stops scroll loading
	div.addClass('loading');
	
	
	// vars
	var s = div.attr('data-s'),
		paged = parseInt( div.attr('data-paged') ),
		taxonomy = div.attr('data-taxonomy'),
		post_type = div.attr('data-post_type'),
		lang = div.attr('data-lang'),
		left = div.find('.relationship_left .relationship_list'),
		right = div.find('.relationship_right .relationship_list'),
		action = div.attr('data-action'), //added by dallas
		sidebar = div.attr('data-sidebar'), //added by dallas
		inherit_from = div.attr('data-inherit_from'); //added by dallas
	
	
	// get results
	 jQuery.ajax({
		url: ajaxurl,
		type: 'post',
		dataType: 'html',
		data: { 
			'action' : action || 'acf_get_relationship_results', //added by dallas
			'sidebar' : sidebar, //added by dallas
			'inherit_from' : inherit_from, //added by dallas
			's' : s,
			'paged' : paged,
			'taxonomy' : taxonomy,
			'post_type' : post_type,
			'lang' : lang
		},
		success: function( html ){
			
			div.removeClass('no-results').removeClass('loading');
			
			// new search?
			if( paged == 1 )
			{
				left.find('li:not(.load-more)').remove();
			}
			
			
			// no results?
			if( !html )
			{
				div.addClass('no-results');
				return;
			}
			
			
			// append new results
			left.find('.load-more').before( html );
			
			
			// less than 10 results?
			var ul = jQuery('<ul>' + html + '</ul>');
			if( ul.find('li').length < 10 )
			{
				div.addClass('no-results');
			}
			
			
			// hide values
			acf.relationship_hide_results( div );
			
		}
	});
};
