<?php
/**
 * Plugin class
 */

namespace Phile\Plugin\An7\InlineMedia;
use Phile\Exception;

/**
  * InlineMedia
  * version 0.8.2 modified 2017.01.18
  *
  * Based on James Doyle's PhileInlineImage plugin and helped by the work of Dan Reeves and Philipp Schmitt
  * Recognises most image, Vimeo, and Youtube links, replacing them with embed code
  * Also filters meta data, allowing media items to be used directly in templates without further modification
  *
  * Meta tags:
  * 	"preview" = creates image_url for using as a preview thumbnail
  * 	"media" = creates embed code for using anywhere outside the normal content flow (for exmaple, embeding a video before the page title)
  * Page content:
  * 	any image name with extension will be converted into embed code (does not change existing wrapping tags)
  * 	any Vimeo or YouTube linkwill be converted into embed code inline with the rest of the content (does not change existing wrapping tags)
  *
  * @author		John Einselen
  * @link		http://iaian7.com
  * @license	http://opensource.org/licenses/MIT
  * @package	Phile\Plugin\An7\InlineMedia
  *
  */

class Plugin extends \Phile\Plugin\AbstractPlugin implements \Phile\Gateway\EventObserverInterface {

	private $page_url = 'not set yet';

	public function __construct() {
		\Phile\Event::registerEvent('before_load_content', $this); // Includes filePath but basically nothing else...using global variables, however, this will work!!!
		\Phile\Event::registerEvent('after_read_file_meta', $this); // May want to use this instead of changing the raw content, should be safer and allows creation of new meta data.
		\Phile\Event::registerEvent('after_parse_content', $this); // Also appears to RELOAD body content.
	}

	public function on($eventKey, $data = null) {
		if ($eventKey == 'before_load_content') { // Create path for use by all subsequent functions
			$result = str_replace(ROOT_DIR, \Phile\Utility::getBaseUrl()."/", $data['filePath']); // Absolute path
//			$result = str_replace(ROOT_DIR, "/", $data['filePath']); // Relative path
			$result = str_replace(".md", "/", $result);
			$this->page_url = $result;
		} elseif ($eventKey == 'after_read_file_meta') { // If preview or media meta data exists, create the url and embed codes
			if (isset($data['meta']['preview'])) {
				$data['meta']['preview_url'] = $this->page_url.$data['meta']['preview'];
			}
			if (isset($data['meta']['media'])) {
				$data['meta']['media_embed'] = $this->filter_content($data['meta']['media'], $this->page_url);
			}
		} elseif ($eventKey == 'after_parse_content') {  // Filter all page content, replacing image and media links with embed code
			$content = $this->filter_content($data['content'], $this->page_url);
			$data['content'] = $content;
		}
	}

	private function filter_content($content, $path) {
		// return nothing if no content is available for processing
		if (!isset($content)) return null;

		// Image Embed
		$regex = "/(<p>|)([\w-%]+)\.(jpg|jpeg|png|gif|webp|svg)(<\/p>|)/i";
		$replace = '<'.$this->settings['wrap_element'].' class="'.$this->settings['wrap_class_img'].'" style="background-image: url(\''.$path.'$2.$3\');"></'.$this->settings['wrap_element'].'>';
		$content = preg_replace($regex, $replace, $content);

		// Vimeo
		$regex = "/(<p>|)(http\S+vimeo\.com\/)(\d+)(<\/p>|)/i";
		$replace = '<iframe class="'.$this->settings['wrap_class_vid'].'" src="https://player.vimeo.com/video/$3?title=0&amp;byline=0&amp;portrait=0&amp;color=b2b0af" width="100%" height="100%" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		$content = preg_replace($regex, $replace, $content);

		// YouTube
		// additional URL solutions from http://stackoverflow.com/questions/3392993/php-regex-to-get-youtube-video-id
		$regex = "/(<p>|)(http\S+youtube\.com\/watch\?v=|http\S+youtu.be\/)(\w+)(<\/p>|)/i";
		$replace = '<iframe class="'.$this->settings['wrap_class_vid'].'" src="https://www.youtube.com/embed/$3" width="100%" height="100%" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		$content = preg_replace($regex, $replace, $content);

		return $content;
	}
}
