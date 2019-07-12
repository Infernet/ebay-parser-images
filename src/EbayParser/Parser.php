<?php
declare(strict_types = 1);

namespace Infernet\Parser;

require __DIR__.'/../../vendor/autoload.php';

use Sunra\PhpSimple\HtmlDomParser;

final class Parser{

    private $dirPath;
    private $url;
    private $curl;
    private $regExpFileUrl='/^[\w\d\S]+\//i';
    private $regExpFileType='/\.\w*$/i';

    /**
     * Конструктор принимающий путь к месту сохранения, и ссылку на товар сайта ebay.com
     */
    public function __construct(string $dir,string $url) {
        $this->dirPath=$dir;
        if (!file_exists($this->dirPath)) {
            mkdir($this->dirPath);
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
    public function download():int {
        $htmlText=curl_exec($this->curl);
        curl_close($this->curl);
        
        $htmlDom=HtmlDomParser::str_get_html($htmlText);
        $images=$htmlDom->find("#vi_main_img_fs_slider img");

        $index=0;
        foreach ($images as $image){
            $fileType=$this->getMatch($image->src, $this->regExpFileType);
            $fileDir=$this->getMatch($image->src, $this->regExpFileUrl);
            $fileName=$this->getFileName($image->src, $fileType, 1600);
            
            if ($this->saveImage($fileDir.$fileName, $index.$fileType)) {
                $index++;
            }
        }

        return $index;
    }

    /**
     * Попытка сохранить изображение
     * Возвращает true в случае успеха
     */
    private function saveImage(string $url,string $fileName):bool {
        //сохранение экземпляра картинки
        if (copy($url, $this->dirPath.$fileName)) {
            return true;
        }
        return false;
    }
    
    /**
     * Получение первого вхождения по переданному регулярному выражению для данного url
     */
    private function getMatch(string $url,string $regExp):string {
        //получение вхождения по полученному регулярному выражению
        $matches=array();
        if (preg_match($regExp, $url, $matches)===1) {
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