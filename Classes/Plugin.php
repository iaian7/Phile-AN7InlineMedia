<?php
/**
 * Plugin class
 */
namespace Phile\Plugin\An7\InlineMedia;

use Phile\Gateway\EventObserverInterface;
use Phile\Plugin\AbstractPlugin;
use Phile\Core\Utility;
use Phile\Exception;

/**
  * InlineMedia
  * version 0.9 - 2020.12.12
  *
  * Recognises most image, Vimeo, and Youtube links, replacing them with embed code
  * Also filters meta tags, allowing media items to be used directly in templates without further modification
  * Designed exclusively to work with content folders alongside identically named pages
  * 	For example: /content/pages/page1.md and /content/pages/page1/image.jpg
  * 	When used: just the name "image.jpg" needs to be included on a new line in page1.md
  *
  * Page content:
  * 	Any image link that starts on a new line will be converted into a background image with
  * 	Any Vimeo or YouTube link will be converted into embed code (does not change existing wrapping tags)
  *
  * Meta tags:
  * 	"banner", "preview", and "media" meta tags work the same, recognised links are converted into embed code
  *
  * @author		John Einselen
  * @link		http://iaian7.com
  * @license	http://opensource.org/licenses/MIT
  * @package	Phile\Plugin\An7\InlineMedia
  *
  */

class Plugin extends AbstractPlugin implements EventObserverInterface
{
	protected $events = ['before_load_content' => 'setMediaPath',
						'after_read_file_meta' => 'processMeta',
						'after_parse_content' => 'processContent'];

	protected $mediaPath = '';

	protected function setMediaPath($data)
	{
		$this->mediaPath = Utility::getBaseUrl().'/content/'.$data['page']->getPageID().'/';
		// $this->mediaPath = Utility::getBaseUrl().'/content/'.$data['page']->getUrl().'/';
	}

	protected function processMeta($data)
	{
		if (isset($data['meta']['banner'])) {
			$data['meta']['banner'] = $this->filter_content($data['meta']['banner'], $this->mediaPath);
		}
		if (isset($data['meta']['preview'])) {
			$data['meta']['preview_url'] = $this->mediaPath.$data['meta']['preview'];
		}
		if (isset($data['meta']['media'])) {
			$data['meta']['media_embed'] = $this->filter_content($data['meta']['media'], $this->mediaPath);
		}
	}

	protected function processContent($data)
	{
		$data['content'] = $this->filter_content($data['content'], $this->mediaPath);
	}

	private function filter_content($content, $path) {
		// return nothing if no content is available for processing
		if (!isset($content)) return null;

		// Image Embed
		$regex = "/^(<p>|)([\w-]+)\.(jpg|jpeg|png|webp|svg)(<\/p>|)/mi";
		$replace = '<'.$this->settings['wrap_element'].' class="'.$this->settings['img_class'].'" style="background-image: url(\''.$path.'$2.$3\');"></'.$this->settings['wrap_element'].'>';
		$content = preg_replace($regex, $replace, $content);

// Match any image at the begining of a paragraph or on its own
//		^(<p>|)([\w-%]+)\.(gif)(.*)(<\/p>|)
// Only match images without paragraph text
//		^(<p>)([\w-%]+)\.(gif)(<\/p>)
// Only match images WITH additional text after them, removing the space between them. The original paragraph and text can be output with $1$4$5
//		^(<p>)([\w-%]+)\.(gif) (.+)(<\/p>)
//		^(<p>)([\w-%]+)\.(gif)(\n.+|.+)(<\/p>)

		// Gif Embed
		$regex = "/^(<p>)([\w-]+)\.(gif)(<\/p>)/mi";
		$replace = '<'.$this->settings['wrap_element'].' class="'.$this->settings['gif_class'].'" style="background-image: url(\''.$path.'$2.$3\');"></'.$this->settings['wrap_element'].'>';
		$content = preg_replace($regex, $replace, $content);
		// Gif Embed with paragraph text and container
		$regex = "/^(<p>)([\w-]+)\.(gif)(\n.+|.+)(<\/p>)/mi";
		$replace = '<div class="'.$this->settings['container_class'].'"><'.$this->settings['wrap_element'].' class="'.$this->settings['gif_class_inline'].'" style="background-image: url(\''.$path.'$2.$3\');"></'.$this->settings['wrap_element'].'>$1$4$5</div>';
		$content = preg_replace($regex, $replace, $content);


//	THIS IS THE OVERLAY CODE USED IN THE TWIG TEMPLATE
//		<a class="thumbnail" href="#{{ title }}_preview" onclick="{{ title }}_preview.play()" style="background-image: url({{ meta.previewimg }});"></a>
//		<div class="lightbox animate" id="{{ title }}_preview">
//			<a class="close" href="#!" onclick="{{ title }}_preview.pause()"></a>
//			<iframe class="animate" src="https://player.vimeo.com/video/{{ meta.preview }}" allowfullscreen onload="{{ title }}_preview=new Vimeo.Player(this)"></iframe>
//		</div>

		// Vimeo
		$regex = "/^(<p>|)(http\S+vimeo\.com\/)(\d+)(<\/p>|)/mi";
		$replace = '<iframe class="'.$this->settings['vid_class'].'" src="https://player.vimeo.com/video/$3?title=0&amp;byline=0&amp;portrait=0&amp;color=b2b0af" width="100%" height="100%" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		$content = preg_replace($regex, $replace, $content);

		// YouTube
		// additional URL solutions from http://stackoverflow.com/questions/3392993/php-regex-to-get-youtube-video-id
		$regex = "/^(<p>|)(http\S+youtube\.com\/watch\?v=|http\S+youtu.be\/)(\w+)(<\/p>|)/mi";
		$replace = '<iframe class="'.$this->settings['vid_class'].'" src="https://www.youtube.com/embed/$3" width="100%" height="100%" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		$content = preg_replace($regex, $replace, $content);

		// Add line breask!
		$regex = "/--/mi";
		$replace = '</br>';
		$content = preg_replace($regex, $replace, $content);

		return $content;
	}
}
