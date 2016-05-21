<?php

namespace ImageCache;

class ImageCache
{
    protected $width;
    protected $height;
    protected $frames;
    protected $delays;
    protected $dimensions;
    protected $loop;
    protected $disposal;
    protected $transparentRed;
    protected $transparentGreen;
    protected $transparentBlue;

    public function __construct($filePath)
    {
        if (file_exists($filePath)) {
            $meta = getimagesize($filePath);
            $raw = fread(fopen($filePath, 'rb'), filesize($filePath));
            $gif = new \GIFDecoder($raw);

            $this->width = $meta[0];
            $this->height = $meta[1];
            $this->frames = $gif->GIFGetFrames();
            $this->delays = $gif->GIFGetDelays();
            $this->dimensions = $gif->GIFGetFramesMeta();
            $this->loop = $gif->GIFGetLoop();
            $this->disposal = $gif->GIFGetDisposal();
            $this->transparentRed = $gif->GIFGetTransparentR();
            $this->transparentGreen = $gif->GIFGetTransparentG();
            $this->transparentBlue = $gif->GIFGetTransparentB();
        }
    }

    protected function watermark()
    {
        static $watermark;

        if (!isset($watermark)) {
            $watermark = imagecreatetruecolor(110, 50);

            imagefilledrectangle($watermark, 0, 0, 109, 49, 0x0000FF);
            imagefilledrectangle($watermark, 3, 3, 106, 46, 0xFFFFFF);
            imagestring($watermark, 2, 20, 10, 'antdigo (c)', 0x0000FF);
            imagestring($watermark, 1, 10, 30, strftime('%d.%m.%Y %H:%M'), 0x0000FF);
        }

        return $watermark;
    }

    public function resize($width = null, $height = null)
    {
        $frames = array();
        $__width = $this->width;
        $__height = $this->height;

        if ($width !== null && $height === null) {
            $ratio = $width / $this->width;
            $__width = $width;
            $__height = $this->height * $ratio;
        } elseif ($width === null && $height !== null) {
            $ratio = $height / $this->height;
            $__width = $this->width * $ratio;
            $__height = $height;
        } elseif ($width !== null && $height !== null) {
            $__width = $width;
            $__height = $height;
        }

        $scaleX = $__width / $this->width;
        $scaleY = $__height / $this->height;

        $watermark = $this->watermark();
        $watermarkW = imagesx($watermark);
        $watermarkH = imagesy($watermark);

        foreach ($this->frames as $index => $source) {
            $dimension = $this->dimensions[$index];
            $sourceW = $dimension['width'];
            $sourceH = $dimension['height'];
            $destinationX = $dimension['left'] * $scaleX;
            $destinationY = $dimension['top'] * $scaleY;
            $destinationW = $sourceW * $scaleX;
            $destinationH = $sourceH * $scaleY;
            $frame = imagecreatefromstring($source);
            $image = imagecreatetruecolor($__width, $__height);
            $transparent = imagecolorallocate($image, $this->transparentRed, $this->transparentGreen, $this->transparentBlue);

            imagecolortransparent($image, $transparent);
            imagefill($image, 0, 0, $transparent);
            imagecopyresized($image, $frame, $destinationX, $destinationY, 0, 0, $destinationW, $destinationH, $sourceW, $sourceH);
            imagecopymerge($image, $this->watermark(), $__width - $watermarkW - 10, $__height - $watermarkH - 10, 0, 0, $watermarkW, $watermarkH, 50);

            ob_start();
            imagegif($image);
            $frames[] = ob_get_contents();
            ob_end_clean();

            imagedestroy($frame);
            imagedestroy($image);
        }

        $gif = new \GIFEncoder($frames, $this->delays, $this->loop, $this->disposal, $this->transparentRed, $this->transparentGreen, $this->transparentBlue, 'bin');

        return $gif->GetAnimation();
    }
}
