<?
/*
Plugin Name: Google Reader widget
Plugin URI: http://www.peix.org/code/greader-widget
Description: Adds a widget with the links to the stories shared or starred or of a certain tag of a google reader
Author: Miguel Ibero
Version: 0.1
Author URI: http://www.peix.org/about/
*/

// 06-05-2007 version 0.1: initial release
// 29-12-2007 version 0.1.5: fixed small weird hex in feed bug
// 29-04-2010 version 0.2: remade code to use new widget class

class WP_Widget_Greader extends WP_Widget {

    var $feed_urls = array(
        'shared'  => 'http://www.google.com/reader/public/atom/user/{user}/state/com.google/broadcast',
        'starred' => 'http://www.google.com/reader/public/atom/user/{user}/state/com.google/starred',
        'tag'     => 'http://www.google.com/reader/public/atom/user/{user}/label/{tag}',
    ); 
    var $defaults =  array(
        'title'     => 'Google Reader',
        'user'      => '05295921671351034808',
        'count'     => 10,
        'feed'      => 'shared',
        'format'    => "<a class=\"greader-source\" href=\"{source_link}\">{source_title}</a>:\n<a class=\"greader-entry\" href=\"{link}\">{title}</a>",
        'tag'       => '',
    );

	function WP_Widget_Greader() {
		$widget_ops = array('classname' => 'widget_greader', 'description' => __( 'Display your latest google reader news items') );
		$this->WP_Widget('greader', __('Google Reader links'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract($args);
		$title = apply_filters('widget_title', empty( $instance['title'] ) ? __( 'Google Reader' ) : $instance['title'], $instance, $this->id_base);
		$feed = empty( $instance['feed'] ) ? 'shared' : $instance['feed'];
		$user = $instance['user'];
		$format = $instance['format'];
		$tag = $instance['tag'];
		$count = (int) $instance['count'];
        if(empty($tag)){
            $feed = 'shared';
        }
        $url = str_replace('{user}',$user,$this->feed_urls[$feed]);
        $url = str_replace('{tag}',$tag,$url);
        $rss = fetch_feed($url);
        $html = "";
        if(is_wp_error($rss)){
            $html .= $rss->get_error_message();
        }else{
            $html .= "<ul>\n";
            $i = 0;
            $links = array();
            foreach ( $rss->get_items() as $item ) {
                $link = $item->get_link();
                if(!in_array($link,$links)){
                    $links[] = $link;
                    $vars = array(
                        '{title}'           => $item->get_title(),
                        '{link}'            => $item->get_link(),
                        '{source_title}'    => $item->get_source()->get_title(),
                        '{source_link}'     => $link,
                    );
                    $html .= "<li>".strtr($format ,$vars)."</li>\n";
                    if($i++>$count){
                        break;
                    }
                }
            }
            $html .= "</ul>\n";
        }

		if (!empty($html)){
			echo $before_widget;
			if($title){
				echo $before_title.$title.$after_title;
            }
            echo $html.$after_widget;
		}
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
        $instance['user'] = strip_tags( $new_instance['user'] );
        $instance['format'] = $new_instance['format'];
		$instance['count'] = (int) $new_instance['count'];
		if ( in_array( $new_instance['feed'], array( 'user', 'friends') ) ) {
			$instance['feed'] = $new_instance['feed'];
		} else {
			$instance['feed'] = 'user';
		}
		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, $this->defaults);
		$title = esc_attr( $instance['title'] );
		$user = esc_attr( $instance['user'] );
		$count = esc_attr( $instance['count'] );
		$feed = esc_attr( $instance['feed'] );
		$tag = esc_attr( $instance['tag'] );
		$format = esc_attr( $instance['format'] );
	?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?>:</label> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>
		<p><label for="<?php echo $this->get_field_id('user'); ?>">Google Reader <?php _e('Id'); ?>:</label> <input class="widefat" id="<?php echo $this->get_field_id('user'); ?>" name="<?php echo $this->get_field_name('user'); ?>" type="text" value="<?php echo $user; ?>" /></p>
        <p style="font-size: x-small;"><?php _e('Go to "shared items" on your Google reader and copy the last numeric part of the public URL.'); ?></p>
		<p><label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Number of titles'); ?>:</label> <input class="widefat" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="text" value="<?php echo $count; ?>" /></p>
		<p><label for="<?php echo $this->get_field_id('tag'); ?>"><?php _e('Tag'); ?>:</label> <input class="widefat" id="<?php echo $this->get_field_id('tag'); ?>" name="<?php echo $this->get_field_name('tag'); ?>" type="text" value="<?php echo $tag; ?>" /></p>
		<p>
			<label for="<?php echo $this->get_field_id('feed'); ?>"><?php _e( 'Feed type' ); ?>:</label>
			<select name="<?php echo $this->get_field_name('feed'); ?>" id="<?php echo $this->get_field_id('feed'); ?>" class="widefat">
				<option value="shared"<?php selected( $instance['feed'], 'shared' ); ?>><?php _e('shared items'); ?></option>
				<option value="starred"<?php selected( $instance['feed'], 'starred' ); ?>><?php _e('starred items'); ?></option>
				<option value="tag"<?php selected( $instance['feed'], 'tag' ); ?>><?php _e('tagged items'); ?></option>
			</select>
		</p>
		<p><label for="<?php echo $this->get_field_id('format'); ?>"><?php _e('Item format'); ?>:</label> <textarea class="widefat" id="<?php echo $this->get_field_id('format'); ?>" name="<?php echo $this->get_field_name('format'); ?>"><?php echo $format; ?></textarea></p>

<?php
	}
}

add_action('widgets_init', create_function('', 'return register_widget("WP_Widget_Greader");'));

?>
