an7InlineMedia
================

A plugin for [Phile](https://github.com/PhileCMS/Phile) that takes image names and embeds them as an element background style. Vimeo and YouTube links are also automatically parsed and turned into iframe embed code.

### 1.1 Installation (composer)
```
php composer.phar require an7/inline-media:*
```

### 1.2 Installation (Download)

* Install the latest version of [Phile](https://github.com/PhileCMS/Phile)
* Clone this repo into `plugins/an7/inlineMedia`

### 2. Activation

After you have installed the plugin. You need to add the following line to your `config.php` file:

```
$config['plugins']['an7\\inlineMedia'] = array('active' => true);
```

### Usage

Some example markdown input:

```markdown
## This is a Sub Page

This is page.md in the "sub" folder.

icon.png

https://vimeo.com/100056057

Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
```

This is what will be output. You can see that `icon.png` was wrapped with some HTML:

```html
<h2>This is a Sub Page</h2>
<p>This is page.md in the "sub" folder.</p>
<div class="content-image" style="background-image: url('http://localhost:8888/phile/content/images/icon.png');"></div>
<iframe class="content-video" src="//player.vimeo.com/video/100056057" width="100%" height="100%" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
```

### Config

This is the default `config.php` file. It explains what each key => value does.

```php
return array(
  'images_dir' => 'content/images/', // the folder where your images exist
  'wrap_element' => 'div', // the element to use for img backgrounds
  'wrap_class_img' => 'content-image', // the class applied to image elements
  'wrap_class_vid' => 'content-video' // the class applied to video elements
  );
```

You can see how the plugin applies the config data to the HTML output.

The `images_dir` should be relative to the root of the Phile installation (the constant `ROOT_DIR` will contain your root path).
