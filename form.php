<?php
/**
 * File : form.php
 **/

$saved_ar = '<option value="0">Select Saved Responder</option>';
$args = array(
    'post_type' => 'ar_magic',
	'posts_per_page' => -1
);
$responders = new WP_Query( $args );
if( $responders->have_posts() ) {
    while( $responders->have_posts() ) {
        $responders->the_post();
        $saved_ar .= '<option value="'.get_the_ID().'">'.get_the_title().'</option>';
    }
}
else {
    $saved_ar = '<option value="0">No Saved Auto Responders Found</option>';
}
?>
<!DOCTYPE html>
<head>
    <title>AR Magic</title>
    <?php wp_enqueue_script("jquery"); ?>
    <?php wp_head(); ?>
    <script type="text/javascript" src="<?php echo get_option( 'siteurl' ) ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
    <script type="text/javascript">
        var $ = jQuery;
        var ar_magic = {
            e: '',
            init: function(e) {
                ar_magic.e = e;
                tinyMCEPopup.resizeToInnerSize();
            },
            insert: function createARShortcode(e) {
                var value = $('#armagic_code').val();
                var value2 = $('#armagic_code2').val();
                var value3 = $('#armagic_code3').val();
                var saved_tool = $('#armagic_saved_ar').val();
                var src = "";
                var style = "";

                if ( value !== '' )
                {
                    src = htmlEntities(value);
                    console.log(src);
                    insertshort(src,style);
                }
                if ( value2 !== '' )
                {
                    src = htmlEntities(value2);
                    insertshort(src,style);
                }
                if ( value3 !== '' )
                {
                    src = htmlEntities(value3);
                    insertshort(src,style);
                }
                if( saved_tool != 0)
                {
                    insertshort(saved_tool,'saved');
                }
                tinyMCEPopup.close();
            }
        }
        tinyMCEPopup.onInit.add(ar_magic.init, ar_magic);



        function strip_tags (input, allowed) {
            allowed = (((allowed || "") + "").toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join(''); // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
            var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
                commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
            return input.replace(commentsAndPhpTags, '').replace(tags, function ($0, $1) {
                return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
            });
        }


        function htmlEntities(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '~');
        }


        function insertshort(src,style)
        {
            var shortcode = 'ar_magic';
            var shortcode_close = '[/ar_magic]';
            var armagic;
            var height;
            var width;


            if (style === undefined) {

                if (src !== undefined) {
                    armagic = '[' + shortcode +']' + src + shortcode_close;
                    tinyMCEPopup.execCommand('mceInsertContent', 0, armagic);
                }



            } else if(style === 'saved'){
                armagic = '[' + shortcode + ' saved="' + src + '" /]';
                tinyMCEPopup.execCommand('mceInsertContent', 0, armagic);

            } else {
                armagic = '[' + shortcode +']' + src + shortcode_close;
                tinyMCEPopup.execCommand('mceInsertContent', 0, armagic);
            }
            return;
        }
    </script>
    <style>
        label {
            display: block;
        }
        textarea {
            margin-bottom: 10px;
        }
        #bz9_tools_txt {
            margin-bottom: 10px;
        }
        a {
            width:155px;
            display:block;
            margin-left:auto;
            margin-right:auto;
            padding: 2px 5px 2px 5px;
            text-decoration:none;
            font-family:arial;
            font-weight:bold;
            text-align:center;
            background-color: #fff9f8;
            color: white;
            font-size:9pt;
            border: 3px #454545 ridge;
        }
        a:hover {
            color: #5c79b7;
        }
    </style>
</head>
<body>
<div id="ar_magic_txt">Enter your autoresponder embed code into one of the boxes below. To save you time three boxes have been provided to make multiple shortcodes.</div>
<div id="armagic-form"><table id="armagic-table" class="form-table">
        <tr>
            <th><label for="armagic_code">Auto Responder Code</label></th>
            <td><textarea id="armagic_code" name="columns" rows="5" cols="40" /></textarea>
            </td>
        </tr>
        <tr>
            <th><label for="armagic_code2">Auto Responder Code</label></th>
            <td><textarea id="armagic_code2" name="columns" rows="5" cols="40" /></textarea>
            </td>
        </tr>
        <tr>
            <th><label for="armagic_code3">Auto Responder Code</label></th>
            <td><textarea id="armagic_code3" name="columns" rows="5" cols="40" /></textarea>
            </td>
        </tr>
        <tr>
            <th><label for="armagic_saved_ar">Saved Auto Responders</label></th>
            <td><select name="armagic_saved_ar" id="armagic_saved_ar"><?php echo $saved_ar; ?></select></td>
        </tr>
    </table>
    <p class="submit">
        <a href="javascript:ar_magic.insert(ar_magic.e)">Create Shortcode</a>
    </p>
</div>
</body>
</html>