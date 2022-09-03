<?php
/**
 * Plugin class
 */

namespace Phile\Plugin\An7\InlineMedia;
use Phile\Exception;

/**
  * InlineMedia
  * version 0.7 modified 2016.08.11
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
		if ($eventKey == 'before_load_content') { // Create path for global use
//			$result = str_replace(ROOT_DIR, "/", $data['filePath']); // Relative path
			$result = str_replace(ROOT_DIR, \Phile\Utility::getBaseUrl()."/", $data['filePath']); // Absolute path
			$result = str_replace(".md", "/", $result);
			$this->page_url = $result;
		} elseif ($eventKey == 'after_read_file_meta') { // this is probably the prefered method, as new meta data tags can be created with the updated content (Image URL for preview images and Image OR Video embed for above-the-fold content, assuming that's the most efficient method)
			if (isset($data['meta']['preview'])) {
				$data['meta']['preview_url'] = $this->page_url.$data['meta']['preview'];
			} else {
				$data['meta']['preview_url'] = "";
			}
			if (isset($data['meta']['media'])) {
				$data['meta']['media_embed'] = $this->filter_content($data['meta']['media'], $this->page_url);
			} else {
				$data['meta']['media_embed'] = "";
			}
		} elseif ($eventKey == 'after_parse_content') { // and this is where we have to change page content. Every other method appears to just be reset by reloading the file content again and again.
			$content = $this->filter_content($data['content'], $this->page_url);
			$data['content'] = $content;
		}
	}

	private function filter_content($content, $path) {
		// return nothing if no content is available for processing
		if (!isset($content)) return null;

		// Image URL (needed for metadata processing)

		// Image Embed


// FIX THE <P> TAG ISSUE...NEEDS TO WORK BOTH WITH AND WITHOUT?


//		$regex = "/(<p>)(.*?)\.(jpg|jpeg|png|gif|webp|svg)+(<\/p>)/i";
		$regex = "/^(\\S+)\\.(jpg|jpeg|png|gif|webp|svg)/uim"; // this is currently broken by <p> tags that touch the file name
//		preg_replace("/^(\\S+)\\.(jpg|jpeg|png|gif|webp|svg)/uim", "http://website.com/$1.$2", $searchText);
		$replace = "\n".'<'.$this->settings['wrap_element'].' class="'.$this->settings['wrap_class_img'].'" style="background-image: url(\''.$path.'$1.$2\');"></'.$this->settings['wrap_element'].'>';
		// replace image strings with image embeds
		$content = preg_replace($regex, $replace, $content);

		// Vimeo
		$regex = "/(http\S+vimeo\.com\/)(\d*)/i";
//		$replace = "\n".'<iframe class="'.$this->settings['wrap_class_vid'].'" src="//player.vimeo.com/video/$3?title=0&amp;byline=0&amp;portrait=0&amp;color=b2b0af" width="100%" height="100%" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		$replace = "\n".'<iframe class="'.$this->settings['wrap_class_vid'].'" src="//player.vimeo.com/video/$2?title=0&amp;byline=0&amp;portrait=0&amp;color=b2b0af" width="100%" height="100%" allowfullscreen></iframe>';
		// replace Vimeo links with Vimeo embeds
		$content = preg_replace($regex, $replace, $content);

		// YouTube - additional URL solutions from http://stackoverflow.com/questions/3392993/php-regex-to-get-youtube-video-id
		$regex = "/(http\S+youtube\.com\/watch\?v=)(\S+)/i";
//		$replace = "\n".'<iframe class="'.$this->settings['wrap_class_vid'].'" src="https://www.youtube.com/embed/$3" width="100%" height="100%" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		$replace = "\n".'<iframe class="'.$this->settings['wrap_class_vid'].'" src="https://www.youtube.com/embed/$2" width="100%" height="100%" allowfullscreen></iframe>';
		// replace YouTube links with Vimeo embeds
		$content = preg_replace($regex, $replace, $content);

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
