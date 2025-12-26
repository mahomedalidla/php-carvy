<?php

namespace Iloveimg;

class CropImageTask extends ImageTask
{
    public $crop_width;
    public $crop_height;
    public $crop_x;
    public $crop_y;

    /**
     * CropImageTask constructor.
     *
     * @param null|string $publicKey    Your public key
     * @param null|string $secretKey    Your secret key
     * @param bool $makeStart           Set to false for chained tasks, because we don't need the start
     */
    function __construct($publicKey, $secretKey, $makeStart = true)
    {
        $this->tool = 'cropimage';
        parent::__construct($publicKey, $secretKey, $makeStart);
    }

    public function setCropWidth($width)
    {
        $this->crop_width = $width;
        return $this;
    }

    public function setCropHeight($height)
    {
        $this->crop_height = $height;
        return $this;
    }

    public function setCropX($x)
    {
        $this->crop_x = $x;
        return $this;
    }

    public function setCropY($y)
    {
        $this->crop_y = $y;
        return $this;
    }
}