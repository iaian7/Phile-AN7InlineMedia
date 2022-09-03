<?php
/**
 * Plugin class
 */
namespace Phile\Plugin\An7\InlineMedia;
use Phile\Exception;

/**
 * Add custom variables in your content before it is parsed.
 */
class Plugin extends \Phile\Plugin\AbstractPlugin implements \Phile\Gateway\EventObserverInterface {

	public function __construct() {
		\Phile\Event::registerEvent('after_parse_content', $this);
	}

	public function on($eventKey, $data = null) {
		if ($eventKey == 'after_parse_content') {
			// check and see if the folder exists
			if (!is_dir(ROOT_DIR . $this->settings['images_dir'])) {
				throw new Exception("The path ".$this->settings['images_dir']." in the PhileInlineMedia config does not exists or is not a directory.");
			}
			// store the starting content
			$content = $data['content'];
			// find the path for images
			$path = \Phile\Utility::getBaseUrl() . '/' . $this->settings['images_dir'];
			// this parse happens after the markdown
			// which means that the potential image is wrapped
			// in p tags
			$regex = "/(<p>)(.*?)\.(jpg|jpeg|png|gif|webp|svg)+(<\/p>)/i";
			// main feature of the plugin, wrapping image names in HTML
			$replace = "\n".'<'.$this->settings['wrap_element'].' class="'.$this->settings['wrap_class_img'].'" style="background-image: url(\''.$path.'$2.$3\');"></'.$this->settings['wrap_element'].'>';
			// add the modified content back in the data
//			$data['content'] = preg_replace($regex, $replace, $content);
			$content = preg_replace($regex, $replace, $content);

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
			$data['content'] = preg_replace($regex, $replace, $content);
		}
	}
}
