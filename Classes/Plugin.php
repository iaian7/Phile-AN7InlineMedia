<?php
/**
 * Plugin class
 */

namespace Phile\Plugin\An7\InlineMedia;
use Phile\Exception;

/**
  * InlineMedia
  * version 0.5 modified 2016.08.11
  *
  * Based on James Doyle's PhileInlineImage plugin and helped by the work of Dan Reeves and Philipp Schmitt
  * Recognises most image, Vimeo, and Youtube links, replacing them with embed code
  * Also filters "media" metadata, allowing them to be used directly in templates
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
		\Phile\Event::registerEvent('after_resolve_page', $this); // Only event that gives access to pageId, but appears to only occur just before the two parse content events!

//		\Phile\Event::registerEvent('before_render_template', $this);
//		\Phile\Event::registerEvent('template_engine_registered', $this);
//		\Phile\Event::registerEvent('after_render_template', $this);

		\Phile\Event::registerEvent('before_read_file_meta', $this);
		\Phile\Event::registerEvent('after_read_file_meta', $this);

		\Phile\Event::registerEvent('before_load_content', $this); // Includes filePath but basically nothing else
			\Phile\Event::registerEvent('after_load_content', $this); // Includes filePath, rawData, page, but meta and content are protected?

		\Phile\Event::registerEvent('before_parse_content', $this);
		\Phile\Event::registerEvent('after_parse_content', $this);
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
		print("\n\n\tEVENT: ".$eventKey);
		print("\n\t PageURL: ".$this->page_url);
//		print_r(get_defined_vars());
		if ($eventKey == 'after_resolve_page') {
//			print_r(get_defined_vars());
			print("\n\t PageID: ".$data['pageId']);
			$this->page_url = $data['pageId'];
		}
		if ($eventKey == 'after_load_content') {
			print("\n\t PATH: ".$data['filePath']);
			print("\n\t BASE: ".\Phile\Utility::getBaseUrl());
			print("\n\t ROOT: ".ROOT_DIR);
			$result = str_replace(ROOT_DIR, \Phile\Utility::getBaseUrl()."/", $data['filePath']);
			$result = str_replace(".md", "/", $result);
			print("\n\t RESULT: ".$result);
//			print("\n\n\n\t PageURL after: ".$this->page_url);
//			print("\n\n\n\t RAW: ".$data['rawData']);
//			print("\n\n\n\t PAGE: ".serialize($data['page']));
		}
		if ($eventKey == 'before_parse_content') {
			print_r(get_defined_vars());
		}
		if ($eventKey == 'after_parse_content') {
			print_r(get_defined_vars());
		}
		print("\n\n");
	}

	private function filter_content($content, $path) {
		// return nothing if no content is available for processing
		if (!isset($content)) return null;

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
