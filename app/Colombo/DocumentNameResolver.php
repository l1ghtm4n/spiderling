<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 7/31/17
 * Time: 14:54
 */

namespace App\Colombo;


class DocumentNameResolver {
	
	const MIN_WORD = 3;
	public $stopWords = [
	    'id' => [],
        'br' => []
    ];
	private $special_characters;
	private $handle_title_config;

    function __construct($configCountry)
    {
        $this->stopWords = $configCountry;
        $this->special_characters = config('crawler.name_file.special_characters');
        $this->handle_title_config = config('crawler.name_file.handle_title');
    }


    /**
     * @return mixed|string
     */
    public function handleTitle($country, $first_name, $second_name, $third_name){
        $minName = $this->handle_title_config['min_name'];
        $min_name_link_text = $this->handle_title_config['min_name_link_text'];
        $min_name_link_text_page_title = $this->handle_title_config['min_name_link_text_page_title'];
        if (mb_strlen($first_name) >= $minName){
            $first_name = $this->filterString($first_name, $country);
            $title = $first_name;
            return $title;
        }else{
            $second_name = $this->filterString($second_name, $country);
            if (empty($first_name)){
                $title = $second_name;
            }else{
                $title = $this->mergeString($first_name, $second_name);
            }
            if (mb_strlen($title) >= $min_name_link_text){
                return $title;
            }else{
                $third_name = $this->filterString($third_name, $country);
                $title_link_page = $this->mergeString($second_name, $third_name);
                if (!empty($first_name)){
                    $title = $title_link_page . ' ' . $first_name;
                }else{
                    $title = $title_link_page;
                }

                if (mb_strlen($title) > $min_name_link_text_page_title){
                    return $title;
                }else{
                    return '';
                }
            }
        }
    }
    public function mergeString($firstString, $secondString){
        $similarPercentMin = $this->handle_title_config['similar_percent_min'];
        $similarPercent = $this->similarity($firstString, $secondString);
        if ($similarPercent > $similarPercentMin){
            $string = $secondString;
        }else{
            //Ghép chuỗi
            if (empty($firstString)){
                $string = $secondString;
            }elseif (empty($secondString)){
                $string = $firstString;
            }else{
                $string = $secondString . ' ' . $firstString;
            }
        }
        return $string;
    }

    public function filterString($string, $country = ''){
        if (empty($country)){
            return $string;
        }
        if (strpos($string, ' ') === false){
            $string = str_replace("-", " ", $string);
            $string = str_replace("_", " ", $string);
            $string = str_replace("+", " ", $string);
        }
        $listSpecialKeywords = array_get($this->stopWords, $country);
        foreach ($listSpecialKeywords as $specialString){
            $string = str_ireplace($specialString, '', $string);
        }
        $listSpecialCharacters = $this->special_characters;
        foreach ($listSpecialCharacters as $specialCharacter){
            $string = str_replace($specialCharacter, ' ', $string);
        }
        $string = trim($string);
        return $string;
    }

    public function similarity($str1, $str2) {
        $len1 = strlen($str1);
        $len2 = strlen($str2);
        if ($len1 == 0 | $len2 == 0){
            return 0;
        }
        if ($len1 == 0 && $len2 == 0){
            return 100;
        }
        $percent = 0;
        similar_text($str1, $str2, $percent);
        return intval($percent * 100);
    }

	public static function fromUrl($link){
		$matches = [];
		if(!preg_match('/\/\/.+\/([\w-_\.%\(\)\+]{10,})\.\w{2,4}/u', $link, $matches)){
			return false;
		}
		$name = $matches[1];
		$name = urldecode($name);
		
		$name = str_replace("-", " ", $name);
		$name = str_replace("_", " ", $name);
		$name = str_replace("+", " ", $name);
		$name = trim($name);
		if(str_word_count($name) < self::MIN_WORD){return false;}
		return $name;
	}
	
	public static function fromContentDisposition($string){
		$string = str_replace("'", "\"", $string);
		$matches = [];
		if(preg_match('/filename\*=UTF\-8.{2}([\w\-\_\.\%\(\)\+\s]+)/u', $string, $matches)){
		
		}elseif(!preg_match('/filename=\"([\w-_\.%\(\)\+\s]+)\"/u', $string, $matches)){
			return false;
		}
		$name = self::urlSmartDecode($matches[1]);
		$name = preg_replace('/\.\w{2,4}$/', '', $name);
		$name = str_replace("-", " ", $name);
		$name = str_replace("_", " ", $name);
		$name = str_replace("+", " ", $name);
		\Log::info('name: ' . $name);
		return $name;

	}
	
	private static function urlSmartDecode($string){
		$is_encoded = preg_match('~%[0-9A-F]{2}~i', $string);
		if($is_encoded) {
			$string  = urldecode(str_replace(['+','='], ['%2B','%3D'], $string));
		}
		return $string;
	}
}