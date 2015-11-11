<?php

/**
 *
 */
class Image {
  private $image = null;
  private $name = null;
  private $crop = null;
  private $width = 0;
  private $height = 0;
  private $cropWidth = 0;
  private $cropHeight = 0;
  private $buffer = null;
  private $mimes = array("image/gif", "image/jpeg", "image/jpg", "image/png");
  private $type = null;
  public function __construct($file, $normalize = true, $width = 800, $height = 600) {
    if(is_resource($file)) {
      $this->width = imagesx($file);
      $this->height = imagesy($file);
      $this->image = $file;
      return $this;
    }
    if(!file_exists($file))
      throw new Exception ('The file '. $file . ' does not exist.');
    $data = getimagesize($file);
    if($data === false) {
      throw new Exception ('Please provide a valid image file.');
    } else {
      if(!in_array($data['mime'], $this->mimes)) {
        throw new Exception ('Please choose and image of allowed filetype (jpg, jpeg, png or gif)');
      }
      $this->type = $data['mime'];
      $this->width = $data[0];
      $this->height = $data[1];
      $name = explode('/',$file);
      $this->name = end($name);
      $this->image = $this->init($file, $this->type());
      if($normalize)
        $this->normalize();
    }
  }
  public function __destruct() {
    @imagedestroy($this->buffer);
    $this->buffer = null;
    @imagedestroy($this->crop);
    $this->crop = null;
    @imagedestroy($this->image);
    $this->image = null;
  }
  public function normalize ($width = 800, $height = 600) {
    $dim = $this->dimensions($this->width, $this->height, $width, $height);
    $this->buffer = imagecreatetruecolor($dim['width'], $dim['height']);
    imagecopyresampled($this->buffer, $this->image, 0, 0, 0, 0, $dim['width'], $dim['height'], $this->width, $this->height);
    $this->image = $this->buffer;
    $this->width = $dim['width'];
    $this->height = $dim['height'];
    return $this;
  }
  public function normalized() {
    return $this->buffer;
  }
  public function cropped() {
    return $this->crop;
  }
  public function width($crop = false) {
    return !$crop ? $this->width : ImageSX($this->crop);
  }
  public function height($crop = false) {
    return !$crop ? $this->height : ImageSY($this->crop);
  }
  public function crop ($width, $height, $x = 0, $y = 0, $center = true) {
    $dim = $this->dimensions($this->width, $this->height, $width, $height);
    if($center === true) {
        $this->buffer = imagecreatetruecolor($dim['width'], $dim['height']);
        if ($x == 0 && $y == 0) {
            if ($dim['width'] < $dim['height'] && $dim['height'] > $height) {
                $y = ($dim['height'] - $height) / 2;
            }
            if ($dim['width'] >= $dim['height'] && $dim['width'] > $width) {
                $x = ($dim['width'] - $width) / 2;
            }
        }
        imagecopyresampled($this->buffer, $this->image, 0, 0, abs($x), abs($y), $dim['width'], $dim['height'], $this->width, $this->height);
    } else {
        $this->buffer = imagecreatetruecolor($width, $height);
        imagecopyresampled($this->buffer, $this->image, 0, 0, abs($x), abs($y), $dim['width'], $dim['height'], $this->width, $this->height);
    }
    $this->crop = imagecreatetruecolor($width, $height);
    $this->cropWidth = $width;
    $this->cropHeight = $height;
    imagecopyresampled($this->crop, $this->buffer, 0, 0, 0, 0, $width, $height, $width, $height);
    imagedestroy($this->buffer);
    return $this;
  }
  public function stamp($img, $location = 'br', $padding = 5, $opacity = 100) { // tl, tr, tc, bl, br, bt, cl, cc, cr

    $toStamp = !is_null($this->crop) ? $this->crop : $this->image;

    $sx = !is_null($this->crop) ? $this->cropWidth : $this->width;
    $sy = !is_null($this->crop) ? $this->cropHeight : $this->height;

    $stamp = $img;
    if(!is_resource($stamp)) {
      $data = getimagesize($stamp);
      if(!file_exists($stamp))
        throw new Exception ('The file '. $stamp . ' does not exist.');
      $stamp = $this->init($stamp, $this->type($data['mime']));
    }

    $ssx = imagesx($stamp);
    $ssy = imagesy($stamp);

    $location = preg_match('/^tl|tr|tc|bl|br|bc|cl|cr|cc$/', $location) ? $location : 'cc';
    $px = 0;
    $py = 0;
    switch($location) {
      case 'tl': 
        $px = $padding;
        $py = $padding;
        break;
      case 'tr': 
        $px = $sx - $ssx - $padding;
        $py = $padding;
        break;
      case 'tc': 
        $px = ($sx/2)-($ssx/2);
        $py = $padding;
        break;
      case 'bl':
        $px = $padding;
        $py = $sy - $ssy - $padding;
        break;
      case 'br':
        $px = $sx - $ssx - $padding;
        $py = $sy - $ssy - $padding;
        break;
      case 'bc':
        $px= ($sx/2)-($ssx/2);
        $py = $sy - $ssy - $padding; 
        break;
      case 'cl': 
        $px= $padding;
        $py = ($sy/2)-($ssy/2);
        break;
      case 'cr': 
        $px= $sx - $ssx - $padding;
        $py = ($sy/2)-($ssy/2);
        break;
      case 'cc':
        $px= ($sx/2)-($ssx/2);
        $py = ($sy/2)-($ssy/2);
      default:
        break;
    }

    $this->_icma($toStamp, $stamp, $px, $py, 0, 0, $ssx, $ssy, $opacity);
    return $this;
  }
  private function _icma($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){
    $cut = imagecreatetruecolor($src_w, $src_h);
    imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h); 
    imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
    imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct); 
  } 
  public function saveImage($format = 'jpg', $path, $name = '', $quality = 80) {
    if(empty($name))
      $name = $this->name;
    $this->save($this->image, $format, $name, $path, $quality);
    return $this;
  }
  public function saveCrop($format = 'jpg', $path, $name = '', $quality = 80) {
    if(empty($name))
      $name = $this->name;
    $this->save($this->crop, $format, $name, $path, $quality);
    return $this;
  }
  private function save($resource, $format = 'jpg', $name, $path, $quality = 80) {
    if(!is_dir($path) || !is_writeable($path))
      throw new Exception ("The path doesn't exist or it's not writeable");
    if($format === 'jpg') {
      imagejpeg($resource, $path . $name, $quality);
    }
    if($format === 'png') {
      imagepng($resource, $path . $name, ($quality/10));
    }
    if($format === 'gif') {
      imagegif($resource, $path . $name);
    }
  }
  public function drawBorder($side = 'left', $color = '#ffffff', $thickness = 10, $toCrop = false) {
    $x = 0;
    $y = 0;
    $image = $toCrop ? $this->crop : $this->image;
    $w = imagesx($image) - 1;
    $h = imagesy($image) - 1;
    if(is_string($color)) {
      $clean = str_replace('#', '', $color);
      list($r, $g, $b) = str_split($clean, 2);
    }
    $color = imagecolorallocate($image, '0x'.$r, '0x'.$g, '0x'.$b);
    imagesetthickness($image, $thickness);
    switch($side) {
      case 'top':
        imageline($image, $x, $y, $x+$w, $y, $color);
        break;
      case 'bottom':
        imageline($image, $x, $y+$h, $x+$w ,$y+$h, $color);
        break;
      case 'left':
        imageline($image, $x, $y, $x, $y+$h, $color);
        break;
      case 'right':
        imageline($image, $x+$w, $y, $x+$w, $y+$h, $color);
        break;
      case 'all':
        imageline($image, $x, $y, $x+$w, $y, $color);
        imageline($image, $x, $y+$h, $x+$w ,$y+$h, $color);
        imageline($image, $x, $y, $x, $y+$h, $color);
        imageline($image, $x+$w, $y, $x+$w, $y+$h, $color);
        break;
    }
  }
  public function translateXY($x, $y, $width, $height) {
    $newX = $x;
    $newY = $y;
    if($x < 0) {
      $newX = ($x/$width) * $this->width;
    }
    if($y < 0) {
      $newY = ($y/$height) * $this->height;
    }
    return array('x' => $x, 'y' => $y);
  }
  private function dimensions($width, $height, $minWidth = 'auto', $minHeight = 'auto') {
    if($width == $minWidth && $height == $minHeight)
        return array('width' => $width, 'height' => $height);
    $ratio = $height / $width;
    $newWidth = $newHeight = 0;
    if($minHeight == 'auto' || ($width <= $height && $minWidth != 'auto')) {
        $newWidth = $minWidth;
        $newHeight = $newWidth * $ratio;
    }
    if($minHeight != 'auto' && ($width > $height || $minWidth == 'auto')) {
        $newHeight = $minHeight;
        $newWidth = $newHeight / $ratio;
    }
    if($newWidth < $minWidth)
        return $this->dimensions($newWidth, $newHeight, $minWidth, 'auto');
    if($newHeight < $minHeight)
        return $this->dimensions($newWidth, $newHeight, 'auto', $minHeight);
    return array('width' => round($newWidth), 'height' => round($newHeight));
  }
  private function init($file, $type) {
    $image = null;
    switch($type) {
      case 'gif':
        $image = imagecreatefromgif($file);
        break;
      case 'png':
        $image = imagecreatefrompng($file);
        break;
      case 'jpeg':
      default:
        $image = imagecreatefromjpeg($file);
        break;
    }
    return $image;
  }
  private function type($type = null) {
    if(is_null($type))
      $type = $this->type;
    if(in_array($type, array("image/jpeg", "image/jpg")))
      return 'jpeg';
    if(in_array($type, array("image/png")))
      return 'png';
    if(in_array($type, array("image/gif")))
      return 'gif';
  }
}
