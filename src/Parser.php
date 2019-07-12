<?php
declare(strict_types = 1);

namespace Infernet\EbayParser;

require __DIR__.'/../../../autoload.php';


use Sunra\PhpSimple\HtmlDomParser;

final class Parser
{

    private $dirPath;
    private $url;
    private $curl;
    private $regExpFileUrl='/^[\w\d\S]+\//i';
    private $regExpFileType='/\.\w*$/i';

    /**
     * Конструктор принимающий путь к месту сохранения, и ссылку на товар сайта ebay.com
     */
    public function __construct(string $dir, string $url) {
        $this->dirPath = $dir;

        if (!is_dir($this->dirPath)) {
            mkdir($this->dirPath, 0777, true);
        }

        $this->url=$url;
        $this->curl=curl_init();
        // установка URL и других необходимых параметров
        curl_setopt($this->curl, CURLOPT_URL, $this->url);
        curl_setopt($this->curl, CURLOPT_HEADER, 0);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_BINARYTRANSFER, true);
    }

    /**
     * Загружает в указанный каталог изображения товара с указанной ссылки
     * @return число загруженных изображений
     */
    public function download(): int 
    {
        $htmlText = curl_exec($this->curl);
        curl_close($this->curl);
        
        $htmlDom = HtmlDomParser::str_get_html($htmlText);
        $images = $htmlDom->find('#vi_main_img_fs_slider img');

        
        foreach ($images as $n => $image) {
            $fileType = $this->getMatch($image->src, $this->regExpFileType);
            $fileDir = $this->getMatch($image->src, $this->regExpFileUrl);
            $fileName = $this->getFileName($image->src, $fileType, 1600);
            
            $this->saveImage($fileDir . $fileName, $n . $fileType);
        }

        return count($images);
    }

    /**
     * Попытка сохранить изображение
     */
    private function saveImage(string $url,string $fileName):void 
    {
        copy($url, $this->dirPath.$fileName);
    }
    
    /**
     * Получение первого вхождения по переданному регулярному выражению для данного url
     */
    private function getMatch(string $url,string $regExp):string {
        //получение вхождения по полученному регулярному выражению
        $matches = [];
        
        if (preg_match($regExp, $url, $matches) === 1) {
            return $matches[0];
        }

        return '';
    }

    /**
     * Получение полного имени файла с учетом его размера
     */
    private function getFileName(string $url,string $fileType,int $size=64):string {
        //установка размера изображения
        return "s-l".$size.$fileType;
    }
}