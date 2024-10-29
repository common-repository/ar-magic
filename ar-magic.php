<?php

/*
Plugin Name: AR Magic
Plugin URI: http://bz9.com
Description: BZ9, Aweber, Getresponse and Mailchimp autoresponder management
Version: 1.4
Author: BZ9.com
Author URI: http://bz9.com
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

class ar_magic_shortcode {

    private $handles = array();
    private $armagic_type;
    private $allowed_host = array(
        "aweber.com" => "AWeber",
        "forms.aweber.com" => "Aweber",
        "list-manage.com" => "MailChimp",
        "list-manage1.com" => "MailChimp",
        "app.getresponse.com" => "GetResponse",
        "bz9.com" => "BZ9"
        );

    /**
     * Initial setup
     */
    function __construct() {
        if( is_admin() )
        {
            add_action( 'wp_ajax_armagic_shortpop', array( $this, 'armagic_shortpop_ajax' ) );
            add_action( 'init', array(&$this, 'armagic_custom_init' ), 9 );
            add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
            add_action( 'save_post', array( $this, 'save' ) );
            add_filter( 'manage_edit-ar_magic_columns', array( $this, 'armagic_columns' ) ) ;
            add_action( 'manage_posts_custom_column', array( $this, 'armagic_populate_columns' ) );
            add_action( 'init', array(&$this, 'add_editor_button' ) );
            add_action( 'admin_menu', array( $this, 'armagic_register_submenu_page' ) );
            add_filter( 'enter_title_here', array( $this, 'armagic_enter_title_here' ) );
            if ( ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'ar_magic' ) || ( isset( $post_type ) && $post_type == 'ar_magic' ) || ( isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == 'ar_magic' ) )
            {
                add_action('admin_enqueue_scripts', array( $this, 'armagic_admin_scripts' ) );
            }
            add_action( 'admin_head', array( $this, 'armagic_plugin_header' ) );
            add_filter( 'post_row_actions', array( $this, 'armagic_remove_quick_edit' ) );
            add_filter( 'post_updated_messages', array( $this, 'armagic_updated_messages' ) );
        } else {
            add_shortcode( 'ar_magic', array(&$this, 'js_shortcode' ) );
        }
    }

    /**
     * Include popup window content
     */
    public function armagic_shortpop_ajax(){
        if ( !current_user_can( 'edit_pages' ) && !current_user_can( 'edit_posts' ) )
            die(__("You are not allowed to be here"));

        include_once('form.php');
        die();
    }

    function startsWith($haystack, $needle)
    {
        return $needle === "" || strpos($haystack, $needle) === 0;
    }

    /**
     * Shortcode handler
     */
    function js_shortcode( $atts = array(), $content = null ){
        $out = '';
        $sc_atts = array();
        extract( shortcode_atts( array(
            'saved' => ''
        ), $atts ) );
        if ($saved != '')
        {
            $saved_ar = get_post_meta( $saved, 'armagic_respcode', true );
            return $saved_ar;
        }

        if( $content )
        {
            if( is_array( $content ) )
            {
                foreach ( $content as $key => $content_new )
                {
                    $out .= $this->process_sc( $content_new );
                }

            } else {
                $out .= $this->process_sc($content, $sc_atts );
            }
        }
    return $out;
    }

    /**
     * Process shortcode
     */
    private function process_sc( $content, $sc_atts=null ){
        $content = str_replace('~','"',$content);


        $content = html_entity_decode($content);
        if($this->armagic_validate_ar( $content ))
        {
            return $content;
        }

        return;

    }

    /**
     * Print footer scripts
     */
    function call_js(){
        wp_print_scripts( $this->handles );
    }

    /**
     * Register editor button
     */
    function register_button( $buttons ) {
        array_push( $buttons, "|", "ar_magic" );
        return $buttons;
    }

    /**
     * Add editor plugin
     */
    function add_plugin( $plugin_array ) {
        $plugin_array['ar_magic'] = WP_PLUGIN_URL.'/ar-magic/js/ar_magic.js';
        return $plugin_array;
    }

    /**
     * Add editor button
     */
    function add_editor_button() {

        if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
            return;
        }

        if ( get_user_option( 'rich_editing' ) == 'true' ) {

            add_filter( 'mce_external_plugins', array( &$this, 'add_plugin' ) );
            add_filter( 'mce_buttons', array( &$this, 'register_button' ) );
        }

    }

    /**
     * Custom post setup
     */
    function armagic_custom_init() {
        $labels = array(
            'name' => 'Your Saved Auto Responders',
            'singular_name' => 'Auto Responder',
            'add_new' => 'Add Responder',
            'add_new_item' => 'Add Responder',
            'edit_item' => 'Edit Auto Responder',
            'new_item' => 'New Auto Responder',
            'all_items' => 'Saved Responders',
            'view_item' => 'View Auto Responder',
            'search_items' => 'Search Auto Responders',
            'not_found' =>  'No Auto Responders found',
            'not_found_in_trash' => 'No Auto Responders found in Trash',
            'parent_item_colon' => '',
            'menu_name' => 'AR Magic'
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'menu_position' => 5,
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => plugins_url( 'ar-magic/images/arMagic_logo16.png' ),
            'supports' => array( 'title')
        );

        register_post_type( 'ar_magic', $args );
    }

    /**
     * Set custom messages
     */
    function armagic_updated_messages( $messages ) {
        global $post, $post_ID;
        $messages['ar_magic'] = array(
            0 => '',
            1 => __('Your auto responder has been updated.' ),
            2 => __('Custom field updated.'),
            3 => __('Custom field deleted.'),
            4 => __('Your auto responder has been updated.'),
            5 => isset($_GET['revision']) ? sprintf( __('auto responder restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6 => __('Your auto responder has been saved.'),
            7 => __('Your auto responder has been saved.'),
            8 => sprintf( __('Auto responder submitted. <a target="_blank" href="%s">Preview Tool</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
            9 => sprintf( __('Auto responder scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Tool</a>'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
            10 => sprintf( __('Auto responder draft updated. <a target="_blank" href="%s">Preview Tool</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
        );
        return $messages;
    }

    /**
     * Adds the meta box container
     */
    public function add_meta_box() {
        add_meta_box(
            'armagic_descr',
            'Auto Responder Details',
            array( &$this, 'render_meta_box_content' ),
            'ar_magic',
            'normal',
            'high'
        );
    }

    /**
     * Render Meta Box content
     */
    public function render_meta_box_content( $post ) {
        // Use nonce for verification
        wp_nonce_field( plugin_basename( __FILE__ ), 'armagic_noncename' );

        $value = get_post_meta( $post->ID, 'armagic_descr', true );
        $value2 = get_post_meta( $post->ID, 'armagic_respcode', true );


        /*echo '<label class="armagic_label" for="armagic_descr">';
        _e( 'Description', 'myplugin_textdomain' );
        echo '</label> ';
        echo '<input type="text" id="armagic_descr" name="armagic_descr" value="'.esc_attr( $value ).'" size="70" /><br/><br/>';

        echo '<p class="formfield"><label class="armagic_label" for="armagic_tool">';
        _e( 'Add or Edit Auto Responder Code', 'myplugin_textdomain' );
        echo '</label> ';
        echo '<textarea id="armagic_respcode" name="armagic_respcode" rows="4" cols="70">'.esc_attr( $value2 ).'</textarea></p>';*/

echo '<div class="armagic">';
        echo '<table class="form-table"><tbody>';
        echo '<tr><th scope="row"><label class="armagic_label" for="armagic_descr">';
        _e( 'Description', 'myplugin_textdomain' );
        echo '</label></th>';
        echo '<td><input type="text" class="large-text" id="armagic_descr" name="armagic_descr" value="'.esc_attr( $value ).'"/>';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label class="armagic_label" for="armagic_respcode">';
        _e( 'Add or Edit Auto Responder Code', 'myplugin_textdomain' );
        echo '</label></th>';
        echo '<td><textarea id="armagic_respcode" class="large-text" name="armagic_respcode" rows="4" cols="70">'.esc_attr( $value2 ).'</textarea></p>';
        echo '</td></tr>';


		echo '</tbody></table>';
        echo '</div>';


    }

    /**
     * Save Meta Box content
     */
    public function save( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        if ( ! isset( $_POST['armagic_noncename'] ) || ! wp_verify_nonce( $_POST['armagic_noncename'], plugin_basename( __FILE__ ) ) )
            return;

        //  we need to check if the current user is authorised to do this action.
        if ( 'ar_magic' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) )
                return;
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) )
                return;
        }

        $post_ID = $_POST['post_ID'];
        //sanitize user input
        $mydata = sanitize_text_field( $_POST['armagic_descr'] );
        $mydata2 = stripslashes_deep($_POST['armagic_respcode']);

        $valid_ar = $this->armagic_validate_ar( $mydata2 );

        if ( !add_post_meta( $post_ID, 'armagic_descr', $mydata, true ) ) {
            update_post_meta( $post_ID, 'armagic_descr', $mydata );
        }
        //if valid ar commit to db
        if ( $valid_ar ){
            if ( !add_post_meta( $post_ID, 'armagic_respcode', $mydata2, true ) ) {
                update_post_meta( $post_ID, 'armagic_respcode', $mydata2 );
            }
            if ( !add_post_meta( $post_ID, 'armagic_type', $this->armagic_type, true ) ) {
                update_post_meta( $post_ID, 'armagic_type', $this->armagic_type );
            }
        }
    }

    /**
     * Validate user input
     */
    private function armagic_validate_ar( $content ){
        if ( !$content ) { return false; }
        $allowed_ar = false;

        //script type
        if($this->startsWith($content, "<script")){
            //script type
            preg_match_all( '/src=[\'"]([^\'"]+)[\'"]/i', $content, $matches );
        }
        //Aweber script type
        elseif($this->startsWith($content, '<div class="AW-Form-')){
            preg_match_all( '/js.src = [\'"]([^\'"]+)[\'"]/i', $content, $matches );
        }
        else {
            //html type
            preg_match_all( '/action=[\'"]([^\'"]+)[\'"]/i', $content, $matches );
        }


        if( count( $matches ) > 0 ){
            foreach( $matches[1] as $key => $value){


               $cur_domain = parse_url($matches[1][$key]);

                $dd = "";
                $d_parts = explode(".", $cur_domain['host']);
                $d_count = count($d_parts);
                $i = $d_count -1;
                while ($i >= 0)
                {
                    if($dd == ""){
                        $dd = $d_parts[$i];
                    } else {
                        $dd = $d_parts[$i].".".$dd;
                    }

                    if( array_key_exists( $dd,$this->allowed_host ) )
                    {

                        $this->armagic_type = $this->allowed_host[$dd];
                        return true;
                    }
                    $i--;
                }
            }
        }
        return false;
    }

    /**
     * Change title text
     */
    function armagic_enter_title_here( $message ){
        global $post;
        if( 'ar_magic' == $post->post_type ):
            $message = 'Enter Auto Responder Name';
        endif;
        return $message;
    }

    /**
     * Add admin scripts / css
     */
    public function armagic_admin_scripts(){
        wp_enqueue_style ( 'armagic_admin_css', WP_PLUGIN_URL.'/ar-magic/css/armagic_admin.css' );
    }

    /**
     * Set admin page header
     */
    function armagic_plugin_header() {
        global $post_type;
        ?>
        <style>
            <?php if ( ( $_GET['post_type'] == 'ar_magic' ) || ( $post_type == 'ar_magic' ) ) : ?>
            #icon-edit { background:transparent url('<?php echo WP_PLUGIN_URL .'/ar-magic/images/arMagic_logo32.png';?>') no-repeat; }
            <?php endif; ?>
        </style>
    <?php
    }

    /**
     * Set saved columns
     */
    public function armagic_columns( $columns ){
        $new_columns['cb'] = '<input type="checkbox" />';
        $new_columns['title'] = _x('Auto Responder Name', 'column name');
        $new_columns['description'] = 'Description';
        $new_columns['type'] = 'Type';
        $new_columns['date'] = _x('Date', 'column name');

        return $new_columns;
    }

    /**
     * Populate columns
     */
    public function armagic_populate_columns( $column ){
        if ( 'description' == $column ) {
            $armagic_descr = esc_html( get_post_meta( get_the_ID(), 'armagic_descr', true ) );
            echo $armagic_descr;
        }
        if ( 'type' == $column ) {
            $armagic_type = esc_html( get_post_meta( get_the_ID(), 'armagic_type', true ) );
            echo $armagic_type;
        }
    }

    /**
     * Remove quick edit link
     */
    public function armagic_remove_quick_edit( $actions ){
        global $post;
        if( $post->post_type == 'ar_magic' ) {
            unset( $actions['inline hide-if-no-js'] );
        }
        return $actions;
    }

    /**
     * Register pages
     */
    public function armagic_register_submenu_page(){
        add_submenu_page( 'edit.php?post_type=ar_magic', 'Account Management', 'Account Management', 'manage_options', 'ar_magic_account', array( $this, 'armagic_account' ) );
        add_submenu_page( 'edit.php?post_type=ar_magic', 'Instructions', 'Instructions', 'manage_options', 'ar_magic', array( $this, 'armagic_about' ) );
        }

    /**
     * Page headers
     */
    private function armagic_page_header(){
        ?>
        <div class="armagic_header_wrap">
        <div id="icon"><img src="<?php echo WP_PLUGIN_URL .'/ar-magic/images/arMagic_logo32.png';?>" /></div>
        <h2><?php echo get_admin_page_title(); ?></h2>
        </div>
        <?php
        return;
    }

    /**
     * About page
     */
    public function armagic_about(){
        $this->armagic_page_header();
        ?>
        <div align="center"><iframe width="650" height="434" src="//www.viewbix.com/frame/a4d5eedd-a7d8-4bf4-a495-46ccd8452a80?w=650&h=434" frameborder="0" scrolling="no" allowTransparency="true"></iframe></div>

    <?php
    }

    /**
     * Account page
     */
    public function armagic_account(){
        $this->armagic_page_header();
        ?>
        <div align="center"><span style="font-family: Arial; font-weight: normal; font-style: normal; text-decoration: none; font-size: 12pt;">Thank you for installing AR Magic. To login to your account for updates and to access further services please click the login button below</span></div><br>
        <div align="center"><a href="http://bz9.com/login" target="_blank"><img border="0" src="<?php echo WP_PLUGIN_URL .'/ar-magic/images/arMagic_login.png';?>" alt="Viewbix" width="400" height="100"></a></div>
        <div align="center"><a href="http://bz9.com/support" target="_blank"><img border="0" src="<?php echo WP_PLUGIN_URL .'/ar-magic/images/arMagic_Help.png';?>" alt="Viewbix" width="400" height="100"></a></div>
        <div align="center"><a href="http://bz9.com/ArMagicPluginOpen" target="_blank"><img border="0" src="<?php echo WP_PLUGIN_URL .'/ar-magic/images/arMagic_Open.png';?>" alt="Viewbix" width="400" height="100"></a></div>
    <?php
    }


}

/**
 * Initiate plugin
 */
$ar_magic_shortcode = new ar_magic_shortcode;