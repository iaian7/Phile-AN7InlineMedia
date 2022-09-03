<?php
/**
 * Plugin class
 */

namespace Phile\Plugin\An7\InlineMedia;
use Phile\Exception;

/**
  * InlineMedia
  * version 0.6 modified 2016.08.11
  *
  * Based on James Doyle's PhileInlineImage plugin and helped by the work of Dan Reeves and Philipp Schmitt
  * Recognises most image, Vimeo, and Youtube links, replacing them with embed code
  * Also filters "media" metadata, allowing them to be used directly in templates
  *
  * Meta tags:
  * 	"preview" = creates image_url for using as a preview thumbnail
  * 	"media" = creates embed code for using anywhere outside the normal content flow (for exmaple, embeding a video before the page title)
  * Page content:
  * 	any image name with extension = creates embed code (does not change existing wrapping tags)
  * 	any Vimeo or YouTube link = creates embed code inline with the rest of the content (does not change existing wrapping tags)
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
		\Phile\Event::registerEvent('before_read_file_meta', $this);
		\Phile\Event::registerEvent('after_read_file_meta', $this); // May want to use this instead of changing the raw content, should be safer and allows creation of new meta data.
		\Phile\Event::registerEvent('after_load_content', $this); // Includes filePath, rawData, page, but meta and content are protected. This RELOADS the body content, and will replace content changes made in previous events.

		\Phile\Event::registerEvent('before_parse_content', $this); // Also appears to RELOAD body content.
		\Phile\Event::registerEvent('after_parse_content', $this); // Also appears to RELOAD body content.
	}

/*
	public function on($eventKey, $data = null) {
		print("\n\n\n\tEVENT: ".$eventKey."\n\n\n");
		print_r(get_defined_vars());

		if ($this->settings['page_dir'] == true) {
			$dir = preg_replace("/[^\\/]+$/u", "", "/content".$_SERVER['REQUEST_URI']);
			if (!is_dir(ROOT_DIR.$dir)) { // check if the folder exists
				throw new Exception("The specified page directory does not exist or is not a directory.");
			} else {
				// hack together a file path, because I cannot figure out any other way...all file or path variables are protected?!
				$path = preg_replace("/[^\\/]+$/u", "", \Phile\Utility::getBaseUrl()."/content".$_SERVER['REQUEST_URI']);
				// The following only works within an "after_resolve_page" event, which of course limits it to just that event
				// $path = preg_replace("/[^\\/]+$/u", "", \Phile\Utility::getBaseUrl()."/content".$data['pageId']);
			}
		} else {
			if (!is_dir(ROOT_DIR.$this->settings['images_dir'])) { // check if the folder exists
				throw new Exception("The path ".$this->settings['images_dir']." in the InlineMedia config does not exist or is not a directory.");
			} else {
				$path = ROOT_DIR.$this->settings['images_dir'];
			}
		}

		if ($eventKey == 'after_read_file_meta') {
			if (isset($data['meta']['media'])) {
				// completely silly, but because the filter expects paragraph tags when processing page content I'm adding them here so it's recognised correctly
				$data['meta']['media_embed'] = $this->filter_media('<p>'.$data['meta']['media'].'</p>', $path);
			} else {
				$data['meta']['media_embed'] = "";
			}
		} elseif ($eventKey == 'after_parse_content') {
			$content = $this->filter_media($data['content'], $path);
			$data['content'] = $content;
		}
	}
//*/


	public function on($eventKey, $data = null) {
//		print("\n\n\tEVENT: ".$eventKey);
//		print_r(get_defined_vars());

		if ($eventKey == 'before_load_content') {
			$result = str_replace(ROOT_DIR, \Phile\Utility::getBaseUrl()."/", $data['filePath']);
			$result = str_replace(".md", "/", $result);
			$this->page_url = $result;
//			print("\n\t page_url: ".$this->page_url);
		}

		if ($eventKey == 'before_read_file_meta') { // changing the rawData will permanently update the meta tags, but NOT the body content!
			if (isset($data['rawData'])) {
				$result = $this->filter_content($data['rawData'], $this->page_url);
				$data['rawData'] = $result;
			}
		}

		if ($eventKey == 'after_read_file_meta') { // this is probably the prefered method, as new meta data tags can be created with the updated content (Image URL for preview images and Image OR Video embed for above-the-fold content, assuming that's the most efficient method)
			if (isset($data['meta']['preview'])) {
				$data['meta']['preview_url'] = $this->filter_content($data['meta']['preview'], $this->page_url);
			} else {
				$data['meta']['preview_url'] = "";
			}
			if (isset($data['meta']['image'])) {
				$data['meta']['image_url'] = $this->filter_content($data['meta']['image'], $this->page_url);
			} else {
				$data['meta']['image_url'] = "";
			}
			// swap out "media" for "banner" perhaps?
			if (isset($data['meta']['media'])) {
				$data['meta']['media_embed'] = $this->filter_content($data['meta']['media'], $this->page_url);
			} else {
				$data['meta']['media_embed'] = "";
			}
			if (isset($data['rawData'])) {
				$result = $this->filter_content($data['rawData'], $this->page_url);
				$data['rawData'] = $result;
			}
		}

		if ($eventKey == 'after_load_content') {
			$content = $this->filter_content($data['rawData'], $this->page_url);
			$data['rawData'] = $content;
		}

		if ($eventKey == 'before_parse_content') {
			$content = $this->filter_content($data['content'], $this->page_url);
			$data['content'] = $content;
		}

		if ($eventKey == 'after_parse_content') { // and this is where we have to change page content. Every other method appears to just be reset by reloading the file content again and again.
			$content = $this->filter_content($data['content'], $this->page_url);
			$data['content'] = $content;
		}

		$result = null;
		print("\n\n\n\n\tEVENT: ".$eventKey);
		if (isset($data['rawData'])) print("\n\n\n\t RAW DATA: ".$data['rawData']);
		if (isset($data['content'])) print("\n\n\n\t CONTENT: ".$data['content']);
//		if (isset($data['rawData'])) print("\n\t EVENT: ".$data['rawData']);
//		print("\n\n\n");
//		print_r(get_defined_vars());
		print("\n\n\n\n");
	}

	private function filter_content($content, $path) {
		// return nothing if no content is available for processing
		if (!isset($content)) return null;

		// Image URL (needed for metadata processing)

		// Image Embed
//		$regex = "/(<p>)(.*?)\.(jpg|jpeg|png|gif|webp|svg)+(<\/p>)/i";
		$regex = "/^(\\S+)\\.(jpg|jpeg|png|gif|webp|svg)/uim";
//		preg_replace("/^(\\S+)\\.(jpg|jpeg|png|gif|webp|svg)/uim", "http://website.com/$1.$2", $searchText);
		$replace = "\n".'<'.$this->settings['wrap_element'].' class="'.$this->settings['wrap_class_img'].'" style="background-image: url(\''.$path.'$1.$2\');"></'.$this->settings['wrap_element'].'>';
		// replace image strings with image embeds
		$content = str_replace("2011-07-NormalMapping-large.jpg", "REPLACED", $content);
		$content = str_replace("2011-07-NormalMapping-thumbnail.jpg", "REALLYREPLACED", $content);
//		$content = preg_replace($regex, $replace, $content);

		// Vimeo
		$regex = "/(<p>)(http\S+vimeo\.com\/)(\d*)(<\/p>)/i";
//		$replace = "\n".'<iframe class="'.$this->settings['wrap_class_vid'].'" src="//player.vimeo.com/video/$3?title=0&amp;byline=0&amp;portrait=0&amp;color=b2b0af" width="100%" height="100%" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		$replace = "\n".'<iframe class="'.$this->settings['wrap_class_vid'].'" src="//player.vimeo.com/video/$3?title=0&amp;byline=0&amp;portrait=0&amp;color=b2b0af" width="100%" height="100%" allowfullscreen></iframe>';
		// replace Vimeo links with Vimeo embeds
//		$content = preg_replace($regex, $replace, $content);

		// YouTube - additional URL solutions from http://stackoverflow.com/questions/3392993/php-regex-to-get-youtube-video-id
		$regex = "/(<p>)(http\S+youtube\.com\/watch\?v=)(\S+)(<\/p>)/i";
//		$replace = "\n".'<iframe class="'.$this->settings['wrap_class_vid'].'" src="https://www.youtube.com/embed/$3" width="100%" height="100%" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		$replace = "\n".'<iframe class="'.$this->settings['wrap_class_vid'].'" src="https://www.youtube.com/embed/$3" width="100%" height="100%" allowfullscreen></iframe>';
		// replace YouTube links with Vimeo embeds
//		$content = preg_replace($regex, $replace, $content);

		return $content;
	}

/*
	private function filter_media($content, $path) {
		// return nothing if no content is available for processing
		if (!isset($content)) return null;
		// body copy processing happens after markdown processing which means that the potential media content is wrapped in <p> tags

		// Images
		$regex = "/(<p>)(.*?)\.(jpg|jpeg|png|gif|webp|svg)+(<\/p>)/i";
		$replace = "\n".'<'.$this->settings['wrap_element'].' class="'.$this->settings['wrap_class_img'].'" style="background-image: url(\''.$path.'$2.$3\');"></'.$this->settings['wrap_element'].'>';
		// replace image strings with image embeds
		$content = preg_replace($regex, $replace, $content);

		// Vimeo
		$regex = "/(<p>)(http\S+vimeo\.com\/)(\d*)(<\/p>)/i";
//		$replace = "\n".'<iframe class="'.$this->settings['wrap_class_vid'].'" src="//player.vimeo.com/video/$3?title=0&amp;byline=0&amp;portrait=0&amp;color=b2b0af" width="100%" height="100%" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		$replace = "\n".'<iframe class="'.$this->settings['wrap_class_vid'].'" src="//player.vimeo.com/video/$3?title=0&amp;byline=0&amp;portrait=0&amp;color=b2b0af" width="100%" height="100%" allowfullscreen></iframe>';
		// replace Vimeo links with Vimeo embeds
		$content = preg_replace($regex, $replace, $content);

		// YouTube - additional URL solutions from http://stackoverflow.com/questions/3392993/php-regex-to-get-youtube-video-id
		$regex = "/(<p>)(http\S+youtube\.com\/watch\?v=)(\S+)(<\/p>)/i";
//		$replace = "\n".'<iframe class="'.$this->settings['wrap_class_vid'].'" src="https://www.youtube.com/embed/$3" width="100%" height="100%" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		$replace = "\n".'<iframe class="'.$this->settings['wrap_class_vid'].'" src="https://www.youtube.com/embed/$3" width="100%" height="100%" allowfullscreen></iframe>';
		// replace YouTube links with Vimeo embeds
		$content = preg_replace($regex, $replace, $content);

		return $content;
	}
//*/

}
