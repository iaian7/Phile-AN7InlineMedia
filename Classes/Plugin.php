<?php
/**
 * Plugin class
 */
namespace Phile\Plugin\An7\InlineMedia;
use Phile\Exception;

/**
 * InlineMedia
 *
 * Based on James Doyle's PhileInlineImage plugin and helped by the work of Dan Reeves and Philipp Schmitt
 * Recognises most image, Vimeo, and Youtube links, replacing them with embed code
 * Also filters "media" metadata, allowing them to be used directly in templates
 *
 * @author  John Einselen
 * @link	http://iaian7.com
 * @license http://opensource.org/licenses/MIT
 * @package Phile\Plugin\An7\InlineMedia
 */

/**
 * Add custom variables in your content before it is parsed.
 */
class Plugin extends \Phile\Plugin\AbstractPlugin implements \Phile\Gateway\EventObserverInterface {
	
	public function __construct() {
		\Phile\Event::registerEvent('after_parse_content', $this);
	}

	public function on($eventKey, $data = null) {
		if ($eventKey == 'after_read_file_meta') {
			if (isset($data['meta']['media'])) {
//				$data['meta']['media_embed'] = $this->filter_media($data['meta']['media']);
			} else {
//				$data['meta']['media_embed'] = "";
			}
		} elseif ($eventKey == 'after_parse_content') {
			// check and see if the folder exists
			if (!is_dir(ROOT_DIR . $this->settings['images_dir'])) {
				throw new Exception("The path ".$this->settings['images_dir']." in the PhileInlineMedia config does not exists or is not a directory.");
			}

			// process using filter instead!
			$content = $this->filter_media($data['content']);

			// store the starting content
//			$content = $data['content'];
			// find the path for images
//			$path = \Phile\Utility::getBaseUrl() . '/' . $this->settings['images_dir'];
			// this parse happens after markdown processing which means that the potential image is wrapped in p tags
//			$regex = "/(<p>)(.*?)\.(jpg|jpeg|png|gif|webp|svg)+(<\/p>)/i";
			// main feature of the plugin, wrapping image names in HTML
//			$replace = "\n".'<'.$this->settings['wrap_element'].' class="'.$this->settings['wrap_class_img'].'" style="background-image: url(\''.$path.'$2.$3\');"></'.$this->settings['wrap_element'].'>';
			// add the modified content back in the data
//			$content = preg_replace($regex, $replace, $content);

			// Vimeo
//			$content = $data['content'];
			$regex = "/(<p>)(http\S+vimeo\.com\/)(\d*)(<\/p>)/i";
//			$replace = "\n".'<iframe class="'.$this->settings['wrap_class_vid'].'" src="//player.vimeo.com/video/$3?title=0&amp;byline=0&amp;portrait=0&amp;color=b2b0af" width="100%" height="100%" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
			$replace = "\n".'<iframe class="'.$this->settings['wrap_class_vid'].'" src="//player.vimeo.com/video/$3?title=0&amp;byline=0&amp;portrait=0&amp;color=b2b0af" width="100%" height="100%" allowfullscreen></iframe>';
//			$data['content'] = preg_replace($regex, $replace, $content);
			$content = preg_replace($regex, $replace, $content);

			// YouTube
//			$content = $data['content'];
			// additional URL solutions from http://stackoverflow.com/questions/3392993/php-regex-to-get-youtube-video-id
			$regex = "/(<p>)(http\S+youtube\.com\/watch\?v=)(\S+)(<\/p>)/i";
//			$replace = "\n".'<iframe class="'.$this->settings['wrap_class_vid'].'" src="https://www.youtube.com/embed/$3" width="100%" height="100%" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
			$replace = "\n".'<iframe class="'.$this->settings['wrap_class_vid'].'" src="https://www.youtube.com/embed/$3" width="100%" height="100%" allowfullscreen></iframe>';
			// replace and send the data back
			$data['content'] = preg_replace($regex, $replace, $content);
		}
	}

	private function filter_media($content) {
		// return nothing if no content is available for processing
		if (!isset($content)) return null;
			// find the path for images
		$path = \Phile\Utility::getBaseUrl() . '/' . $this->settings['images_dir'];
			// body copy processing happens after markdown processing which means that the potential image is wrapped in <p> tags
		$regex = "/(<p>)(.*?)\.(jpg|jpeg|png|gif|webp|svg)+(<\/p>)/i";
			// main feature of the plugin; wrapping image names in HTML
		$replace = "\n".'<'.$this->settings['wrap_element'].' class="'.$this->settings['wrap_class_img'].'" style="background-image: url(\''.$path.'$2.$3\');"></'.$this->settings['wrap_element'].'>';
			// add the modified content back in the data
		$content = preg_replace($regex, $replace, $content);
		return $content;
	}
}
