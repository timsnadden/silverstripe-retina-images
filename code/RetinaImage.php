<?php
class RetinaImage extends Image {
	
	public static $use_retina_images = false;
	
	public static function set_use_retina_images() {
		Requirements::customScript(<<<JS
			if(document.cookie.indexOf('devicePixelRatio') != -1) {
				document.cookie='devicePixelRatio='+((window.devicePixelRatio === undefined) ? 1 : window.devicePixelRatio)+'; path=/';
			}
JS
		);

		if (preg_match('/(iphone|ipad)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
			RetinaImage::$use_retina_images = true;
		}

		if(Cookie::get('devicePixelRatio') >= 2) {
			RetinaImage::$use_retina_images = true;
		}

		if(isset($_GET['devicePixelRatio'])) {
			($_GET['devicePixelRatio'] == 1) ? RetinaImage::$use_retina_images = false : RetinaImage::$use_retina_images = true;
		}
	}
	
	function getTag() {
		if(file_exists(Director::baseFolder() . '/' . $this->Filename)) {
			$url = $this->getURL();
			$title = ($this->Title) ? $this->Title : $this->Filename;
			if($this->Title) {
				$title = Convert::raw2att($this->Title);
			} else {
				if(preg_match("/([^\/]*)\.[a-zA-Z0-9]{1,6}$/", $title, $matches)) $title = Convert::raw2att($matches[1]);
			}
			
			$height = $this->getHeight();
			$width = $this->getWidth();

			return "<img src=\"$url\" alt=\"$title\" width=\"$width\" height=\"$height\">";
		}
	}
	
	function getFormattedImage($format, $arg1 = null, $arg2 = null) {
		if($this->ID && $this->Filename && Director::fileExists($this->Filename)) {
			$cacheFile = $this->cacheFilename($format, $arg1, $arg2);

			if(!file_exists(Director::baseFolder()."/".$cacheFile) || isset($_GET['flush'])) {
				$this->generateFormattedImage($format, $arg1, $arg2);
			}

			$cached = new RetinaImage_Cached($cacheFile);
			// Pass through the title so the templates can use it
			$cached->Title = $this->Title;
			return $cached;
		}
	}
	
	function cacheFilename($format, $arg1 = null, $arg2 = null) {
		$folder = $this->ParentID ? $this->Parent()->Filename : ASSETS_DIR . "/";
		
		$format = $format.$arg1.$arg2;

		$cacheFile = $folder . "_resampled/$format-" . $this->Name;
		

		if(RetinaImage::$use_retina_images) {
			$cacheFile = pathinfo($cacheFile, PATHINFO_DIRNAME).'/'.pathinfo($cacheFile, PATHINFO_FILENAME).'@2x.'.pathinfo($cacheFile, PATHINFO_EXTENSION);
		}

		
		return $cacheFile;
	}
	
	function generateFormattedImage($format, $arg1 = null, $arg2 = null) {
		$cacheFile = $this->cacheFilename($format, $arg1, $arg2);
		if(RetinaImage::$use_retina_images) {
			if(isset($arg1)) { $arg1 = $arg1 * 2; }
			if(isset($arg2)) { $arg2 = $arg2 * 2; }
		}
		$gd = new GD(Director::baseFolder()."/" . $this->Filename);

		if($gd->hasGD()){
			$generateFunc = "generate$format";		
			if($this->hasMethod($generateFunc)){
				$gd = $this->$generateFunc($gd, $arg1, $arg2);
				if($gd){
					$gd->writeTo(Director::baseFolder()."/" . $cacheFile);
				}
	
			} else {
				USER_ERROR("Image::generateFormattedImage - Image $format function not found.",E_USER_WARNING);
			}
		}
	}

	function getDimensions($dim = "string") {
		if($this->getField('Filename')) {
			$imagefile = Director::baseFolder() . '/' . $this->getField('Filename');
			if(file_exists($imagefile)) {
				$size = getimagesize($imagefile);

				if(preg_match('/\@2x.(jpg|jpeg|png|gif)$/i', $imagefile)) {
					$size[0] /= 2;
					$size[1] /= 2;
				}
				
				return ($dim === "string") ? "$size[0]x$size[1]" : $size[$dim];
			} else {
				return ($dim === "string") ? "file '$imagefile' not found" : null;
			}
		}
	}

}

class RetinaImage_Cached extends RetinaImage {

	public function __construct($filename = null, $isSingleton = false) {
		parent::__construct(array(), $isSingleton);
		$this->Filename = $filename;
	}

}