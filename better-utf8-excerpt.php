<?php
/*
Plugin Name: better-utf8-excerpt
Version: 0.1
Author: Yang
Author URI: http://getok.org/
Plugin URI: http://getok.org/plugin/
Description: A better excerpt plugin with better support for Chinese. It also support cutomized tags.
*/

/* $Id: better-utf8-excerpt.php 23 2011-08-17 14:41:11Z Yang $ */

/* if the host doesn't support the mb_ functions, we have to define them. From Yskin's wp-CJK-excerpt, thanks to Yskin. */
if ( !function_exists('mb_strlen') ) {
	function mb_strlen ($text, $encode) {
		if ($encode=='UTF-8') {
			return preg_match_all('%(?:
					  [\x09\x0A\x0D\x20-\x7E]           # ASCII
					| [\xC2-\xDF][\x80-\xBF]            # non-overlong 2-byte
					|  \xE0[\xA0-\xBF][\x80-\xBF]       # excluding overlongs
					| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
					|  \xED[\x80-\x9F][\x80-\xBF]       # excluding surrogates
					|  \xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
					| [\xF1-\xF3][\x80-\xBF]{3}         # planes 4-15
					|  \xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
					)%xs',$text,$out);
		}else{
			return strlen($text);
		}
	}
}

/* from Internet, author unknown */
if (!function_exists('mb_substr')) {
    function mb_substr($str, $start, $len = '', $encoding="UTF-8"){
        $limit = strlen($str);
 
        for ($s = 0; $start > 0;--$start) {// found the real start
            if ($s >= $limit)
                break;
 
            if ($str[$s] <= "\x7F")
                ++$s;
            else {
                ++$s; // skip length
 
                while ($str[$s] >= "\x80" && $str[$s] <= "\xBF")
                    ++$s;
            }
        }
 
        if ($len == '')
            return substr($str, $s);
        else
            for ($e = $s; $len > 0; --$len) {//found the real end
                if ($e >= $limit)
                    break;
 
                if ($str[$e] <= "\x7F")
                    ++$e;
                else {
                    ++$e;//skip length
 
                    while ($str[$e] >= "\x80" && $str[$e] <= "\xBF" && $e < $limit)
                        ++$e;
                }
            }
 
        return substr($str, $s, $e - $s);
    }
}

/* the real excerpt function */
if (!function_exists('utf8_excerpt')) {
	function utf8_excerpt ($text) {
		global $post;
	
		if ( is_home() || is_archive() ) {
//get options			
			$home_excerpt_length = get_option('home_excerpt_length');
			$archive_excerpt_length = get_option('archive_excerpt_length');
			$allowd_tag = get_option('allowd_tag');
			$read_more_link = get_option('read_more_link');
			$read_more_text = get_option('read_more_text');
//set default options
			if ('' == $allowd_tag) {$allowd_tag = '<a><b><blockquote><br><cite><code><dd><del><div><dl><dt><em><h1><h2><h3><h4><h5><h6><i><img><li><ol><p><span><strong><ul>';}	
			if ('' == $home_excerpt_length) {$home_excerpt_length = 300;}
			if ('' == $archive_excerpt_length) {$archive_excerpt_length = 150;}		
			if ('' == $read_more_link) {$read_more_link = '阅读全文';}
//			if is home, display 300 character, otherwise 150 characters
			if (is_home()) {
				$length = $home_excerpt_length;
			} else {
				$length = $archive_excerpt_length;
			}			

//get and trim the text
			//$text = $post->post_content;
			//$text = apply_filters('the_content', $text);
			$text = str_replace(']]>', ']]&gt;', $text);
			$text = trim($text);
			
//			check if the post is already short
			if($length > mb_strlen(strip_tags($text), 'utf-8')) {
				return $text;
			}
				
//          check if there is a <!--more--> tag
            $more_position = stripos ($text, "<!--more-->");
            if ($more_position !== false) {
                $text = substr ($text, 0, $more_position);
            } 
            else {

// strip tags now, otherwise the <!--more--> tag is also stripped
				$text = strip_tags($text, $allowd_tag); 		
				$text = trim($text);
            	
//		 		check if the character is worth counting (ie. not part of an HTML tag). From Bas van Doren's Advanced Excerpt, thanks to Bas van Doren.
				$num = 0;
				$in_tag = false;
				for ($i=0; $num<$length || $in_tag; $i++) {
					if(mb_substr($text, $i, 1) == '<')
						$in_tag = true;
					elseif(mb_substr($text, $i, 1) == '>')
						$in_tag = false;
					elseif(!$in_tag)
						$num++;
				}
				$text = mb_substr ($text,0,$i, 'utf-8');            
            }
           
		   		$text .= $read_more_text;
				$text = force_balance_tags($text);
		
// 		add a "read more" link. If you don't want it, just comment the following line out.
		$text .= "<p class='read-more'><a href='".get_permalink()."'>".$read_more_link."</a></p>";
		return $text;
		}
		else {
			return $text;
		}

	}
}

add_filter('the_content', 'utf8_excerpt');


/* the options  */
function utf8_excerpt_menu(){
	add_options_page('Excerpt Options', 'Excerpt', 8, __FILE__, 'utf8_excerpt_options');	
}

function utf8_excerpt_options() {
?>
<div class="wrap">
    <h2>
        <?php _e( 'Excerpt Options' );s ?>
    </h2>

<form name="form1" method="post" action="options.php">
<?php wp_nonce_field('update-options'); ?>

<table class="form-table">
	<tr valign="top">
		<th scope="row"><?php _e('Length of excerpts on homepage:' ); ?></th>
		<td><input type="text" name="home_excerpt_length" value="<?php if (!get_option('home_excerpt_length')) { echo 300; } else { echo get_option('home_excerpt_length');} ?>" /><?php _e('characers' ); ?></td>
	</tr>
	<tr valign="top">
		<th scope="row"><?php _e('Length of excerpts on archive pages:' ); ?></th>
		<td><input type="text" name="archive_excerpt_length" value="<?php  if ('' == get_option('archive_excerpt_length')) { echo 150; } else { echo get_option('archive_excerpt_length');} ?>"/><?php _e('characers' ); ?></td>
	</tr>
	<tr valign="top">
		<th scope="row"><?php _e('Allow these HTML tags:' ); ?></th>
		<td><input type="text" name="allowd_tag" value="<?php if (!get_option('allowd_tag')) { echo '<a><b><blockquote><br><cite><code><dd><del><div><dl><dt><em><h1><h2><h3><h4><h5><h6><i><img><li><ol><p><span><strong><ul>'; } else { echo get_option('allowd_tag');} ?>" style="width:400px" /></td>
	</tr>
	<tr valign="top">
		<th scope="row"><?php _e('Display the "read more" link as:' ); ?></th>
		<td><input type="text" name="read_more_link" value="<?php if (!get_option('read_more_link')) { echo '阅读全文'; } else { echo get_option('read_more_link');} ?>" style="width:400px" /></td>
	</tr>
	<tr valign="top">
		<th scope="row"><?php _e('Display the "read more" text as:'); ?></th>
		<td><input type="text" name="read_more_text" value="<?php echo get_option('read_more_text'); ?>" style="width:400px" /></td>
	</tr>
<!-- If the options are not set, load the default values. 
The default values are the same as the same-name variables in the main function.
Because they are local variables, I have to write the value out here, which I don't like. -->

</table>

<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="home_excerpt_length,archive_excerpt_length, allowd_tag, read_more_link, read_more_text" />

<p class="submit">
<input type="submit" class="button-primary" name="Submit" value="<?php _e('Save Changes' ) ?>" />
</p>

</form>
</div>
<?php
}

add_action('admin_menu', 'utf8_excerpt_menu');
?>