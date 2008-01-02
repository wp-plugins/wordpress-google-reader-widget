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

$greader_widget_version = "0.1";
$greader_feed_urls = array(
  'shared'  => 'http://www.google.com/reader/public/atom/user/{user}/state/com.google/broadcast',
  'starred' => 'http://www.google.es/reader/public/atom/user/{user}/state/com.google/starred',
  'tag'     => 'http://www.google.com/reader/public/atom/user/{user}/label/{tag}',
); 
$greader_page_urls = array(
  'shared'  => 'http://www.google.es/reader/shared/{user}',
  'starred' => 'http://www.google.es/reader/shared/user/{user}/state/com.google/starred',
  'tag'     => 'http://www.google.es/reader/shared/user/{user}/label/{tag}',
); 

function widget_greader_init() {
  if ( !function_exists('register_sidebar_widget') )
    return;

  register_sidebar_widget('Google Reader links', 'widget_greader');
  register_widget_control('Google Reader links', 'widget_greader_control', 370, 320);
}

function widget_greader($args){
  $options = get_option('widget_greader');
  extract($args);
  extract($options);

  global $greader_page_urls;
  $url = $greader_page_urls[$feed];
  $url = str_replace('{user}',$user,$url);
  $url = str_replace('{tag}',$tag,$url);

  $title = "<a href=\"${url}\">${title}</a>";
  echo $before_widget . $before_title . $title . $after_title;
  widget_greader_html($user,$feed,$tag,$count);
  echo $after_widget;
}

function widget_greader_html($user, $type, $tag, $num_items){
  if ( file_exists(ABSPATH . WPINC . '/rss.php') )
    require_once(ABSPATH . WPINC . '/rss.php');
  else
    require_once(ABSPATH . WPINC . '/rss-functions.php');

  global $greader_feed_urls;
  $url = $greader_feed_urls[$type];
  $url = str_replace('{user}',$user,$url);
  $url = str_replace('{tag}',$tag,$url);

  $rss = widget_greader_fetch_rss($url);
  
  if ( !$rss ) {
    echo "Error reading greader RSS.";
    return false;
  }

  echo "<ul id=\"greader-list\">";
  foreach($rss->output['FEED']['ENTRY'] as $item){
    $source = $item['SOURCE'];
    echo "<li><a class=\"greader-source\" href=\"${source['LINK']}\">${source['TITLE']}</a>:\n";
    echo "<a class=\"greader-entry\" href=\"${item['LINK']}\">${item['TITLE']}</a></li>";
  }
  echo "</ul>";

  return true;
}

function widget_greader_fetch_remote_file($url, $headers=array()) {
  $u = parse_url($url);
  $da = fsockopen($u['host'], 80, $errno, $errstr, 30);
  if (!$da)
    return false;
  $out = "GET ${u['path']} HTTP/1.1\r\n";
  $out .= "Host: ${u['host']}\r\n";  
  foreach($headers as $name => $value)
    $out .= "${name}: ${value}\r\n";
  $out .= "Connection: Close\r\n\r\n";

  $data = "";
  $in_body = false;
  fwrite($da, $out);
  while (!feof($da)) {
    $line = fgets($da, 1024);
    if($in_body){
      // HACK: strange lines in response with hexcodes
      if(preg_match('/^([a-f0-9]){1,4}$/',trim($line))){
	$weird_feed_hex_hack = true;
      }else{
	if($weird_feed_hex_hack){
	  $data = trim($data);
	  $line = trim($line)."\n";
	  $weird_feed_hex_hack = false;
	}
	$data .= $line;
      }
    }elseif(trim($line)==""){
      $in_body = true;
      fgets($da, 1024); 
    }elseif(preg_match("/^Location:(.+)$/",$line,$match)){
      if($redirect_times>10)
        return false;
      else
        return widget_fotolog_fetch_remote_file(trim($match[1]),$headers,$redirect_times+1);
    }
  }
  fclose($da);
  return $data;
}

function widget_greader_data_to_rss($data) {
  $rss = new atomParser($data);
  return $rss;
}


function widget_greader_fetch_rss ($url) {
  init();
  if ( !isset($url) )
    return false;
  
  if ( !MAGPIE_CACHE_ON ) {
    $data = widget_greader_fetch_remote_file( $url );
    if ( $data )
      return widget_greader_data_to_rss( $data );
    else
      return false;
  }

  $cache = new RSSCache( MAGPIE_CACHE_DIR, MAGPIE_CACHE_AGE );
  $cache_status = 0;
  $request_headers = array();
  $rss = 0;
  $errormsg = 0;

  if (!$cache->ERROR)
    $cache_status = $cache->check_cache( $url );
  
  if ( $cache_status == 'HIT' ) {
    $rss = $cache->get( $url );
    if ( isset($rss) and $rss ) {
      $rss->from_cache = 1;
      return $rss;
    }
  }

  if ( $cache_status == 'STALE' ) {
    $rss = $cache->get( $url );
    if ( $rss->etag and $rss->last_modified ) {
      $request_headers['If-None-Match'] = $rss->etag;
      $request_headers['If-Last-Modified'] = $rss->last_modified;
    }
  }

  $data = widget_greader_fetch_remote_file( $url, $request_headers );
  if (isset($data) and $data) {
    $rss = widget_greader_data_to_rss( $data );
    if ( $rss ) {
      $cache->set( $url, $rss );
      return $rss;
    }
  } 

  return $rss;
}

function widget_greader_checkoptions() {
  $oldoptions = $options = get_option('widget_greader');
  if (!$options['title']) $options['title'] = 'Google Reader';
  if (!$options['user']) $options['user'] = "07777491564637864058S";
  if (!$options['count']) $options['count'] = 10;
  if (!$options['feed']) $options['feed'] = 'shared';
  if (!$options['tag']) $options['tag'] = '';
  if ($oldoptions != $options) update_option('widget_greader', $options);
  return $options;
}

function widget_greader_control(){

  if ( isset($_POST['greader-user']) ) {
    $options['title'] = $_POST['greader-title'];
    $options['user'] = $_POST['greader-user'];
    $options['count'] = $_POST['greader-count'];
    $options['feed'] = $_POST['greader-feed'];
    $options['tag'] = $_POST['greader-tag'];
    foreach($options as $k=>$v)
      $options[$k] = trim($v);
    update_option('widget_greader', $options);
  }

  $options = widget_greader_checkoptions();
  $title = htmlspecialchars($options['title'], ENT_QUOTES);
  $user = htmlspecialchars($options['user'], ENT_QUOTES);
  $count = htmlspecialchars($options['count'], ENT_QUOTES);
  $feed = htmlspecialchars($options['feed'], ENT_QUOTES);
  $tag = htmlspecialchars($options['tag'], ENT_QUOTES);

?>	
    <p>Display your latest google reader news items.</p>
    <h3><?php _e('Settings'); ?></h3>
    <p><label for="greader-title"><?php _e('Title'); ?>:</label><input id="greader-title" name="greader-title" type="text" value="<?php echo $title ?>" /></p>
    <p><label for="greader-count">Number of links:</label><input id="greader-count" name="greader-count" type="text" value="<?php echo $count ?>" /></p>
    <p><label for="greader-user">Google Reader Id:</label><input id="greader-user" name="greader-user" type="text" value="<?php echo $user ?>" /><br/>
    <small>Go to "shared items" on your Google reader and copy the last numeric part of the public URL.</small></p>
    <p><label for="greader-feed" style="float: left;">Feed type:</label>
      <ul style="list-style: none; margin-left: 50px;">				   
        <li><input type="radio" name="greader-feed" id="greader-feed-shared" value="shared"<? if($feed=="shared"){ echo ' checked="checked"'; } ?> />
	  <label for="greader-feed-shared">shared items</label></li>
        <li><input type="radio" name="greader-feed" id="greader-feed-starred" value="starred"<? if($feed=="starred"){ echo ' checked="checked"'; } ?> />
          <label for="greader-feed-starred">starred items</label></li>
        <li><input type="radio" name="greader-feed" id="greader-feed-tag" value="tag"<? if($feed=="tag"){ echo ' checked="checked"'; } ?> />
          <label for="greader-feed-tag">tag</label><input type="text" name="greader-tag" value="<?php echo $tag ?>" /></li>
      </ul>
    </p>
<?php

}


 class atomParser {
    var $tags = array();
    var $output = array();
    var $retval = "";
    var $encoding = array();

    function atomParser($data)
    {
      $xml_parser = xml_parser_create("");
      xml_set_object($xml_parser, $this);
      xml_set_element_handler($xml_parser, "startElement", "endElement");
      xml_set_character_data_handler($xml_parser, "parseData");

      xml_parse($xml_parser, $data) or die(
        sprintf("XML error: %s at line %d",
        xml_error_string(xml_get_error_code($xml_parser)),
        xml_get_current_line_number($xml_parser))
      );

      xml_parser_free($xml_parser);
    }

    function startElement($parser, $tagname, $attrs)
    {
      if($this->encoding) {
	$tmpdata = "<$tagname";
        if($attrs) foreach($attrs as $key => $val) $tmpdata .= " $key=\"$val\"";
        $tmpdata .= ">";
        $this->parseData($parser, $tmpdata);
      } else {
        if($attrs['HREF'] && $attrs['REL'] && $attrs['REL'] == 'alternate') {
          $this->startElement($parser, 'LINK', array());
          $this->parseData($parser, $attrs['HREF']);
          $this->endElement($parser, 'LINK');
        }
        if($attrs['TYPE']) $this->encoding[$tagname] = $attrs['TYPE'];

	if(preg_match("/^(FEED|ENTRY)$/", $tagname)) {
          if($this->tags) {
            $depth = count($this->tags);
            list($parent, $num) = each($tmp = end($this->tags));
            if($parent) $this->tags[$depth-1][$parent][$tagname]++;
          }
          array_push($this->tags, array($tagname => array()));
        } else {
	  array_push($this->tags, $tagname);
        }
      }
    }

    function endElement($parser, $tagname)
    {
      # remove tag from tags array
      if($this->encoding) {
        if(isset($this->encoding[$tagname])) {
          unset($this->encoding[$tagname]);
          array_pop($this->tags);
        } else {
          if(!preg_match("/(BR|IMG)/", $tagname)) $this->parseData($parser, "</$tagname>");
        }
      } else {
        array_pop($this->tags);
      }
    }

    function parseData($parser, $data)
    {
      # return if data contains no text
      if(!trim($data)) return;
      $evalcode = "\$this->output";
      foreach($this->tags as $tag) {
        if(is_array($tag)) {
          list($tagname, $indexes) = each($tag);
          $evalcode .= "[\"$tagname\"]";
          if(${$tagname}) $evalcode .= "[" . (${$tagname} - 1) . "]";
          if($indexes) extract($indexes);
        } else {
          if(preg_match("/^([A-Z]+):([A-Z]+)$/", $tag, $matches)) {
            $evalcode .= "[\"$matches[1]\"][\"$matches[2]\"]";
          } else {
            $evalcode .= "[\"$tag\"]";
          }
        }
      }

      if(isset($this->encoding['CONTENT']) && $this->encoding['CONTENT'] == "text/plain") {
        $data = "<pre>$data</pre>";
      }

      eval("$evalcode .= '" . addslashes($data) . "';");
    }

  }

add_action('plugins_loaded', 'widget_greader_init');

?>
