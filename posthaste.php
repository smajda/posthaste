<?php 
/*
Plugin Name: Posthaste
Plugin URI: http://jon.smajda.com/blog/2008/09/01/posthaste-wp-plugin/
Description: Adds the post box from the Prologue theme (modified to include a Title field and Category dropbox) to any theme.
Version: 1.0.1
Author: Jon Smajda
Author URI: http://jon.smajda.com
License: GPL
*/

/*
 * Copyright 2008 Jon Smajda (email: jon@smajda.com)
 *
 * This plugin reuses code from the Prologue Theme,
 * Copyright Joseph Scott and Matt Thomas of Automattic,
 * http://wordpress.org/extend/themes/prologue/
 * according to the terms of the GNU General Public License.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * http://www.gnu.org/licenses/gpl.html
 * */

/***********
 * VARIABLES 
 ***********/
$posthasteVariables = array(
        "asidesCatName" => "asides"
    );



/************
 * FUNCTIONS 
 ************/

// Add to header
function posthasteHeader() {
    global $posthasteVariables;
    if('POST' == $_SERVER['REQUEST_METHOD']
        && !empty( $_POST['action'])
        && $_POST['action'] == 'post'
        && is_home()) { // !is_admin() will get it on all pages

        if (!is_user_logged_in()) {
            wp_redirect( get_bloginfo( 'url' ) . '/' );
            exit;
        }

        if( !current_user_can('publish_posts')) {
            wp_redirect( get_bloginfo( 'url' ) . '/' );
            exit;
        }

        check_admin_referer( 'new-post' );

        $user_id       = $current_user->user_id;
        $post_content  = $_POST['postText'];
        $post_title    = strip_tags($_POST['postTitle']);
        $tags          = $_POST['tags'];
		$post_category = $_POST['newcat_parent'];

		// if no category was selected, unset it & default will be used
        if ($post_category == '-1') {
            unset($post_category);
        } elseif ( isset($post_category) ) {
           $post_category = array($post_category);
        }


        // if title was kept empty, trim content for title 
        // & add to asides category if it exists (unless another
        // category was explicitly chosen in form)
        if (empty($post_title)) {
            $post_title      = strip_tags( $post_content );    
            $char_limit      = 40;    
            if( strlen( $post_title ) > $char_limit ) {
                $post_title = substr( $post_title, 0, $char_limit ) . ' ... ';
            }    
            // if "asides" category exists & title is empty, add to asides:
            if ($asidesCatID = get_cat_id($posthasteVariables['asidesCatName'])){
                $post_category = array($asidesCatID); 
            } 
        } 

        $post_id = wp_insert_post( array(
            'post_author'   => $user_id,        
            'post_title'    => $post_title,
            'post_category' => $post_category,
            'post_content'  => $post_content,
            'tags_input'    => $tags,
            'post_status'   => 'publish'
        ) );

        wp_redirect( get_bloginfo( 'url' ) . '/' );
        exit;
    }
}

// the post form
function posthasteForm() {
    if(current_user_can('publish_posts') && is_home() ) { // !is_admin() will get it on all pages
        echo "\n\t".'<div id="posthasteForm">'."\n\t";
        global $current_user;
        $user = get_userdata($current_user->ID);
        $nickname = attribute_escape($user->nickname);
        ?><form id="new-post" name="new-post" method="post" action="<?php bloginfo('url'); ?>/">
            <input type="hidden" name="action" value="post" />
            <?php wp_nonce_field( 'new-post' ); ?>
            
            <div id="posthasteIntro">
            <b>Hello, <?php echo $nickname; ?>!</b> <a href="<?php bloginfo('wpurl');  ?>/wp-admin/post-new.php" title="Go to the full WordPress editor">Write a new post</a>, <a href="<?php bloginfo('wpurl');  ?>/wp-admin/" title="Manage the blog">Manage the blog</a>, or <?php wp_loginout(); ?>.
            </div>
            <label for="postTitle">Title:</label>
            <!--<textarea name="postTitle" id="postTitle"></textarea></br>-->
            <input type="text" name="postTitle" id="postTitle" tabindex="1" />

            <label for="postTitle" id="postLabel">Post:</label>
            <textarea name="postText" id="postText" tabindex="2" ></textarea>

            <label for="tags" id="tags">Tag:</label>
            <input type="text" name="tags" id="tags" tabindex="3"  autocomplete="off" />
            
			<label for="cats" id="cats">Category:</label>
            <?php wp_dropdown_categories( array(
                'hide_empty' => 0,
                'name' => 'newcat_parent',
                'orderby' => 'name',
                'class' => 'catSelection',
                'heirarchical' => 1,
                'show_option_none' => __('Category...'),
                //'selected' => ,  // how to select default cat by default?
                'tab_index' => 3
                )
            ); ?>


            <input id="submit" type="submit" value="Post it" />

           
        </form>
        <?php
        echo '</div> <!-- close posthasteForm -->'."\n";
    }
}



// remove action if loop is in sidebar, i.e. recent posts widget
function removePosthasteInSidebar() {
    remove_action('loop_start', posthasteForm);
}



// add css
function addStylesheet() {
    // for pre2.6, guess path to plugins
    if ( !defined('WP_PLUGIN_URL') ) {
        define( 'WP_PLUGIN_URL', get_option('siteurl') . '/wp-content/plugins');
    }
    // Set url to stylesheet
    $pluginStyleURL = WP_PLUGIN_URL.'/'.basename(dirname(__FILE__)).'/style.css';

    // echo the stylesheet if user can publish posts
	if( current_user_can('publish_posts')) {
		echo "\n".'<link rel="stylesheet" type="text/css" media="screen" href="'.$pluginStyleURL.'">'."\n";
	}
}


/************
 * ACTIONS 
 ************/
// add header content
add_action('get_header', posthasteHeader);
// add form at start of loop
add_action('loop_start', posthasteForm); 
// don't display form in sidebar loop (i.e. 'recent posts')
add_action('get_sidebar', removePosthasteInSidebar);
// add the css
add_action('wp_head', addStylesheet);


