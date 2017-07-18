<?php
use ssv_material_parser\Parser;

/**
 * Created by PhpStorm.
 * User: moridrin
 * Date: 18-7-17
 * Time: 6:36
 */
class ImageCombiner
{
    public static function convertToSingle(array $srcImagePaths, $mapWidth)
    {
        $rowWidth  = 0;
        $mapHeight = 0;
        $images    = [];
        foreach ($srcImagePaths as $index => $srcImagePath) {
            if (mp_ends_with($srcImagePath, '.gif')) {
                $tileImg = imagecreatefromgif($srcImagePath);
            } elseif (mp_ends_with($srcImagePath, '.jpg')) {
                $tileImg = imagecreatefromjpeg($srcImagePath);
            } else {
                $tileImg = imagecreatefrompng($srcImagePath);
            }
            list($width, $height) = getimagesize($srcImagePath);
            $images[] = [
                'image'  => $tileImg,
                'width'  => $width,
                'height' => $height,
                'x'      => $rowWidth,
                'y'      => $mapHeight,
            ];
            $rowWidth += $width;
            if ($rowWidth >= $mapWidth) {
                $rowWidth  = 0;
                $mapHeight += $height;
            }
        }

        $mapImage = imagecreatetruecolor($mapWidth, $mapHeight);
        $bgColor  = imagecolorallocate($mapImage, 0, 0, 0);
        imagefill($mapImage, 0, 0, $bgColor);

        foreach ($images as $image) {
            imagecopy($mapImage, $image['image'], $image['x'], $image['y'], 0, 0, $image['width'], $image['height']);
            imagedestroy($image['image']);
        }

        imagepng($mapImage, Parser::PATH . 'tmp.png');
        return Parser::URL . 'tmp.png';
    }
}
