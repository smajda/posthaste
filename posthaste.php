<?php 
/*
Plugin Name: Posthaste
Plugin URI: http://jon.smajda.com/blog/2008/09/01/posthaste-wp-plugin/
Description: Adds the post box from the Prologue theme (modified to include a Title field, Category dropdown and a Save as Draft option) to any theme.
Version: 1.3
Author: Jon Smajda
Author URI: http://jon.smajda.com
License: GPL
*/

/*
 * Copyright 2009 Jon Smajda (email: jon@smajda.com)
 *
 * This plugin reuses code from the Prologue and P2 Themes,
 * Copyright Joseph Scott, Matt Thomas, Noel Jackson, and Automattic
 * according to the terms of the GNU General Public License.
 * http://wordpress.org/extend/themes/prologue/
 * http://wordpress.org/extend/themes/p2/
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

// When to display form
function posthasteDisplayCheck() {
    if(!$display = get_option('posthaste_display'))
        $display = 'front';

    switch ($display) {
        case 'front':
            if (is_home())
                $posthaste_display = true;
            break;
        case 'archive':
            if (is_archive() || is_home())
                $posthaste_display = true;
            break;
        case 'everywhere':
            if (!is_admin())
                $posthaste_display = true;
            break;
        case ((int)$display != 0):
            if (is_category($display))
                $posthaste_display = true;
            break;
        default:
            $posthaste_display = false;
    }

    return $posthaste_display;
}

// Which category to use
function posthasteCatCheck() {
    if (is_category())
        return get_cat_ID(single_cat_title('', false));
    else 
        return get_option('default_category', 1);
}

// Add to header
function posthasteHeader() {
    global $posthasteVariables;
    if('POST' == $_SERVER['REQUEST_METHOD']
        && !empty( $_POST['action'])
        && $_POST['action'] == 'post'
        && posthasteDisplayCheck() ) { // !is_admin() will get it on all pages

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
		$post_category = array($_POST['catsdd']);
        $returnUrl     = $_POST['posthasteUrl'];

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
        wp_redirect( $returnUrl . $postresult );
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
    
    if(current_user_can('publish_posts') && posthasteDisplayCheck() ) { 
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
        ?><form id="new-post" name="new-post" method="post" action="">
            <input type="hidden" name="action" value="post" />
            <?php wp_nonce_field( 'new-post' ); ?>
            
            <?php if ($options['gravatar'] == "on" || $options['greeting and links'] == "on") { ?>
            <div id="posthasteIntro">

            <?php if ($options['gravatar'] == "on" && function_exists('get_avatar') ) {
                    global $current_user;
                    echo get_avatar($current_user->ID, 40); } ?>

            <?php if ($options['greeting and links'] == "on") { ?>
            <b>Hello, <?php echo $nickname; ?>!</b> <a href="<?php bloginfo('wpurl');  ?>/wp-admin/post-new.php" title="Go to the full WordPress editor">Write a new post</a>, <a href="<?php bloginfo('wpurl');  ?>/wp-admin/" title="Manage the blog">Manage the blog</a>, or <?php wp_loginout(); ?>.
            <?php } ?>

            </div>
            <?php } ?>

            <?php if ($options['title'] == "on") { ?>
            <label for="postTitle">Title:</label>
            <input type="text" name="postTitle" id="postTitle" tabindex="1" />
            <label for="postTitle" id="postLabel">Post:</label>
            <?php } ?>
            <textarea name="postText" id="postText" tabindex="2" ></textarea>


            <?php if ($options['tags'] == "on") { ?>
            <label for="tags" id="tagsLabel">Tag:</label>
            <input type="text" name="tags" 
                   id="tags" tabindex="3"  
                   autocomplete="off"
            />
            <?php } ?>
            
            <?php 
            if ($options['categories'] == "on") { 
    			echo '<label for="cats" id="cats">Category:</label> ';
                $catselect = posthasteCatCheck();
                wp_dropdown_categories( array(
                    'hide_empty' => 0,
                    'name' => 'catsdd',
                    'orderby' => 'name',
                    'class' => 'catSelection',
                    'heirarchical' => 1,
                    'selected' => $catselect,
                    'tab_index' => 3
                    )
                ); 
            } else {
                $catselect = posthasteCatCheck();
                echo '<input checked="checked" type="hidden" value="'
                      .$catselect.'" name="catsdd" id="catsdd">';
            } ?>


            <?php if ($options['draft'] == "on") { ?>
            <input type="checkbox" name="postStatus" value="draft" id="postStatus">
            <label for="postStatus" id="postStatusLabel">Draft</label>
            <input checked="checked" type="hidden" value="<?php echo $_SERVER['REQUEST_URI']; ?>" name="posthasteUrl" >
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


// add posthaste.js and dependencies
function addPosthasteJs() {
	if( current_user_can('publish_posts') && !is_admin() ) {
        wp_enqueue_script(
            'posthaste',  // script name
            WP_PLUGIN_URL.'/'.basename(dirname(__FILE__)).'/posthaste.js', // url
            array('jquery', 'suggest')  // dependencies
        );
    }
}

// Blatant copying from p2 here
function posthaste_ajax_tag_search() {
    global $wpdb;
    $s = $_GET['q'];
    if ( false !== strpos( $s, ',' ) ) {
        $s = explode( ',', $s );
        $s = $s[count( $s ) - 1];
    }
    $s = trim( $s );
    if ( strlen( $s ) < 2 )
        die; // require 2 chars for matching

    $results = $wpdb->get_col( "SELECT t.name 
        FROM $wpdb->term_taxonomy 
        AS tt INNER JOIN $wpdb->terms 
        AS t ON tt.term_id = t.term_id 
        WHERE tt.taxonomy = 'post_tag' AND t.name 
        LIKE ('%". like_escape( $wpdb->escape( $s )  ) . "%')" );
    echo join( $results, "\n" );
    exit;
}

// pass wpurl from php to js
function posthaste_jsvars() {
    ?><script type='text/javascript'>
    // <![CDATA[
    var ajaxUrl = "<?php echo js_escape( get_bloginfo( 'wpurl' ) . '/wp-admin/admin-ajax.php' ); ?>";
    //]]>
    </script><?php
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
    $fields = array('title', 'tags', 'categories', 'draft', 'greeting and links'); 

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

        // add 'display on' option
        add_settings_field(
            'posthaste_display', 
            'Display Posthaste on...',
            'posthasteDisplayCallback',
            'writing',
            'posthaste_settings_section'
        );
        register_setting('writing','posthaste_display');

        // add fields selection
        add_settings_field(
            'posthaste_fields', 
            'Posthaste Elements',
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
        $fields = array('title', 'tags', 'categories','draft','gravatar', 'greeting and links'); 

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

    function posthasteDisplayCallback() {
        // get current values
        if(!$select = get_option('posthaste_display'))
            $select = 'front';

        $options = array(
                'front' => 'Front Page', 
                'archive' => 'Front and Archive Pages',
                'everywhere' => 'Everwhere',
                'catheader' => 'Single Category Page:'
            );

        $cats = get_categories(array(
                    'hide_empty' => 0,
                    'hierarchical' => 0
                ));

        foreach($cats as $cat){
            $options[$cat->cat_ID] = $cat->cat_name;
        }


        // build the dropdown menu
        echo '<select name="posthaste_display" id="posthaste_display">';

        foreach($options as $key=>$value) {
            if ($select == $key)
                $selected = ' selected="selected"';
            if ($key == 'catheader')
                $disabled = ' disabled="disabled"';
            echo "<option value=\"$key\"$selected$disabled>$value</option>\n";
            unset($selected,$disabled);
        }   

        echo '</select>';

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
// add js
add_action('init', addPosthasteJs);
// tell wp-admin.php about ajax tags function with wp_ajax_ action
add_action('wp_ajax_posthaste_ajax_tag_search', 'posthaste_ajax_tag_search');
// load php vars for js
add_action('wp_head', 'posthaste_jsvars');
// add options to "Writing" admin page in 2.7 and up
if ($wp_version >= '2.7') { add_action('admin_init', posthasteSettingsInit); }
