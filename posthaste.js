jQuery(function($) {
    jQuery('div#posthasteForm input#tags').suggest(
        ajaxUrl + '?action=posthaste_ajax_tag_search', 
        { delay: 350, minchars: 2, multiple: true, multipleSep: ", " } 
    );

    if (jQuery.suggest)
        jQuery('ul.ac_results').css('display', 'none'); // Hide tag suggestion box if displayed
});
