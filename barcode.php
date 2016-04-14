<?php
/**
 * This Barcode class based on Barcode's algorithms written by David S. Tufts, davidscotttufts.com
 *
 * Date: 2016-04-13
 * Author: Dmitriy Mamontov
 *
 */

namespace mamontovdmitriy\barcode;

class Barcode
{
    /**
     * @var string Barcode's text value
     */
    public $text;

    /**
     * @var string Type of barcode may be code25, code39, code128a, code128b, code128, codabar
     */
    public $type;

    /**
     * @var int  Size of barcode's image in pixels
     */
    public $size;

    /**
     * @var bool Horizontal or Vertical orientation
     */
    public $isHorizontal;

    /**
     * @var bool Show a text value under the barcode
     */
    public $showText;

    /**
     * @var int Size of text area in pixels
     */
    public $textSize = 30;


    private $encodeText;
    private $checksum;
    private $imageWidth;
    private $imageHeight;
    private $image;

    public function __construct($text = "", $type = "code128", $size = 20, $isHorizontal = true, $showText = true)
    {
        $this->text = $text;
        $this->type = $type;
        $this->size = $size;
        $this->isHorizontal = $isHorizontal;
        $this->showText = $showText;
    }

    /**
     * Save a barcode as image
     * @param string $filename
     * @param bool|false $download
     */
    public function save($filename = "", $download = true)
    {
        // generate encodeText
        if (in_array($this->type, ['code128', 'code128a'])) {
            $this->{$this->type}();
        }
        // calculate imageWidth, imageHeight
        $this->calculateImageSize();
        // generate image
        $this->generateImage();
        // output
        $this->saveImage($filename, $download);
    }


    private function calculateImageSize()
    {
        $length = 20;
        for ($i = 1; $i <= strlen($this->encodeText); $i++) {
            $length += (int)$this->encodeText[$i - 1];
        }

        if ($this->isHorizontal) {
            $this->imageWidth = $length;
            $this->imageHeight = $this->size;
        } else {
            $this->imageWidth = $this->size;
            $this->imageHeight = $length;
        }
    }

    private function generateImage()
    {
        $this->image = imagecreate($this->imageWidth, $this->imageHeight + ($this->showText ? $this->textSize : 0));
        $black = imagecolorallocate($this->image, 0, 0, 0);
        $white = imagecolorallocate($this->image, 255, 255, 255);
        imagefill($this->image, 0, 0, $white);

        if ($this->showText) {
            imagestring($this->image, 5, 31, $this->imageHeight, $this->text, $black);
        }

        $location = 10;
        for ($position = 1; $position <= strlen($this->encodeText); $position++) {
            $curSize = $location + $this->encodeText[$position - 1];
            if ($this->isHorizontal) {
                imagefilledrectangle($this->image, $location, 0, $curSize, $this->imageHeight, ($position % 2 == 0 ? $white : $black));
            } else {
                imagefilledrectangle($this->image, 0, $location, $this->imageWidth, $curSize, ($position % 2 == 0 ? $white : $black));
            }
            $location = $curSize;
        }
    }

    private function saveImage($filename, $download)
    {

        if ($download) {
            if ($filename !== "") {
                if (strtolower(substr($filename, -4)) !== ".png") {
                    $filename .= ".png";
                }
                header("Content-Description: File Transfer");
                header('Content-Disposition: attachment; filename="' . $filename . '"');
            }
            header('Content-Type: image/png');
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Expires: 0');
            $filename = "php://output";
        }

        imagepng($this->image, $filename);
        imagedestroy($this->image);
    }

    /**
     * code128 algorithm
     */
    private function code128()
    {
        // Must not change order of array elements as the checksum depends on the array's key to validate final code
        $alphabet = [
            " " => "212222", "!" => "222122", "\"" => "222221", "#" => "121223", "$" => "121322", "%" => "131222",
            "&" => "122213", "'" => "122312", "(" => "132212", ")" => "221213", "*" => "221312", "+" => "231212",
            "," => "112232", "-" => "122132", "." => "122231", "/" => "113222", "0" => "123122", "1" => "123221",
            "2" => "223211", "3" => "221132", "4" => "221231", "5" => "213212", "6" => "223112", "7" => "312131",
            "8" => "311222", "9" => "321122", ":" => "321221", ";" => "312212", "<" => "322112", "=" => "322211",
            ">" => "212123", "?" => "212321", "@" => "232121", "A" => "111323", "B" => "131123", "C" => "131321",
            "D" => "112313", "E" => "132113", "F" => "132311", "G" => "211313", "H" => "231113", "I" => "231311",
            "J" => "112133", "K" => "112331", "L" => "132131", "M" => "113123", "N" => "113321", "O" => "133121",
            "P" => "313121", "Q" => "211331", "R" => "231131", "S" => "213113", "T" => "213311", "U" => "213131",
            "V" => "311123", "W" => "311321", "X" => "331121", "Y" => "312113", "Z" => "312311", "[" => "332111",
            "\\" => "314111", "]" => "221411", "^" => "431111", "_" => "111224", "\'" => "111422", "a" => "121124",
            "b" => "121421", "c" => "141122", "d" => "141221", "e" => "112214", "f" => "112412", "g" => "122114",
            "h" => "122411", "i" => "142112", "j" => "142211", "k" => "241211", "l" => "221114", "m" => "413111",
            "n" => "241112", "o" => "134111", "p" => "111242", "q" => "121142", "r" => "121241", "s" => "114212",
            "t" => "124112", "u" => "124211", "v" => "411212", "w" => "421112", "x" => "421211", "y" => "212141",
            "z" => "214121", "{" => "412121", "|" => "111143", "}" => "111341", "~" => "131141", "DEL" => "114113",
            "FNC 3" => "114311", "FNC 2" => "411113", "SHIFT" => "411311", "CODE C" => "113141",
            "FNC 4" => "114131", "CODE A" => "311141", "FNC 1" => "411131", "Start A" => "211412",
            "Start B" => "211214", "Start C" => "211232", "Stop" => "2331112"];

        $alphabetKeys = array_keys($alphabet);
        $alphabetValues = array_flip($alphabetKeys);

        $this->checksum = 104;
        $this->encodeText = "";
        for ($i = 1; $i <= strlen($this->text); $i++) {
            $char = $this->text[$i - 1];
            $this->encodeText .= $alphabet[$char];
            $this->checksum += $alphabetValues[$char] * $i;
        }

        $this->encodeText .= $alphabet[$alphabetKeys[$this->checksum - intval($this->checksum / 103) * 103]];
        $this->encodeText = "211214" . $this->encodeText . "2331112";
    }

    /**
     * code128a algorithm
     */
    private function code128a()
    {
        // Must not change order of array elements as the checksum depends on the array's key to validate final code
        $alphabet = [
            " " => "212222", "!" => "222122", "\"" => "222221", "#" => "121223", "$" => "121322", "%" => "131222",
            "&" => "122213", "'" => "122312", "(" => "132212", ")" => "221213", "*" => "221312", "+" => "231212",
            "," => "112232", "-" => "122132", "." => "122231", "/" => "113222", "0" => "123122", "1" => "123221",
            "2" => "223211", "3" => "221132", "4" => "221231", "5" => "213212", "6" => "223112", "7" => "312131",
            "8" => "311222", "9" => "321122", ":" => "321221", ";" => "312212", "<" => "322112", "=" => "322211",
            ">" => "212123", "?" => "212321", "@" => "232121", "A" => "111323", "B" => "131123", "C" => "131321",
            "D" => "112313", "E" => "132113", "F" => "132311", "G" => "211313", "H" => "231113", "I" => "231311",
            "J" => "112133", "K" => "112331", "L" => "132131", "M" => "113123", "N" => "113321", "O" => "133121",
            "P" => "313121", "Q" => "211331", "R" => "231131", "S" => "213113", "T" => "213311", "U" => "213131",
            "V" => "311123", "W" => "311321", "X" => "331121", "Y" => "312113", "Z" => "312311", "[" => "332111",
            "\\" => "314111", "]" => "221411", "^" => "431111", "_" => "111224", "NUL" => "111422", "SOH" => "121124",
            "STX" => "121421", "ETX" => "141122", "EOT" => "141221", "ENQ" => "112214", "ACK" => "112412",
            "BEL" => "122114", "BS" => "122411", "HT" => "142112", "LF" => "142211", "VT" => "241211",
            "FF" => "221114", "CR" => "413111", "SO" => "241112", "SI" => "134111", "DLE" => "111242",
            "DC1" => "121142", "DC2" => "121241", "DC3" => "114212", "DC4" => "124112", "NAK" => "124211",
            "SYN" => "411212", "ETB" => "421112", "CAN" => "421211", "EM" => "212141", "SUB" => "214121",
            "ESC" => "412121", "FS" => "111143", "GS" => "111341", "RS" => "131141", "US" => "114113",
            "FNC 3" => "114311", "FNC 2" => "411113", "SHIFT" => "411311", "CODE C" => "113141",
            "CODE B" => "114131", "FNC 4" => "311141", "FNC 1" => "411131", "Start A" => "211412",
            "Start B" => "211214", "Start C" => "211232", "Stop" => "2331112"];

        $alphabetKeys = array_keys($alphabet);
        $alphabetValues = array_flip($alphabetKeys);

        $this->checksum = 103;
        $this->encodeText = "";
        $upperText = strtoupper($this->text); // Code 128A doesn't support lower case

        for ($i = 1; $i <= strlen($this->text); $i++) {
            $char = $upperText[$i - 1];
            $this->encodeText .= $alphabet[$char];
            $this->checksum += $alphabetValues[$char] * $i;
        }
        $this->encodeText .= $alphabet[$alphabetKeys[$this->checksum - intval($this->checksum / 103) * 103]];
        $this->encodeText = "211412" . $this->encodeText . "2331112";
    }


}
