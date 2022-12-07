<?php

# ======================================================================== #
#
#  This work is licensed under the Creative Commons Attribution 3.0 Unported
#  License. To view a copy of this license,
#  visit http://creativecommons.org/licenses/by/3.0/ or send a letter to
#  Creative Commons, 444 Castro Street, Suite 900, Mountain View, California,
#  94041, USA.
#
#  All rights reserved.
#
#  Author:    Jarrod Oberto
#  Version:   1.5.1
#  Date:      10-05-11
#  Purpose:   Provide tools for image manipulation using GD
#  Param In:  See functions.
#  Param Out: Produces a resized image
#  Requires:  Requires PHP GD library.
#  Usage Example:
#    include("lib/php_image_magician.php");
#    $magicianObj = new resize('images/car.jpg');
#    $magicianObj -> resizeImage(150, 100, 0);
#    $magicianObj -> saveImage('images/car_small.jpg', 100);
#
#  - See end of doc for more examples -
#
#  Supported file types include: webp, jpg, png, gif, bmp, psd (read)
#
#  The following functions are taken from phpThumb() [available from
#    http://phpthumb.sourceforge.net], and are used with written permission
#  from James Heinrich.
#    - GD2BMPstring
#    - GetPixelColor
#    - LittleEndian2String
#
#  The following functions are from Marc Hibbins and are used with written
#  permission (are also under the Attribution-ShareAlike
#  [http://creativecommons.org/licenses/by-sa/3.0/] license.
#
#  PhpPsdReader is used with written permission from Tim de Koning.
#  [http://www.kingsquare.nl/phppsdreader]
#
#  Modificatoin history
#  Date      Initials  Ver Description
#  10-05-11  J.C.O   0.0 Initial build
#  01-06-11  J.C.O   0.1.1   * Added reflections
#              * Added Rounded corners
#              * You can now use PNG interlacing
#              * Added shadow
#              * Added caption box
#              * Added vintage filter
#              * Added dynamic image resizing (resize on the fly)
#              * minor bug fixes
#  05-06-11  J.C.O   0.1.1.1 * Fixed undefined variables
#  17-06-11  J.C.O   0.1.2   * Added image_batch_class.php class
#              * Minor bug fixes
#  26-07-11  J.C.O   0.1.4 * Added support for external images
#              * Can now set the crop poisition
#  03-08-11  J.C.O   0.1.5 * Added reset() method to reset resource to
#                original input file.
#              * Added method addTextToCaptionBox() to
#                simplify adding text to a caption box.
#              * Added experimental writeIPTC. (not finished)
#              * Added experimental readIPTC. (not finished)
#  11-08-11  J.C.O     * Added initial border presets.
#  30-08-11  J.C.O     * Added 'auto' crop option to crop portrait
#                images near the top.
#  08-09-11  J.C.O     * Added cropImage() method to allow standalone
#                cropping.
#  17-09-11  J.C.O     * Added setCropFromTop() set method - set the
#                percentage to crop from the top when using
#                crop 'auto' option.
#              * Added setTransparency() set method - allows you
#                to turn transparency off (like when saving
#                as a jpg).
#              * Added setFillColor() set method - set the
#                background color to use instead of transparency.
#  05-11-11  J.C.O   0.1.5.1 * Fixed interlacing option
#  0-07-12  J.C.O   1.0
#
#  Known issues & Limitations:
# -------------------------------
#  Not so much an issue, the image is destroyed on the deconstruct rather than
#  when we have finished with it. The reason for this is that we don't know
#  when we're finished with it as you can both save the image and display
#  it directly to the screen (imagedestroy($this->imageResized))
#
#  Opening BMP files is slow. A test with 884 bmp files processed in a loop
#  takes forever - over 5 min. This test inlcuded opening the file, then
#  getting and displaying its width and height.
#
#  $forceStretch:
# -------------------------------
#  On by default.
#  $forceStretch can be disabled by calling method setForceStretch with false
#  parameter. If disabled, if an images original size is smaller than the size
#  specified by the user, the original size will be used. This is useful when
#  dealing with small images.
#
#  If enabled, images smaller than the size specified will be stretched to
#  that size.
#
#  Tips:
# -------------------------------
#  * If you're resizing a transparent png and saving it as a jpg, set
#  $keepTransparency to false with: $magicianObj->setTransparency(false);
#
#  FEATURES:
#    * EASY TO USE
#    * BMP SUPPORT (read & write)
#    * PSD (photoshop) support (read)
#    * RESIZE IMAGES
#      - Preserve transparency (png, gif)
#      - Apply sharpening (jpg) (requires PHP >= 5.1.0)
#      - Set image quality (jpg, png)
#      - Resize modes:
#        - exact size
#        - resize by width (auto height)
#        - resize by height (auto width)
#        - auto (automatically determine the best of the above modes to use)
#        - crop - resize as best as it can then crop the rest
#      - Force stretching of smaller images (upscale)
#    * APPLY FILTERS
#      - Convert to grey scale
#      - Convert to black and white
#      - Convert to sepia
#      - Convert to negative
#    * ROTATE IMAGES
#      - Rotate using predefined "left", "right", or "180"; or any custom degree amount
#    * EXTRACT EXIF DATA (requires exif module)
#      - make
#      - model
#      - date
#      - exposure
#      - aperture
#      - f-stop
#      - iso
#      - focal length
#      - exposure program
#      - metering mode
#      - flash status
#      - creator
#      - copyright
#    * ADD WATERMARK
#      - Specify exact x, y placement
#      - Or, specify using one of the 9 pre-defined placements such as "tl"
#        (for top left), "m" (for middle), "br" (for bottom right)
#        - also specify padding from edge amount (optional).
#      - Set opacity of watermark (png).
#    * ADD BORDER
#    * USE HEX WHEN SPECIFYING COLORS (eg: #ffffff)
#    * SAVE IMAGE OR OUTPUT TO SCREEN
#
# ======================================================================== #

class imageLib
{
    /**
     * @var string
     */
    private $fileName;
    /**
     * @var false|GdImage|resource
     */
    private $image;
    /**
     * @var false|GdImage|resource
     */
    protected $imageResized;
    /**
     * @var false|int
     */
    private $widthOriginal;
    /**
     * @var false|int
     */
    private $heightOriginal;
    /**
     * @var false|int
     */
    private $width;
    /**
     * @var false|int
     */
    private $height;
    /**
     * @var string
     */
    private $fileExtension;
    /**
     * @var array
     */
    private $errorArray = [];
    /**
     * @var bool
     */
    private $forceStretch = true;
    /**
     * @var bool
     */
    private $aggresiveSharpening = false;
    /**
     * @var string[]
     */
    private $transparentArray = ['.png', '.gif', 'webp'];
    /**
     * @var bool
     */
    private $keepTransparency = true;
    /**
     * @var int[]
     */
    private $fillColorArray = ['r' => 255, 'g' => 255, 'b' => 255];
    /**
     * @var string[]
     */
    private $sharpenArray = ['jpg'];
    /**
     * @var string
     */
    private $filterOverlayPath;
    /**
     * @var bool
     */
    private $isInterlace;
    /**
     * @var array
     */
    private $captionBoxPositionArray = [];
    /**
     * @var string
     */
    private $fontDir = 'fonts';
    /**
     * @var int
     */
    private $cropFromTopPercent = 10;

    /**
     * @param string $fileName
     * @throws PrestaShopException
     */
    function __construct($fileName)
    {
        if (!$this->testGDInstalled()) {
            throw new PrestaShopException('The GD Library is not installed.');
        }
        $this->initialise();
        $this->fileName = $fileName;
        $this->fileExtension = fix_strtolower(strrchr($fileName, '.'));
        $this->image = $this->openImage($fileName);
        $this->imageResized = $this->image;
        if ($this->testIsImage($this->image)) {
            $this->width = imagesx($this->image);
            $this->widthOriginal = imagesx($this->image);
            $this->height = imagesy($this->image);
            $this->heightOriginal = imagesy($this->image);
        } else {
            $this->errorArray[] = 'File is not an image';
        }
    }

    /**
     * @return void
     */
    private function initialise()
    {
        $this->filterOverlayPath = dirname(__FILE__).'/filters';
        $this->isInterlace = false;
    }

    /**
     * @param int $newWidth
     * @param int $newHeight
     * @param int $option
     * @param bool $sharpen
     * @param bool $autoRotate
     * @return void
     * @throws PrestaShopException
     */
    public function resizeImage($newWidth, $newHeight, $option = 0, $sharpen = false, $autoRotate = false)
    {
        $cropPos = 'm';
        if (is_array($option) && fix_strtolower($option[0]) == 'crop') {
            $cropPos = $option[1];
        } else {
            if (strpos($option, '-') !== false) {
                $optionPiecesArray = explode('-', $option);
                $cropPos = end($optionPiecesArray);
            }
        }
        $option = $this->prepOption($option);
        if (!$this->image) {
            throw new PrestaShopException('file '.$this->getFileName().' is missing or invalid');
        }
        $dimensionsArray = $this->getDimensions($newWidth, $newHeight, $option);
        $optimalWidth = $dimensionsArray['optimalWidth'];
        $optimalHeight = $dimensionsArray['optimalHeight'];
        $this->imageResized = imagecreatetruecolor($optimalWidth, $optimalHeight);
        $this->keepTransparancy($optimalWidth, $optimalHeight, $this->imageResized);
        imagecopyresampled($this->imageResized, $this->image, 0, 0, 0, 0, $optimalWidth, $optimalHeight, $this->width, $this->height);
        if ($option == 4 || $option == 'crop') {
            if (($optimalWidth >= $newWidth && $optimalHeight >= $newHeight)) {
                $this->crop($optimalWidth, $optimalHeight, $newWidth, $newHeight, $cropPos);
            }
        }
        if ($autoRotate) {
            $exifData = $this->getExif();
            if (count($exifData) > 0) {
                switch ($exifData['orientation']) {
                    case 8:
                        $this->imageResized = imagerotate($this->imageResized, 90, 0);
                        break;
                    case 3:
                        $this->imageResized = imagerotate($this->imageResized, 180, 0);
                        break;
                    case 6:
                        $this->imageResized = imagerotate($this->imageResized, -90, 0);
                        break;
                }
            }
        }
        if ($sharpen && in_array($this->fileExtension, $this->sharpenArray)) {
            $this->sharpen();
        }
    }

    /**
     * @param int $newWidth
     * @param int $newHeight
     * @param string $cropPos
     * @return void
     * @throws PrestaShopException
     */
    public function cropImage($newWidth, $newHeight, $cropPos = 'm')
    {
        if (!$this->image) {
            throw new PrestaShopException('file '.$this->getFileName().' is missing or invalid');
        }
        $this->imageResized = $this->image;
        $this->crop($this->width, $this->height, $newWidth, $newHeight, $cropPos);
    }

    /**
     * @param int $width
     * @param int $height
     * @param GdImage $im
     * @return void
     */
    private function keepTransparancy($width, $height, $im)
    {
        if (in_array($this->fileExtension, $this->transparentArray) && $this->keepTransparency) {
            imagealphablending($im, false);
            imagesavealpha($im, true);
            $transparent = imagecolorallocatealpha($im, 255, 255, 255, 127);
            imagefilledrectangle($im, 0, 0, $width, $height, $transparent);
        } else {
            $color = imagecolorallocate($im, $this->fillColorArray['r'], $this->fillColorArray['g'], $this->fillColorArray['b']);
            imagefilledrectangle($im, 0, 0, $width, $height, $color);
        }
    }

    /**
     * @param int $optimalWidth
     * @param int $optimalHeight
     * @param int $newWidth
     * @param int $newHeight
     * @param string $cropPos
     * @return void
     */
    private function crop($optimalWidth, $optimalHeight, $newWidth, $newHeight, $cropPos)
    {
        $cropArray = $this->getCropPlacing($optimalWidth, $optimalHeight, $newWidth, $newHeight, $cropPos);
        $cropStartX = (int) $cropArray['x'];
        $cropStartY = (int) $cropArray['y'];
        $crop = imagecreatetruecolor($newWidth, $newHeight);
        $this->keepTransparancy($optimalWidth, $optimalHeight, $crop);
        imagecopyresampled($crop, $this->imageResized, 0, 0, $cropStartX, $cropStartY, $newWidth, $newHeight, $newWidth, $newHeight);
        $this->imageResized = $crop;
        $this->width = $newWidth;
        $this->height = $newHeight;
    }

    /**
     * @param int $optimalWidth
     * @param int $optimalHeight
     * @param int $newWidth
     * @param int $newHeight
     * @param string $pos
     * @return array
     */
    private function getCropPlacing($optimalWidth, $optimalHeight, $newWidth, $newHeight, $pos = 'm')
    {
        $pos = fix_strtolower($pos);
        if (strstr($pos, 'x')) {
            $pos = str_replace(' ', '', $pos);
            $xyArray = explode('x', $pos);
            list($cropStartX, $cropStartY) = $xyArray;
        } else {
            switch ($pos) {
                case 'tl':
                    $cropStartX = 0;
                    $cropStartY = 0;
                    break;
                case 't':
                    $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                    $cropStartY = 0;
                    break;
                case 'tr':
                    $cropStartX = $optimalWidth - $newWidth;
                    $cropStartY = 0;
                    break;
                case 'l':
                    $cropStartX = 0;
                    $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    break;
                case 'm':
                    $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                    $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    break;
                case 'r':
                    $cropStartX = $optimalWidth - $newWidth;
                    $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    break;
                case 'bl':
                    $cropStartX = 0;
                    $cropStartY = $optimalHeight - $newHeight;
                    break;
                case 'b':
                    $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                    $cropStartY = $optimalHeight - $newHeight;
                    break;
                case 'br':
                    $cropStartX = $optimalWidth - $newWidth;
                    $cropStartY = $optimalHeight - $newHeight;
                    break;
                case 'auto':
                    if ($optimalHeight > $optimalWidth) {
                        $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                        $cropStartY = ($this->cropFromTopPercent / 100) * $optimalHeight;
                    } else {
                        $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                        $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    }
                    break;
                default:
                    $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                    $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    break;
            }
        }
        return ['x' => $cropStartX, 'y' => $cropStartY];
    }

    /**
     * @param int $newWidth
     * @param int $newHeight
     * @param string $option
     * @return array
     */
    private function getDimensions($newWidth, $newHeight, $option)
    {
        switch (strval($option)) {
            case '0':
            case 'exact':
                $optimalWidth = $newWidth;
                $optimalHeight = $newHeight;
                break;
            case '1':
            case 'portrait':
                $dimensionsArray = $this->getSizeByFixedHeight($newWidth, $newHeight);
                $optimalWidth = $dimensionsArray['optimalWidth'];
                $optimalHeight = $dimensionsArray['optimalHeight'];
                break;
            case '2':
            case 'landscape':
                $dimensionsArray = $this->getSizeByFixedWidth($newWidth, $newHeight);
                $optimalWidth = $dimensionsArray['optimalWidth'];
                $optimalHeight = $dimensionsArray['optimalHeight'];
                break;
            case '3':
            case 'auto':
                $dimensionsArray = $this->getSizeByAuto($newWidth, $newHeight);
                $optimalWidth = $dimensionsArray['optimalWidth'];
                $optimalHeight = $dimensionsArray['optimalHeight'];
                break;
            case '4':
            case 'crop':
                $dimensionsArray = $this->getOptimalCrop($newWidth, $newHeight);
                $optimalWidth = $dimensionsArray['optimalWidth'];
                $optimalHeight = $dimensionsArray['optimalHeight'];
                break;
            default:
                $optimalWidth = $newWidth;
                $optimalHeight = $newHeight;
                break;
        }
        return [
            'optimalWidth' => $optimalWidth,
            'optimalHeight' => $optimalHeight
        ];
    }

    /**
     * @param int $newWidth
     * @param int $newHeight
     * @return array
     */
    private function getSizeByFixedHeight($newWidth, $newHeight)
    {
        if (!$this->forceStretch) {
            if ($this->height < $newHeight) {
                return ['optimalWidth' => $this->width, 'optimalHeight' => $this->height];
            }
        }
        $ratio = $this->width / $this->height;
        $newWidth = $newHeight * $ratio;
        return ['optimalWidth' => $newWidth, 'optimalHeight' => $newHeight];
    }

    /**
     * @param int $newWidth
     * @param int $newHeight
     * @return array
     */
    private function getSizeByFixedWidth($newWidth, $newHeight)
    {
        if (!$this->forceStretch) {
            if ($this->width < $newWidth) {
                return ['optimalWidth' => $this->width, 'optimalHeight' => $this->height];
            }
        }
        $ratio = $this->height / $this->width;
        $newHeight = $newWidth * $ratio;
        return ['optimalWidth' => $newWidth, 'optimalHeight' => $newHeight];
    }

    /**
     * @param int $newWidth
     * @param int $newHeight
     * @return array
     */
    private function getSizeByAuto($newWidth, $newHeight)
    {
        if (!$this->forceStretch) {
            if ($this->width < $newWidth && $this->height < $newHeight) {
                return ['optimalWidth' => $this->width, 'optimalHeight' => $this->height];
            }
        }
        if ($this->height < $this->width) {
            $dimensionsArray = $this->getSizeByFixedWidth($newWidth, $newHeight);
            $optimalWidth = $dimensionsArray['optimalWidth'];
            $optimalHeight = $dimensionsArray['optimalHeight'];
        } elseif ($this->height > $this->width) {
            $dimensionsArray = $this->getSizeByFixedHeight($newWidth, $newHeight);
            $optimalWidth = $dimensionsArray['optimalWidth'];
            $optimalHeight = $dimensionsArray['optimalHeight'];
        } else {
            if ($newHeight < $newWidth) {
                $dimensionsArray = $this->getSizeByFixedWidth($newWidth, $newHeight);
                $optimalWidth = $dimensionsArray['optimalWidth'];
                $optimalHeight = $dimensionsArray['optimalHeight'];
            } else {
                if ($newHeight > $newWidth) {
                    $dimensionsArray = $this->getSizeByFixedHeight($newWidth, $newHeight);
                    $optimalWidth = $dimensionsArray['optimalWidth'];
                    $optimalHeight = $dimensionsArray['optimalHeight'];
                } else {
                    $optimalWidth = $newWidth;
                    $optimalHeight = $newHeight;
                }
            }
        }
        return ['optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight];
    }

    /**
     * @param int $newWidth
     * @param int $newHeight
     * @return array
     */
    private function getOptimalCrop($newWidth, $newHeight)
    {
        if (!$this->forceStretch) {
            if ($this->width < $newWidth && $this->height < $newHeight) {
                return ['optimalWidth' => $this->width, 'optimalHeight' => $this->height];
            }
        }
        $heightRatio = $this->height / $newHeight;
        $widthRatio = $this->width / $newWidth;
        $optimalRatio = min($heightRatio, $widthRatio);
        $optimalHeight = round($this->height / $optimalRatio);
        $optimalWidth = round($this->width / $optimalRatio);
        return ['optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight];
    }

    /**
     * @return void
     */
    private function sharpen()
    {
        if ($this->aggresiveSharpening) {
            $sharpenMatrix = [
                [-1, -1, -1],
                [-1, 16, -1],
                [-1, -1, -1]
            ];
            $divisor = 8;
            $offset = 0;
            imageconvolution($this->imageResized, $sharpenMatrix, $divisor, $offset);
        } else {
            $sharpness = $this->findSharp($this->widthOriginal, $this->width);
            $sharpenMatrix = [
                [-1, -2, -1 ],
                [-2, $sharpness + 12, -2],
                [-1, -2, -1]
            ];
            $divisor = $sharpness;
            $offset = 0;
            imageconvolution($this->imageResized, $sharpenMatrix, $divisor, $offset);
        }
    }

    /**
     * @param float $orig
     * @param float $final
     * @return float
     */
    private function findSharp($orig, $final)
    {
        $final = $final * (750.0 / $orig);
        $a = 52;
        $b = -0.27810650887573124;
        $c = .00047337278106508946;
        $result = $a + $b * $final + $c * $final * $final;
        return max(round($result), 0);
    }

    /**
     * @param array|string $option
     * @return string
     * @throws PrestaShopException
     */
    private function prepOption($option)
    {
        if (is_array($option)) {
            if (fix_strtolower($option[0]) == 'crop' && count($option) == 2) {
                return 'crop';
            } else {
                throw new PrestaShopException('Crop resize option array is badly formatted.');
            }
        } else {
            if (strpos($option, 'crop') !== false) {
                return 'crop';
            }
        }
        if (is_string($option)) {
            return fix_strtolower($option);
        }
        /** @var string $option */
        return $option;
    }

    /**
     * @param string $preset
     * @return void
     */
    public function borderPreset($preset)
    {
        switch ($preset) {
            case 'simple':
                $this->addBorder(7, '#fff');
                $this->addBorder(6, '#f2f1f0');
                $this->addBorder(2, '#fff');
                $this->addBorder(1, '#ccc');
                break;
            default:
                break;
        }
    }

    /**
     * @param int $thickness
     * @param array $rgbArray
     * @return void
     */
    public function addBorder($thickness = 1, $rgbArray = [255, 255, 255])
    {
        if ($this->imageResized) {
            $rgbArray = $this->formatColor($rgbArray);
            $r = $rgbArray['r'];
            $g = $rgbArray['g'];
            $b = $rgbArray['b'];
            $x1 = 0;
            $y1 = 0;
            $x2 = ImageSX($this->imageResized) - 1;
            $y2 = ImageSY($this->imageResized) - 1;
            $rgbArray = ImageColorAllocate($this->imageResized, $r, $g, $b);
            for ($i = 0; $i < $thickness; $i++) {
                ImageRectangle($this->imageResized, $x1++, $y1++, $x2--, $y2--, $rgbArray);
            }
        }
    }

    /**
     * @return void
     */
    public function greyScale()
    {
        if ($this->imageResized) {
            imagefilter($this->imageResized, IMG_FILTER_GRAYSCALE);
        }
    }

    /**
     * @return void
     */
    public function greyScaleEnhanced()
    {
        if ($this->imageResized) {
            imagefilter($this->imageResized, IMG_FILTER_GRAYSCALE);
            imagefilter($this->imageResized, IMG_FILTER_CONTRAST, -15);
            imagefilter($this->imageResized, IMG_FILTER_BRIGHTNESS, 2);
            $this->sharpen();
        }
    }

    /**
     * @return void
     */
    public function greyScaleDramatic()
    {
        $this->gd_filter_monopin();
    }

    /**
     * @return void
     */
    public function blackAndWhite()
    {
        if ($this->imageResized) {
            imagefilter($this->imageResized, IMG_FILTER_GRAYSCALE);
            imagefilter($this->imageResized, IMG_FILTER_CONTRAST, -1000);
        }
    }

    /**
     * @return void
     */
    public function negative()
    {
        if ($this->imageResized) {
            imagefilter($this->imageResized, IMG_FILTER_NEGATE);
        }
    }

    /**
     * @return void
     */
    public function sepia()
    {
        if ($this->imageResized) {
            imagefilter($this->imageResized, IMG_FILTER_GRAYSCALE);
            imagefilter($this->imageResized, IMG_FILTER_BRIGHTNESS, -10);
            imagefilter($this->imageResized, IMG_FILTER_CONTRAST, -20);
            imagefilter($this->imageResized, IMG_FILTER_COLORIZE, 60, 30, -15);
        }
    }

    /**
     * @return void
     */
    public function sepia2()
    {
        if ($this->imageResized) {
            $total = imagecolorstotal($this->imageResized);
            for ($i = 0; $i < $total; $i++) {
                $index = imagecolorsforindex($this->imageResized, $i);
                $red = ($index["red"] * 0.393 + $index["green"] * 0.769 + $index["blue"] * 0.189) / 1.351;
                $green = ($index["red"] * 0.349 + $index["green"] * 0.686 + $index["blue"] * 0.168) / 1.203;
                $blue = ($index["red"] * 0.272 + $index["green"] * 0.534 + $index["blue"] * 0.131) / 2.140;
                imagecolorset($this->imageResized, $i, $red, $green, $blue);
            }
        }
    }

    /**
     * @return void
     */
    public function vintage()
    {
        $this->gd_filter_vintage();
    }

    /**
     * @return void
     */
    public function gd_filter_monopin()
    {
        if ($this->imageResized) {
            imagefilter($this->imageResized, IMG_FILTER_GRAYSCALE);
            imagefilter($this->imageResized, IMG_FILTER_BRIGHTNESS, -15);
            imagefilter($this->imageResized, IMG_FILTER_CONTRAST, -15);
            $this->imageResized = $this->gd_apply_overlay($this->imageResized, 'vignette', 100);
        }
    }

    /**
     * @return void
     */
    public function gd_filter_vintage()
    {
        if ($this->imageResized) {
            $this->imageResized = $this->gd_apply_overlay($this->imageResized, 'vignette', 45);
            imagefilter($this->imageResized, IMG_FILTER_BRIGHTNESS, 20);
            imagefilter($this->imageResized, IMG_FILTER_CONTRAST, -35);
            imagefilter($this->imageResized, IMG_FILTER_COLORIZE, 60, -10, 35);
            imagefilter($this->imageResized, IMG_FILTER_SMOOTH, 7);
            $this->imageResized = $this->gd_apply_overlay($this->imageResized, 'scratch', 10);
        }
    }

    /**
     * @param GdImage $im
     * @param string $type
     * @param int $amount
     * @return GdImage
     */
    private function gd_apply_overlay($im, $type, $amount)
    {
        $width = imagesx($im);
        $height = imagesy($im);
        $filter = imagecreatetruecolor($width, $height);
        imagealphablending($filter, false);
        imagesavealpha($filter, true);
        $transparent = imagecolorallocatealpha($filter, 255, 255, 255, 127);
        imagefilledrectangle($filter, 0, 0, $width, $height, $transparent);
        $overlay = $this->filterOverlayPath.'/'.$type.'.png';
        $png = imagecreatefrompng($overlay);
        imagecopyresampled($filter, $png, 0, 0, 0, 0, $width, $height, imagesx($png), imagesy($png));
        $comp = imagecreatetruecolor($width, $height);
        imagecopy($comp, $im, 0, 0, 0, 0, $width, $height);
        imagecopy($comp, $filter, 0, 0, 0, 0, $width, $height);
        imagecopymerge($im, $comp, 0, 0, 0, 0, $width, $height, $amount);
        imagedestroy($comp);
        return $im;
    }

    /**
     * @param array $rgb
     * @return bool
     */
    public function image_colorize($rgb)
    {
        imageTrueColorToPalette($this->imageResized, true, 256);
        $numColors = imageColorsTotal($this->imageResized);
        for ($x = 0; $x < $numColors; $x++) {
            list($r, $g, $b) = array_values(imageColorsForIndex($this->imageResized, $x));
            $grayscale = ($r + $g + $b) / 3 / 0xff;
            imageColorSet($this->imageResized, $x,
                $grayscale * $rgb[0],
                $grayscale * $rgb[1],
                $grayscale * $rgb[2]
            );
        }
        return true;
    }

    /**
     * @param int $reflectionHeight
     * @param int $startingTransparency
     * @param false $inside
     * @param string $bgColor
     * @param bool $stretch
     * @param int $divider
     * @return void
     */
    public function addReflection($reflectionHeight = 50, $startingTransparency = 30, $inside = false, $bgColor = '#fff', $stretch = false, $divider = 0)
    {
        $rgbArray = $this->formatColor($bgColor);
        $r = $rgbArray['r'];
        $g = $rgbArray['g'];
        $b = $rgbArray['b'];
        $im = $this->imageResized;
        $li = imagecreatetruecolor($this->width, 1);
        $bgc = imagecolorallocate($li, $r, $g, $b);
        imagefilledrectangle($li, 0, 0, $this->width, 1, $bgc);
        $bg = imagecreatetruecolor($this->width, $reflectionHeight);
        $wh = imagecolorallocate($im, 255, 255, 255);
        $im = imagerotate($im, -180, $wh);
        imagecopyresampled($bg, $im, 0, 0, 0, 0, $this->width, $this->height, $this->width, $this->height);
        $im = $bg;
        $bg = imagecreatetruecolor($this->width, $reflectionHeight);
        for ($x = 0; $x < $this->width; $x++) {
            imagecopy($bg, $im, $x, 0, $this->width - $x - 1, 0, 1, $reflectionHeight);
        }
        $im = $bg;
        if ($stretch) {
            $step = 100 / ($reflectionHeight + $startingTransparency);
        } else {
            $step = 100 / $reflectionHeight;
        }
        for ($i = 0; $i <= $reflectionHeight; $i++) {
            if ($startingTransparency > 100) {
                $startingTransparency = 100;
            }
            if ($startingTransparency < 1) {
                $startingTransparency = 1;
            }
            imagecopymerge($bg, $li, 0, $i, 0, 0, $this->width, 1, $startingTransparency);
            $startingTransparency += $step;
        }
        imagecopymerge($im, $li, 0, 0, 0, 0, $this->width, $divider, 100);
        $x = imagesx($im);
        $y = imagesy($im);
        if ($inside) {
            $final = imagecreatetruecolor($this->width, $this->height);
            imagecopymerge($final, $this->imageResized, 0, 0, 0, $reflectionHeight, $this->width, $this->height - $reflectionHeight, 100);
            imagecopymerge($final, $im, 0, $this->height - $reflectionHeight, 0, 0, $x, $y, 100);
        } else {
            $final = imagecreatetruecolor($this->width, $this->height + $y);
            imagecopymerge($final, $this->imageResized, 0, 0, 0, 0, $this->width, $this->height, 100);
            imagecopymerge($final, $im, 0, $this->height, 0, 0, $x, $y, 100);
        }
        $this->imageResized = $final;
        imagedestroy($li);
        imagedestroy($im);
    }

    /**
     * @param int $value
     * @param string $bgColor
     * @return void
     */
    public function rotate($value = 90, $bgColor = 'transparent')
    {
        if ($this->imageResized) {
            $degrees = (int)$value;
            $rgbArray = $this->formatColor($bgColor);
            $r = $rgbArray['r'];
            $g = $rgbArray['g'];
            $b = $rgbArray['b'];
            $a = $rgbArray['a'] ?? 0;
            if (is_string($value)) {
                $value = fix_strtolower($value);
                switch ($value) {
                    case 'left':
                        $degrees = 90;
                        break;
                    case 'right':
                        $degrees = 270;
                        break;
                    case 'upside':
                        $degrees = 180;
                        break;
                    default:
                        break;
                }
            }
            $degrees = 360 - $degrees;
            $bg = ImageColorAllocateAlpha($this->imageResized, $r, $g, $b, $a);
            ImageFill($this->imageResized, 0, 0, $bg);
            $this->imageResized = imagerotate($this->imageResized, $degrees, $bg);
            ImageSaveAlpha($this->imageResized, true);
        }
    }

    /**
     * @param int $radius
     * @param string|array $bgColor
     * @return void
     */
    public function roundCorners($radius = 5, $bgColor = 'transparent')
    {
        $isTransparent = false;
        if (!is_array($bgColor)) {
            if (fix_strtolower($bgColor) == 'transparent') {
                $isTransparent = true;
            }
        }
        if ($isTransparent) {
            $bgColor = $this->findUnusedGreen();
        }
        $rgbArray = $this->formatColor($bgColor);
        $r = $rgbArray['r'];
        $g = $rgbArray['g'];
        $b = $rgbArray['b'];
        $cornerImg = imagecreatetruecolor($radius, $radius);
        $maskColor = imagecolorallocate($cornerImg, 0, 0, 0);
        imagecolortransparent($cornerImg, $maskColor);
        $imagebgColor = imagecolorallocate($cornerImg, $r, $g, $b);
        imagefill($cornerImg, 0, 0, $imagebgColor);
        imagefilledellipse($cornerImg, $radius, $radius, $radius * 2, $radius * 2, $maskColor);
        imagecopymerge($this->imageResized, $cornerImg, 0, 0, 0, 0, $radius, $radius, 100);
        $cornerImg = imagerotate($cornerImg, 90, 0);
        imagecopymerge($this->imageResized, $cornerImg, 0, $this->height - $radius, 0, 0, $radius, $radius, 100);
        $cornerImg = imagerotate($cornerImg, 90, 0);
        imagecopymerge($this->imageResized, $cornerImg, $this->width - $radius, $this->height - $radius, 0, 0, $radius, $radius, 100);
        $cornerImg = imagerotate($cornerImg, 90, 0);
        imagecopymerge($this->imageResized, $cornerImg, $this->width - $radius, 0, 0, 0, $radius, $radius, 100);
        if ($isTransparent) {
            $this->imageResized = $this->transparentImage($this->imageResized);
            imagesavealpha($this->imageResized, true);
        }
    }

    /**
     * @param int $shadowAngle
     * @param int $blur
     * @param string|array $bgColor
     * @return void
     */
    public function addShadow($shadowAngle = 45, $blur = 15, $bgColor = 'transparent')
    {
        define('STEPS', $blur * 2);
        $shadowDistance = $blur * 0.25;
        $blurWidth = $blurHeight = $blur;
        if ($shadowAngle == 0) {
            $distWidth = 0;
            $distHeight = 0;
        } else {
            $distWidth = $shadowDistance * cos(deg2rad($shadowAngle));
            $distHeight = $shadowDistance * sin(deg2rad($shadowAngle));
        }
        if (fix_strtolower($bgColor) != 'transparent') {
            $rgbArray = $this->formatColor($bgColor);
            $r0 = $rgbArray['r'];
            $g0 = $rgbArray['g'];
            $b0 = $rgbArray['b'];
        } else {
            $r0 = 0;
            $g0 = 0;
            $b0 = 0;
        }
        $image = $this->imageResized;
        $width = $this->width;
        $height = $this->height;
        $newImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $width, $height);
        $rgb = imagecreatetruecolor($width + $blurWidth, $height + $blurHeight);
        $colour = imagecolorallocate($rgb, 0, 0, 0);
        imagefilledrectangle($rgb, 0, 0, $width + $blurWidth, $height + $blurHeight, $colour);
        $colour = imagecolorallocate($rgb, 255, 255, 255);
        imagefilledrectangle($rgb, $blurWidth * 0.5 - $distWidth, $blurHeight * 0.5 - $distHeight, $width + $blurWidth * 0.5 - $distWidth, $height + $blurWidth * 0.5 - $distHeight, $colour);
        imagecopymerge($rgb, $newImage, $blurWidth * 0.5 - $distWidth, $blurHeight * 0.5 - $distHeight, 0, 0, $width + $blurWidth, $height + $blurHeight, 100);
        $shadow = imagecreatetruecolor($width + $blurWidth, $height + $blurHeight);
        imagealphablending($shadow, false);
        $colour = imagecolorallocate($shadow, 0, 0, 0);
        imagefilledrectangle($shadow, 0, 0, $width + $blurWidth, $height + $blurHeight, $colour);
        for ($i = 0; $i <= STEPS; $i++) {
            $t = ((1.0 * $i) / STEPS);
            $intensity = 255 * $t * $t;
            $colour = imagecolorallocate($shadow, $intensity, $intensity, $intensity);
            $points = [
                $blurWidth * $t, $blurHeight,
                $blurWidth, $blurHeight * $t,
                $width, $blurHeight * $t,
                $width + $blurWidth * (1 - $t), $blurHeight,
                $width + $blurWidth * (1 - $t), $height,
                $width, $height + $blurHeight * (1 - $t),
                $blurWidth, $height + $blurHeight * (1 - $t),
                $blurWidth * $t,
                $height
            ];
            imagepolygon($shadow, $points, 8, $colour);
        }
        for ($i = 0; $i <= STEPS; $i++) {
            $t = ((1.0 * $i) / STEPS);
            $intensity = 255 * $t * $t;
            $colour = imagecolorallocate($shadow, $intensity, $intensity, $intensity);
            imagefilledarc($shadow, $blurWidth - 1, $blurHeight - 1, 2 * (1 - $t) * $blurWidth, 2 * (1 - $t) * $blurHeight, 180, 268, $colour, IMG_ARC_PIE);
            imagefilledarc($shadow, $width, $blurHeight - 1, 2 * (1 - $t) * $blurWidth, 2 * (1 - $t) * $blurHeight, 270, 358, $colour, IMG_ARC_PIE);
            imagefilledarc($shadow, $width, $height, 2 * (1 - $t) * $blurWidth, 2 * (1 - $t) * $blurHeight, 0, 90, $colour, IMG_ARC_PIE);
            imagefilledarc($shadow, $blurWidth - 1, $height, 2 * (1 - $t) * $blurWidth, 2 * (1 - $t) * $blurHeight, 90, 180, $colour, IMG_ARC_PIE);
        }
        $colour = imagecolorallocate($shadow, 255, 255, 255);
        imagefilledrectangle($shadow, $blurWidth, $blurHeight, $width, $height, $colour);
        imagefilledrectangle($shadow, $blurWidth * 0.5 - $distWidth, $blurHeight * 0.5 - $distHeight, $width + $blurWidth * 0.5 - 1 - $distWidth, $height + $blurHeight * 0.5 - 1 - $distHeight, $colour);
        imagealphablending($rgb, false);
        for ($theX = 0; $theX < imagesx($rgb); $theX++) {
            for ($theY = 0; $theY < imagesy($rgb); $theY++) {
                $colArray = imagecolorat($rgb, $theX, $theY);
                $r = ($colArray >> 16) & 0xFF;
                $g = ($colArray >> 8) & 0xFF;
                $b = $colArray & 0xFF;
                $colArray = imagecolorat($shadow, $theX, $theY);
                $a = $colArray & 0xFF;
                $a = 127 - floor($a / 2);
                $t = $a / 128.0;
                if (fix_strtolower($bgColor) == 'transparent') {
                    $myColour = imagecolorallocatealpha($rgb, $r, $g, $b, $a);
                } else {
                    $myColour = imagecolorallocate($rgb, $r * (1.0 - $t) + $r0 * $t, $g * (1.0 - $t) + $g0 * $t, $b * (1.0 - $t) + $b0 * $t);
                }
                imagesetpixel($rgb, $theX, $theY, $myColour);
            }
        }
        imagealphablending($rgb, true);
        imagesavealpha($rgb, true);
        $this->imageResized = $rgb;
        imagedestroy($image);
        imagedestroy($newImage);
        imagedestroy($shadow);
    }

    /**
     * @param string $side
     * @param int $thickness
     * @param int $padding
     * @param string|array $bgColor
     * @param int $transaprencyAmount
     * @return void
     */
    public function addCaptionBox($side = 'b', $thickness = 50, $padding = 0, $bgColor = '#000', $transaprencyAmount = 30)
    {
        $side = fix_strtolower($side);
        $rgbArray = $this->formatColor($bgColor);
        $r = $rgbArray['r'];
        $g = $rgbArray['g'];
        $b = $rgbArray['b'];
        $positionArray = $this->calculateCaptionBoxPosition($side, $thickness, $padding);
        $this->captionBoxPositionArray = $positionArray;
        $transaprencyAmount = $this->invertTransparency($transaprencyAmount, 127, false);
        $transparent = imagecolorallocatealpha($this->imageResized, $r, $g, $b, $transaprencyAmount);
        imagefilledrectangle($this->imageResized, $positionArray['x1'], $positionArray['y1'], $positionArray['x2'], $positionArray['y2'], $transparent);
    }

    /**
     * @param string $text
     * @param string $fontColor
     * @param int $fontSize
     * @param int $angle
     * @param string|null $font
     * @return void
     * @throws PrestaShopException
     */
    public function addTextToCaptionBox($text, $fontColor = '#fff', $fontSize = 12, $angle = 0, $font = null)
    {
        if (count($this->captionBoxPositionArray) == 4) {
            $x1 = $this->captionBoxPositionArray['x1'];
            $x2 = $this->captionBoxPositionArray['x2'];
            $y1 = $this->captionBoxPositionArray['y1'];
            $y2 = $this->captionBoxPositionArray['y2'];
        } else {
            throw new PrestaShopException('No caption box found.');
        }
        $font = $this->getTextFont($font);
        $textSizeArray = $this->getTextSize($fontSize, $angle, $font, $text);
        $textWidth = $textSizeArray['width'];
        $textHeight = $textSizeArray['height'];
        $boxXMiddle = (($x2 - $x1) / 2);
        $boxYMiddle = (($y2 - $y1) / 2);
        $xPos = ($x1 + $boxXMiddle) - ($textWidth / 2);
        $yPos = ($y1 + $boxYMiddle) - ($textHeight / 2);
        $pos = $xPos.'x'.$yPos;
        $this->addText($text, $pos, 0, $fontColor, $fontSize, $angle, $font);
    }

    /**
     * @param string $side
     * @param int $thickness
     * @param int $padding
     * @return array
     */
    private function calculateCaptionBoxPosition($side, $thickness, $padding)
    {
        $positionArray = [];
        switch ($side) {
            case 't':
                $positionArray['x1'] = 0;
                $positionArray['y1'] = $padding;
                $positionArray['x2'] = $this->width;
                $positionArray['y2'] = $thickness + $padding;
                break;
            case 'r':
                $positionArray['x1'] = $this->width - $thickness - $padding;
                $positionArray['y1'] = 0;
                $positionArray['x2'] = $this->width - $padding;
                $positionArray['y2'] = $this->height;
                break;
            case 'b':
                $positionArray['x1'] = 0;
                $positionArray['y1'] = $this->height - $thickness - $padding;
                $positionArray['x2'] = $this->width;
                $positionArray['y2'] = $this->height - $padding;
                break;
            case 'l':
                $positionArray['x1'] = $padding;
                $positionArray['y1'] = 0;
                $positionArray['x2'] = $thickness + $padding;
                $positionArray['y2'] = $this->height;
                break;
            default:
                break;
        }
        return $positionArray;
    }

    /**
     * @param bool $debug
     * @return array
     */
    public function getExif($debug = false)
    {
        if (!$this->testEXIFInstalled()) {
            return [];
        }
        if (!file_exists($this->fileName)) {
            return [];
        }
        if ($this->fileExtension != '.jpg') {
            return [];
        }
        $exifData = exif_read_data($this->fileName, 'IFD0');
        $ev = $exifData['ApertureValue'];
        $apPeicesArray = explode('/', $ev);
        if (count($apPeicesArray) == 2) {
            $apertureValue = round($apPeicesArray[0] / $apPeicesArray[1], 2, PHP_ROUND_HALF_DOWN).' EV';
        } else {
            $apertureValue = '';
        }
        $focalLength = $exifData['FocalLength'];
        $flPeicesArray = explode('/', $focalLength);
        if (count($flPeicesArray) == 2) {
            $focalLength = $flPeicesArray[0] / $flPeicesArray[1].'.0 mm';
        } else {
            $focalLength = '';
        }
        $fNumber = $exifData['FNumber'];
        $fnPeicesArray = explode('/', $fNumber);
        if (count($fnPeicesArray) == 2) {
            $fNumber = $fnPeicesArray[0] / $fnPeicesArray[1];
        } else {
            $fNumber = '';
        }
        if (isset($exifData['ExposureProgram'])) {
            $ep = $exifData['ExposureProgram'];
        }
        if (isset($ep)) {
            $ep = $this->resolveExposureProgram($ep);
        }
        $mm = $exifData['MeteringMode'];
        $mm = $this->resolveMeteringMode($mm);
        $flash = $exifData['Flash'];
        $flash = $this->resolveFlash($flash);
        if (isset($exifData['Make'])) {
            $exifDataArray['make'] = $exifData['Make'];
        } else {
            $exifDataArray['make'] = '';
        }
        if (isset($exifData['Model'])) {
            $exifDataArray['model'] = $exifData['Model'];
        } else {
            $exifDataArray['model'] = '';
        }
        if (isset($exifData['DateTime'])) {
            $exifDataArray['date'] = $exifData['DateTime'];
        } else {
            $exifDataArray['date'] = '';
        }
        if (isset($exifData['ExposureTime'])) {
            $exifDataArray['exposure time'] = $exifData['ExposureTime'].' sec.';
        } else {
            $exifDataArray['exposure time'] = '';
        }
        if ($apertureValue != '') {
            $exifDataArray['aperture value'] = $apertureValue;
        } else {
            $exifDataArray['aperture value'] = '';
        }
        if (isset($exifData['COMPUTED']['ApertureFNumber'])) {
            $exifDataArray['f-stop'] = $exifData['COMPUTED']['ApertureFNumber'];
        } else {
            $exifDataArray['f-stop'] = '';
        }
        if (isset($exifData['FNumber'])) {
            $exifDataArray['fnumber'] = $exifData['FNumber'];
        } else {
            $exifDataArray['fnumber'] = '';
        }
        if ($fNumber != '') {
            $exifDataArray['fnumber value'] = $fNumber;
        } else {
            $exifDataArray['fnumber value'] = '';
        }
        if (isset($exifData['ISOSpeedRatings'])) {
            $exifDataArray['iso'] = $exifData['ISOSpeedRatings'];
        } else {
            $exifDataArray['iso'] = '';
        }
        if ($focalLength != '') {
            $exifDataArray['focal length'] = $focalLength;
        } else {
            $exifDataArray['focal length'] = '';
        }
        if (isset($ep)) {
            $exifDataArray['exposure program'] = $ep;
        } else {
            $exifDataArray['exposure program'] = '';
        }
        if ($mm != '') {
            $exifDataArray['metering mode'] = $mm;
        } else {
            $exifDataArray['metering mode'] = '';
        }
        if ($flash != '') {
            $exifDataArray['flash status'] = $flash;
        } else {
            $exifDataArray['flash status'] = '';
        }
        if (isset($exifData['Artist'])) {
            $exifDataArray['creator'] = $exifData['Artist'];
        } else {
            $exifDataArray['creator'] = '';
        }
        if (isset($exifData['Copyright'])) {
            $exifDataArray['copyright'] = $exifData['Copyright'];
        } else {
            $exifDataArray['copyright'] = '';
        }
        if (isset($exifData['Orientation'])) {
            $exifDataArray['orientation'] = $exifData['Orientation'];
        } else {
            $exifDataArray['orientation'] = '';
        }
        return $exifDataArray;
    }

    /**
     * @param int $ep
     * @return int|string
     */
    private function resolveExposureProgram($ep)
    {
        switch ($ep) {
            case 0:
                $ep = '';
                break;
            case 1:
                $ep = 'manual';
                break;
            case 2:
                $ep = 'normal program';
                break;
            case 3:
                $ep = 'aperture priority';
                break;
            case 4:
                $ep = 'shutter priority';
                break;
            case 5:
                $ep = 'creative program';
                break;
            case 6:
                $ep = 'action program';
                break;
            case 7:
                $ep = 'portrait mode';
                break;
            case 8:
                $ep = 'landscape mode';
                break;
            default:
                break;
        }
        return $ep;
    }

    /**
     * @param int $mm
     * @return int|string
     */
    private function resolveMeteringMode($mm)
    {
        switch ($mm) {
            case 0:
                $mm = 'unknown';
                break;
            case 1:
                $mm = 'average';
                break;
            case 2:
                $mm = 'center weighted average';
                break;
            case 3:
                $mm = 'spot';
                break;
            case 4:
                $mm = 'multi spot';
                break;
            case 5:
                $mm = 'pattern';
                break;
            case 6:
                $mm = 'partial';
                break;
            case 255:
                $mm = 'other';
                break;
            default:
                break;
        }
        return $mm;
    }

    /**
     * @param int $flash
     * @return int|string
     */
    private function resolveFlash($flash)
    {
        switch ($flash) {
            case 0:
                $flash = 'flash did not fire';
                break;
            case 1:
                $flash = 'flash fired';
                break;
            case 5:
                $flash = 'strobe return light not detected';
                break;
            case 7:
                $flash = 'strobe return light detected';
                break;
            case 9:
                $flash = 'flash fired, compulsory flash mode';
                break;
            case 13:
                $flash = 'flash fired, compulsory flash mode, return light not detected';
                break;
            case 15:
                $flash = 'flash fired, compulsory flash mode, return light detected';
                break;
            case 16:
                $flash = 'flash did not fire, compulsory flash mode';
                break;
            case 24:
                $flash = 'flash did not fire, auto mode';
                break;
            case 25:
                $flash = 'flash fired, auto mode';
                break;
            case 29:
                $flash = 'flash fired, auto mode, return light not detected';
                break;
            case 31:
                $flash = 'flash fired, auto mode, return light detected';
                break;
            case 32:
                $flash = 'no flash function';
                break;
            case 65:
                $flash = 'flash fired, red-eye reduction mode';
                break;
            case 69:
                $flash = 'flash fired, red-eye reduction mode, return light not detected';
                break;
            case 71:
                $flash = 'flash fired, red-eye reduction mode, return light detected';
                break;
            case 73:
                $flash = 'flash fired, compulsory flash mode, red-eye reduction mode';
                break;
            case 77:
                $flash = 'flash fired, compulsory flash mode, red-eye reduction mode, return light not detected';
                break;
            case 79:
                $flash = 'flash fired, compulsory flash mode, red-eye reduction mode, return light detected';
                break;
            case 89:
                $flash = 'flash fired, auto mode, red-eye reduction mode';
                break;
            case 93:
                $flash = 'flash fired, auto mode, return light not detected, red-eye reduction mode';
                break;
            case 95:
                $flash = 'flash fired, auto mode, return light detected, red-eye reduction mode';
                break;
            default:
                break;
        }
        return $flash;
    }

    /**
     * @param string $value
     * @return void
     */
    public function writeIPTCcaption($value)
    {
        $this->writeIPTC(120, $value);
    }

    /**
     * @param string $value
     * @return void
     */
    public function writeIPTCwriter($value)
    {
    }

    /**
     * @param int $dat
     * @param string $value
     * @return void
     */
    private function writeIPTC($dat, $value)
    {
        $caption_block = $this->iptc_maketag(2, $dat, $value);
        $image_string = iptcembed($caption_block, $this->fileName);
        file_put_contents('iptc.jpg', $image_string);
    }

    /**
     * @param int $rec
     * @param int $dat
     * @param string $val
     * @return string
     */
    private function iptc_maketag($rec, $dat, $val)
    {
        $len = strlen($val);
        if ($len < 0x8000) {
            return chr(0x1c).chr($rec).chr($dat).
                chr($len >> 8).
                chr($len & 0xff).
                $val;
        } else {
            return chr(0x1c).chr($rec).chr($dat).
                chr(0x80).chr(0x04).
                chr(($len >> 24) & 0xff).
                chr(($len >> 16) & 0xff).
                chr(($len >> 8) & 0xff).
                chr(($len) & 0xff).
                $val;
        }
    }

    /**
     * @param string $text
     * @param string $pos
     * @param int $padding
     * @param string $fontColor
     * @param int $fontSize
     * @param int $angle
     * @param string|null $font
     * @return void
     * @throws PrestaShopException
     */
    public function addText($text, $pos = '20x20', $padding = 0, $fontColor = '#fff', $fontSize = 12, $angle = 0, $font = null)
    {
        $rgbArray = $this->formatColor($fontColor);
        $r = $rgbArray['r'];
        $g = $rgbArray['g'];
        $b = $rgbArray['b'];
        $font = $this->getTextFont($font);
        $textSizeArray = $this->getTextSize($fontSize, $angle, $font, $text);
        $textWidth = $textSizeArray['width'];
        $textHeight = $textSizeArray['height'];
        $posArray = $this->calculatePosition($pos, $padding, $textWidth, $textHeight, false);
        $x = $posArray['width'];
        $y = $posArray['height'];
        $fontColor = imagecolorallocate($this->imageResized, $r, $g, $b);
        imagettftext($this->imageResized, $fontSize, $angle, $x, $y, $fontColor, $font, $text);
    }

    /**
     * @param string $font
     * @return string
     * @throws PrestaShopException
     */
    private function getTextFont($font)
    {
        $fontPath = dirname(__FILE__).'/'.$this->fontDir;
        putenv('GDFONTPATH='.realpath('.'));
        if ($font == null || !file_exists($font)) {
            $font = $fontPath.'/arimo.ttf';
            if (!file_exists($font)) {
                throw new PrestaShopException('Font not found');
            }
        }
        return $font;
    }

    /**
     * @param int $fontSize
     * @param int $angle
     * @param string $font
     * @param string $text
     * @return array
     */
    private function getTextSize($fontSize, $angle, $font, $text)
    {
        $box = @imageTTFBbox($fontSize, $angle, $font, $text);
        $textWidth = abs($box[4] - $box[0]);
        $textHeight = abs($box[5] - $box[1]);
        return ['height' => $textHeight, 'width' => $textWidth];
    }

    /**
     * @param string $watermarkImage
     * @param string $pos
     * @param int $padding
     * @param int $opacity
     * @return void
     * @throws PrestaShopException
     */
    public function addWatermark($watermarkImage, $pos, $padding = 0, $opacity = 0)
    {
        $stamp = $this->openImage($watermarkImage);
        $im = $this->imageResized;
        $sx = imagesx($stamp);
        $sy = imagesy($stamp);
        $posArray = $this->calculatePosition($pos, $padding, $sx, $sy);
        $x = $posArray['width'];
        $y = $posArray['height'];
        if (fix_strtolower(strrchr($watermarkImage, '.')) == '.png') {
            $opacity = $this->invertTransparency($opacity, 100);
            $this->filterOpacity($stamp, $opacity);
        }
        imagecopy($im, $stamp, $x, $y, 0, 0, imagesx($stamp), imagesy($stamp));
    }

    /**
     * @param string $pos
     * @param int $padding
     * @param int $assetWidth
     * @param int $assetHeight
     * @param bool $upperLeft
     * @return array
     */
    private function calculatePosition($pos, $padding, $assetWidth, $assetHeight, $upperLeft = true)
    {
        $pos = fix_strtolower($pos);
        if (strstr($pos, 'x')) {
            $pos = str_replace(' ', '', $pos);
            $xyArray = explode('x', $pos);
            list($width, $height) = $xyArray;
        } else {
            switch ($pos) {
                case 'tl':
                    $width = 0 + $padding;
                    $height = 0 + $padding;
                    break;
                case 't':
                    $width = ($this->width / 2) - ($assetWidth / 2);
                    $height = 0 + $padding;
                    break;
                case 'tr':
                    $width = $this->width - $assetWidth - $padding;
                    $height = 0 + $padding;
                    break;
                case 'l':
                    $width = 0 + $padding;
                    $height = ($this->height / 2) - ($assetHeight / 2);
                    break;
                case 'm':
                    $width = ($this->width / 2) - ($assetWidth / 2);
                    $height = ($this->height / 2) - ($assetHeight / 2);
                    break;
                case 'r':
                    $width = $this->width - $assetWidth - $padding;
                    $height = ($this->height / 2) - ($assetHeight / 2);
                    break;
                case 'bl':
                    $width = 0 + $padding;
                    $height = $this->height - $assetHeight - $padding;
                    break;
                case 'b':
                    $width = ($this->width / 2) - ($assetWidth / 2);
                    $height = $this->height - $assetHeight - $padding;
                    break;
                case 'br':
                    $width = $this->width - $assetWidth - $padding;
                    $height = $this->height - $assetHeight - $padding;
                    break;
                default:
                    $width = 0;
                    $height = 0;
                    break;
            }
        }
        if (!$upperLeft) {
            $height = $height + $assetHeight;
        }
        return ['width' => $width, 'height' => $height];
    }

    /**
     * @param GdImage $img
     * @param int $opacity
     */
    private function filterOpacity($img, $opacity = 75)
    {
        if (!isset($opacity)) {
            return;
        }
        if ($opacity == 100) {
            return;
        }
        $opacity /= 100;
        $w = imagesx($img);
        $h = imagesy($img);
        imagealphablending($img, false);
        $minalpha = 127;
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $alpha = (imagecolorat($img, $x, $y) >> 24) & 0xFF;
                if ($alpha < $minalpha) {
                    $minalpha = $alpha;
                }
            }
        }
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $colorxy = imagecolorat($img, $x, $y);
                $alpha = ($colorxy >> 24) & 0xFF;
                if ($minalpha !== 127) {
                    $alpha = 127 + 127 * $opacity * ($alpha - 127) / (127 - $minalpha);
                } else {
                    $alpha += 127 * $opacity;
                }
                $alphacolorxy = imagecolorallocatealpha($img, ($colorxy >> 16) & 0xFF, ($colorxy >> 8) & 0xFF, $colorxy & 0xFF, $alpha);
                imagesetpixel($img, $x, $y, $alphacolorxy);
            }
        }
    }

    /**
     * @param string $file
     * @return false|GdImage|resource
     * @throws PrestaShopException
     */
    private function openImage($file)
    {
        if (!file_exists($file) && !$this->checkStringStartsWith('http://', $file) && !$this->checkStringStartsWith('https://', $file)) {
            throw new PrestaShopException('Image not found.');
        }
        $extension = mime_content_type($file);
        $extension = fix_strtolower($extension);
        $extension = str_replace('image/', '', $extension);
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $img = @imagecreatefromjpeg($file);
                break;
            case 'webp':
                $img = @imagecreatefromwebp($file);
                break;
            case 'gif':
                $img = @imagecreatefromgif($file);
                break;
            case 'png':
                $img = @imagecreatefrompng($file);
                break;
            case 'bmp':
            case 'x-ms-bmp':
                $img = @$this->imagecreatefrombmp($file);
                break;
            default:
                $img = false;
                break;
        }
        return $img;
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function reset()
    {
        $this->__construct($this->fileName);
    }

    /**
     * @param string $savePath
     * @param int $imageQuality
     * @return void
     * @throws PrestaShopException
     */
    public function saveImage($savePath, $imageQuality = 100)
    {
        if (! static::isImageResource($this->imageResized)) {
            throw new PrestaShopException('saveImage: This is not a resource.');
        }
        $fileInfoArray = pathInfo($savePath);
        clearstatcache();
        if (!is_writable($fileInfoArray['dirname'])) {
            throw new PrestaShopException('The path is not writable. Please check your permissions.');
        }
        $extension = strrchr($savePath, '.');
        $extension = fix_strtolower($extension);
        $error = '';
        switch ($extension) {
            case '.jpg':
            case '.jpeg':
                $this->checkInterlaceImage($this->isInterlace);
                if (imagetypes() & IMG_JPG) {
                    imagejpeg($this->imageResized, $savePath, $imageQuality);
                } else {
                    $error = 'jpg';
                }
                break;
            case '.webp':
                if (imagetypes() & IMG_WEBP) {
                    imagewebp($this->imageResized, $savePath, $imageQuality);
                } else {
                    $error = 'webp';
                }
                break;
            case '.gif':
                $this->checkInterlaceImage($this->isInterlace);
                if (imagetypes() & IMG_GIF) {
                    imagegif($this->imageResized, $savePath);
                } else {
                    $error = 'gif';
                }
                break;
            case '.png':
                $scaleQuality = round(($imageQuality / 100) * 9);
                $invertScaleQuality = 9 - $scaleQuality;
                $this->checkInterlaceImage($this->isInterlace);
                if (imagetypes() & IMG_PNG) {
                    imagepng($this->imageResized, $savePath, $invertScaleQuality);
                } else {
                    $error = 'png';
                }
                break;
            case '.bmp':
                file_put_contents($savePath, $this->GD2BMPstring($this->imageResized));
                break;
            default:
                $this->errorArray[] = 'This file type ('.$extension.') is not supported. File not saved.';
                break;
        }
        if ($error != '') {
            $this->errorArray[] = $error.' support is NOT enabled. File not saved.';
        }
    }

    /**
     * @param string $fileType
     * @param int $imageQuality
     * @return void
     * @throws PrestaShopException
     */
    public function displayImage($fileType = 'jpg', $imageQuality = 100)
    {
        if (! static::isImageResource($this->imageResized)) {
            throw new PrestaShopException('saveImage: This is not a resource.');
        }
        switch ($fileType) {
            case 'jpg':
            case 'jpeg':
                header('Content-type: image/jpeg');
                imagejpeg($this->imageResized, '', $imageQuality);
                break;
            case 'webp':
                header('Content-type: image/webp');
                imagewebp($this->imageResized, '', $imageQuality);
                break;
            case 'gif':
                header('Content-type: image/gif');
                imagegif($this->imageResized);
                break;
            case 'png':
                header('Content-type: image/png');
                $scaleQuality = round(($imageQuality / 100) * 9);
                $invertScaleQuality = 9 - $scaleQuality;
                imagepng($this->imageResized, '', $invertScaleQuality);
                break;
            case 'bmp':
                echo 'bmp file format is not supported.';
                break;
            default:
                break;
        }
    }

    /**
     * @param bool $bool
     * @return void
     */
    public function setTransparency($bool)
    {
        $this->keepTransparency = $bool;
    }

    /**
     * @param string|array $value
     * @return void
     */
    public function setFillColor($value)
    {
        $colorArray = $this->formatColor($value);
        $this->fillColorArray = $colorArray;
    }

    /**
     * @param int $value
     * @return void
     */
    public function setCropFromTop($value)
    {
        $this->cropFromTopPercent = $value;
    }

    /**
     * @return bool
     */
    public function testGDInstalled()
    {
        if (extension_loaded('gd') && function_exists('gd_info')) {
            $gdInstalled = true;
        } else {
            $gdInstalled = false;
        }
        return $gdInstalled;
    }

    /**
     * @return bool
     */
    public function testEXIFInstalled()
    {
        if (extension_loaded('exif')) {
            $exifInstalled = true;
        } else {
            $exifInstalled = false;
        }
        return $exifInstalled;
    }

    /**
     * @param GdImage $image
     * @return bool
     */
    public function testIsImage($image)
    {
        if ($image) {
            $fileIsImage = true;
        } else {
            $fileIsImage = false;
        }
        return $fileIsImage;
    }

    /**
     * @return void
     */
    public function testFunct()
    {
        echo $this->height;
    }

    /**
     * @param bool $value
     * @return void
     */
    public function setForceStretch($value)
    {
        $this->forceStretch = $value;
    }

    /**
     * @param string $fileName
     * @return void
     * @throws PrestaShopException
     */
    public function setFile($fileName)
    {
        self::__construct($fileName);
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @return false|int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return false|int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return false|int
     */
    public function getOriginalHeight()
    {
        return $this->heightOriginal;
    }

    /**
     * @return false|int
     */
    public function getOriginalWidth()
    {
        return $this->widthOriginal;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errorArray;
    }

    /**
     * @param bool $isEnabled
     * @return void
     */
    private function checkInterlaceImage($isEnabled)
    {
        if ($isEnabled) {
            imageinterlace($this->imageResized, $isEnabled);
        }
    }

    /**
     * @param string|array $value
     * @return array
     */
    protected function formatColor($value)
    {
        $rgbArray = [];
        if (is_array($value)) {
            if (key($value) == 0 && count($value) == 3) {
                $rgbArray['r'] = $value[0];
                $rgbArray['g'] = $value[1];
                $rgbArray['b'] = $value[2];
            } else {
                $rgbArray = $value;
            }
        } else {
            if (fix_strtolower($value) == 'transparent') {
                $rgbArray = [
                    'r' => 255,
                    'g' => 255,
                    'b' => 255,
                    'a' => 127
                ];
            } else {
                $rgbArray = $this->hex2dec($value);
            }
        }
        return $rgbArray;
    }

    /**
     * @param string $hex
     * @return array
     */
    function hex2dec($hex)
    {
        $color = str_replace('#', '', $hex);
        if (strlen($color) == 3) {
            $color = $color.$color;
        }
        $rgb = [
            'r' => hexdec(substr($color, 0, 2)),
            'g' => hexdec(substr($color, 2, 2)),
            'b' => hexdec(substr($color, 4, 2)),
            'a' => 0
        ];
        return $rgb;
    }

    /**
     * @param array $colorArray
     * @return bool
     */
    private function testColorExists($colorArray)
    {
        $r = $colorArray['r'];
        $g = $colorArray['g'];
        $b = $colorArray['b'];
        if (imagecolorexact($this->imageResized, $r, $g, $b) == -1) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @return int[]
     */
    private function findUnusedGreen()
    {
        $green = 255;
        do {
            $greenChroma = [0, $green, 0];
            $colorArray = $this->formatColor($greenChroma);
            $match = $this->testColorExists($colorArray);
            $green--;
        } while ($match == false && $green > 0);
        if (!$match) {
            $greenChroma = [0, $green, 0];
        }
        return $greenChroma;
    }

    /**
     * @param int $value
     * @param int $originalMax
     * @param bool $invert
     * @return float|int
     */
    private function invertTransparency($value, $originalMax, $invert = true)
    {
        if ($value > $originalMax) {
            $value = $originalMax;
        }
        if ($value < 0) {
            $value = 0;
        }
        if ($invert) {
            return $originalMax - (($value / 100) * $originalMax);
        } else {
            return ($value / 100) * $originalMax;
        }
    }

    /**
     * @param GdImage $src
     * @return GdImage
     */
    private function transparentImage($src)
    {
        for ($x = 0; $x < imagesx($src); ++$x) {
            for ($y = 0; $y < imagesy($src); ++$y) {
                $color = imagecolorat($src, $x, $y);
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;
                for ($i = 0; $i < 270; $i++) {
                    if ($r == 0 && $g == 255 && $b == 0) {
                        $trans_colour = imagecolorallocatealpha($src, 0, 0, 0, 127);
                        imagefill($src, $x, $y, $trans_colour);
                    }
                }
            }
        }
        return $src;
    }

    /**
     * @param string $needle
     * @param string $haystack
     * @return bool
     */
    function checkStringStartsWith($needle, $haystack)
    {
        return (substr($haystack, 0, strlen($needle)) == $needle);
    }

    /**
     * @param GdImage $gd_image
     * @return string
     */
    private function GD2BMPstring($gd_image)
    {
        $imageX = ImageSX($gd_image);
        $imageY = ImageSY($gd_image);
        $BMP = '';
        for ($y = ($imageY - 1); $y >= 0; $y--) {
            $thisline = '';
            for ($x = 0; $x < $imageX; $x++) {
                $argb = $this->GetPixelColor($gd_image, $x, $y);
                $thisline .= chr($argb['blue']).chr($argb['green']).chr($argb['red']);
            }
            while (strlen($thisline) % 4) {
                $thisline .= "\x00";
            }
            $BMP .= $thisline;
        }
        $bmpSize = strlen($BMP) + 14 + 40;
        $BITMAPFILEHEADER = 'BM';
        $BITMAPFILEHEADER .= $this->LittleEndian2String($bmpSize, 4);
        $BITMAPFILEHEADER .= $this->LittleEndian2String(0, 2);
        $BITMAPFILEHEADER .= $this->LittleEndian2String(0, 2);
        $BITMAPFILEHEADER .= $this->LittleEndian2String(54, 4);
        $BITMAPINFOHEADER = $this->LittleEndian2String(40, 4);
        $BITMAPINFOHEADER .= $this->LittleEndian2String($imageX, 4);
        $BITMAPINFOHEADER .= $this->LittleEndian2String($imageY, 4);
        $BITMAPINFOHEADER .= $this->LittleEndian2String(1, 2);
        $BITMAPINFOHEADER .= $this->LittleEndian2String(24, 2);
        $BITMAPINFOHEADER .= $this->LittleEndian2String(0, 4);
        $BITMAPINFOHEADER .= $this->LittleEndian2String(0, 4);
        $BITMAPINFOHEADER .= $this->LittleEndian2String(2835, 4);
        $BITMAPINFOHEADER .= $this->LittleEndian2String(2835, 4);
        $BITMAPINFOHEADER .= $this->LittleEndian2String(0, 4);
        $BITMAPINFOHEADER .= $this->LittleEndian2String(0, 4);
        return $BITMAPFILEHEADER.$BITMAPINFOHEADER.$BMP;
    }

    /**
     * @param GdImage $img
     * @param int $x
     * @param int $y
     * @return array|false
     */
    private function GetPixelColor($img, $x, $y)
    {
        if (! static::isImageResource($img)) {
            return false;
        }
        return @ImageColorsForIndex($img, @ImageColorAt($img, $x, $y));
    }

    /**
     * @param int $number
     * @param int $minbytes
     * @return string
     */
    private function LittleEndian2String($number, $minbytes = 1)
    {
        $intstring = '';
        while ($number > 0) {
            $intstring = $intstring . chr($number & 255);
            $number >>= 8;
        }
        return str_pad($intstring, $minbytes, "\x00", STR_PAD_RIGHT);
    }

    /**
     * @param string $filename
     * @return false|GdImage|resource
     */
    private function ImageCreateFromBMP($filename)
    {
        if (!$f1 = fopen($filename, "rb")) {
            return false;
        }
        $FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1, 14));
        if ($FILE['file_type'] != 19778) {
            return false;
        }
        $BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel' .
            '/Vcompression/Vsize_bitmap/Vhoriz_resolution' .
            '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1, 40));
        $BMP['colors'] = pow(2, $BMP['bits_per_pixel']);
        if ($BMP['size_bitmap'] == 0) {
            $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
        }
        $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel'] / 8;
        $BMP['decal'] = ($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] -= floor($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] = 4 - (4 * $BMP['decal']);
        if ($BMP['decal'] == 4) {
            $BMP['decal'] = 0;
        }
        $PALETTE = [];
        if ($BMP['colors'] < 16777216) {
            $PALETTE = unpack('V' . $BMP['colors'], fread($f1, $BMP['colors'] * 4));
        }
        $IMG = fread($f1, $BMP['size_bitmap']);
        $VIDE = chr(0);
        $res = imagecreatetruecolor($BMP['width'], $BMP['height']);
        $P = 0;
        $Y = $BMP['height'] - 1;
        while ($Y >= 0) {
            $X = 0;
            while ($X < $BMP['width']) {
                if ($BMP['bits_per_pixel'] == 24) {
                    $COLOR = unpack("V", substr($IMG, $P, 3).$VIDE);
                } elseif ($BMP['bits_per_pixel'] == 16) {
                    $COLOR = unpack("v", substr($IMG, $P, 2));
                    $blue = ($COLOR[1] & 0x001f) << 3;
                    $green = ($COLOR[1] & 0x07e0) >> 3;
                    $red = ($COLOR[1] & 0xf800) >> 8;
                    $COLOR[1] = $red * 65536 + $green * 256 + $blue;
                } elseif ($BMP['bits_per_pixel'] == 8) {
                    $COLOR = unpack("n", $VIDE.substr($IMG, $P, 1));
                    $COLOR[1] = $PALETTE[ $COLOR[1] + 1 ];
                } elseif ($BMP['bits_per_pixel'] == 4) {
                    $COLOR = unpack("n", $VIDE.substr($IMG, floor($P), 1));
                    if (($P * 2) % 2 == 0) {
                        $COLOR[1] = ($COLOR[1] >> 4);
                    } else {
                        $COLOR[1] = ($COLOR[1] & 0x0F);
                    }
                    $COLOR[1] = $PALETTE[ $COLOR[1] + 1 ];
                } elseif ($BMP['bits_per_pixel'] == 1) {
                    $COLOR = unpack("n", $VIDE.substr($IMG, floor($P), 1));
                    if (($P * 8) % 8 == 0) {
                        $COLOR[1] = $COLOR[1] >> 7;
                    } elseif (($P * 8) % 8 == 1) {
                        $COLOR[1] = ($COLOR[1] & 0x40) >> 6;
                    } elseif (($P * 8) % 8 == 2) {
                        $COLOR[1] = ($COLOR[1] & 0x20) >> 5;
                    } elseif (($P * 8) % 8 == 3) {
                        $COLOR[1] = ($COLOR[1] & 0x10) >> 4;
                    } elseif (($P * 8) % 8 == 4) {
                        $COLOR[1] = ($COLOR[1] & 0x8) >> 3;
                    } elseif (($P * 8) % 8 == 5) {
                        $COLOR[1] = ($COLOR[1] & 0x4) >> 2;
                    } elseif (($P * 8) % 8 == 6) {
                        $COLOR[1] = ($COLOR[1] & 0x2) >> 1;
                    } elseif (($P * 8) % 8 == 7) {
                        $COLOR[1] = ($COLOR[1] & 0x1);
                    }
                    $COLOR[1] = $PALETTE[ $COLOR[1] + 1 ];
                } else {
                    return false;
                }
                imagesetpixel($res, $X, $Y, $COLOR[1]);
                $X++;
                $P += $BMP['bytes_per_pixel'];
            }
            $Y--;
            $P += $BMP['decal'];
        }
        fclose($f1);
        return $res;
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        if (static::isImageResource($this->imageResized)) {
            imagedestroy($this->imageResized);
        }
    }

    /**
     * Returns true, if $image is either resource, or GdImage
     * @param resource|GdImage|mixed $image
     * @return bool
     */
    private static function isImageResource($image)
    {
        if (is_null($image)) {
            return false;
        }
        if (is_resource($image)) {
            return true;
        }
        /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
        if (class_exists('GdImage') && ($image instanceof GdImage)) {
            return true;
        }
        return false;
    }
}
