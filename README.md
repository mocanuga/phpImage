# phpImage
Dead simple image resizing, cropping an watermarking using GD
# Usage
  The constructor accepts an image resource or a filepath.
* Create simple crop
```
$image = new Image('/path/to/image.jpg');

// generate 100x100px crop
$image->crop(100, 100)
  ->saveCrop('jpg', '/path/', 'name.jpg', 90);
```
* Create multiple crops
```
$image = new Image('/path/to/image.jpg');
$image->crop(50, 50)
  ->saveCrop('jpg', '/path/', 'crop50x50.jpg', 90)
  ->crop(100, 100)
  ->saveCrop('jpg', '/path/', 'crop100x100.jpg', 90);
```
* Add watermark
```
$image = new Image('/path/to/image.jpg');
$image->crop(150, 150)
  ->stamp('/path/to/watermark.png', 'br')
  ->saveCrop('jpg', '/path/', 'watermark150x150.jpg', 90);
```
