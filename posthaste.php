<?php 
/*
Plugin Name: Posthaste
Plugin URI: http://jon.smajda.com/blog/2008/09/01/posthaste-wp-plugin/
Description: Adds the post box from the Prologue theme (modified to include a Title field, Category dropdown and a Save as Draft option) to any theme.
Version: 1.1
Author: Jon Smajda
Author URI: http://jon.smajda.com
License: GPL
*/

/*
 * Copyright 2009 Jon Smajda (email: jon@smajda.com)
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

        // set post_status 
        if ($_POST['postStatus'] == 'draft') {
            $post_status = 'draft';
        } else {
            $post_status = 'publish';    
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

        // create the post
        $post_id = wp_insert_post( array(
            'post_author'   => $user_id,        
            'post_title'    => $post_title,
            'post_category' => $post_category,
            'post_content'  => $post_content,
            'tags_input'    => $tags,
            'post_status'   => $post_status
        ) );
        
        // now redirect back to blog
        if ($post_status == 'draft') { 
            $postresult = "?posthastedraft=1";
        } else { 
            $postresult = ''; 
        }
        wp_redirect( get_bloginfo( 'url' ) . '/' . $postresult );
        exit;
    }
}

// the post form
function posthasteForm() {
    // get options (if empty, fill in defaults & then get options)
    if(!$options = get_option('posthaste_fields')) { 
        posthasteAddDefaultFields(); 
        $options = get_option('posthaste_fields');
    } 

    if(current_user_can('publish_posts') && is_home() ) { // !is_admin() will get it on all pages
        echo "\n\t".'<div id="posthasteForm">'."\n\t";
        if (isset($_GET['posthastedraft'])) { 
            echo '<div id="posthasteDraftNotice">'
                 .'Post saved as draft. '
                 .'<a href="'.get_bloginfo('wpurl').'/wp-admin/edit.php?post_status=draft">'
                 .'View drafts</a>.</div>';
        }
        global $current_user;
        $user = get_userdata($current_user->ID);
        $nickname = attribute_escape($user->nickname);
        ?><form id="new-post" name="new-post" method="post" action="<?php bloginfo('url'); ?>/">
            <input type="hidden" name="action" value="post" />
            <?php wp_nonce_field( 'new-post' ); ?>
            
            <div id="posthasteIntro">
            <b>Hello, <?php echo $nickname; ?>!</b> <a href="<?php bloginfo('wpurl');  ?>/wp-admin/post-new.php" title="Go to the full WordPress editor">Write a new post</a>, <a href="<?php bloginfo('wpurl');  ?>/wp-admin/" title="Manage the blog">Manage the blog</a>, or <?php wp_loginout(); ?>.
            </div>

            <?php if ($options['title'] == "on") { ?>
            <label for="postTitle">Title:</label>
            <input type="text" name="postTitle" id="postTitle" tabindex="1" />
            <label for="postTitle" id="postLabel">Post:</label>
            <?php } ?>
            <textarea name="postText" id="postText" tabindex="2" ></textarea>


            <?php if ($options['tags'] == "on") { ?>
            <label for="tags" id="tagsLabel">Tag:</label>
            <input type="text" name="tags" id="tags" tabindex="3"  autocomplete="off" />
            <?php } ?>
            
            <?php if ($options['categories'] == "on") { ?>
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
            <?php } ?>


            <?php if ($options['draft'] == "on") { ?>
            <input type="checkbox" name="postStatus" value="draft" id="postStatus">
            <label for="postStatus" id="postStatusLabel">Draft</label>
            <?php } ?>

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
function addPosthasteStylesheet() {
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


/*
 * SETTINGS
 *
 * - 2.7 and up can modify these in Settings -> Writing -> Posthaste Settings
 *
 * - pre-2.7, the default options are added to the db properly,
 * but the user cannot change this. (well, they can modify the array in db manually...)
 *
 */

// add default fields to db if db is empty
function posthasteAddDefaultFields() {
    
    // fields that are on by default:
    $fields = array('title', 'tags', 'categories', 'draft'); 

    // fill in options array with each field on
    $options = array();
    foreach($fields as $field) {
        $options[$field] = "on";
    }

    // add the hidden value too
    $options['hidden'] = "on";

    // now add options to the db 
    add_option('posthaste_fields', $options, '', 'yes');
}


// Only load the next three functions if using 2.7 or higher:
global $wp_version;
if ($wp_version >= '2.7') {
    // add_settings_field
    function posthasteSettingsInit() {
        // add the section
        add_settings_section(
            'posthaste_settings_section', 
            'Posthaste Settings', 
            'posthasteSettingsSectionCallback', 
            'writing'
        );

        // add the fields
        add_settings_field(
            'posthaste_fields', 
            'Posthaste Fields',
            'posthasteFieldsCallback',
            'writing',
            'posthaste_settings_section'
        );

        register_setting('writing','posthaste_fields');
    }

    // callback with section description for new writing section
    function posthasteSettingsSectionCallback() {
        echo "<p>The settings below affect the behavior of the "
            ."<a href=\"http://wordpress.org/extend/plugins/posthaste/\">Posthaste</a> "
            ."plugin.</p>";
    }

    // prints the options form on writing page
    function posthasteFieldsCallback() {

        // fields you want in the form
        $fields = array('title', 'tags', 'categories','draft'); 

        // get options (if empty, fill in defaults & then get options)
        if(!$options = get_option('posthaste_fields')) { 
            posthasteAddDefaultFields(); 
            $options = get_option('posthaste_fields');
        } 

        if (!empty($options)) {
            $options = get_option('posthaste_fields');
            echo "<fieldset>\n";
            foreach ($fields as $field) {
                // see if it should be checked or not
                unset($checked);
                if ($options[$field] == 'on') { $checked = ' checked="checked" ';}

                // print the checkbox
                $fieldname = "posthaste_fields[$field]";
                echo "<label for=\"$fieldname\">\n"
                    ."<input {$checked} name=\"$fieldname\" type=\"checkbox\" id=\"$fieldname\">\n"
                    ." ".ucfirst($field)."\n</label><br />\n";
            }
            // now the hidden input (stupid hack so "all off" will work, probably a better way)
            echo '<input checked="checked" type="hidden" value="on" '
                 .'name="posthaste_fields[hidden]" id="posthaste_fields[hidden]">';
            echo "</fieldset>";
        }
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
add_action('wp_head', addPosthasteStylesheet);
// add options to "Writing" admin page in 2.7 and up
if ($wp_version >= '2.7') { add_action('admin_init', posthasteSettingsInit); }
