<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 7/13/16
 * Time: 01:13
 */

namespace App\Helpers;

/**
 * A php library for converting relative urls to absolute.
 * Website: https://github.com/monkeysuffrage/phpuri
 *
 * <pre>
 * echo phpUri::parse('https://www.google.com/')->join('foo');
 * //==> https://www.google.com/foo
 * </pre>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author  P Guardiario <pguardiario@gmail.com>
 * @version 1.0
 */
class PhpUri {
	/**
	 * http(s)://
	 * @var string
	 */
	public $scheme;
	/**
	 * www.example.com
	 * @var string
	 */
	public $authority;
	/**
	 * /search
	 * @var string
	 */
	public $path;
	/**
	 * ?q=foo
	 * @var string
	 */
	public $query;
	/**
	 * #bar
	 * @var string
	 */
	public $fragment;

	private function __construct( $string ) {
		preg_match_all( '/^(([^:\/?#]+):)?(\/\/([^\/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?$/', $string, $m );
		$this->scheme    = $m[2][0];
		$this->authority = $m[4][0];
		/**
		 * CHANGE:
		 * @author Dominik Habichtsberg <Dominik.Habichtsberg@Hbg-IT.de>
		 * @since  24 Mai 2015 10:02 Uhr
		 *
		 * Former code:  $this->path = ( empty( $m[ 5 ][ 0 ] ) ) ? '/' : $m[ 5 ][ 0 ];
		 * No tests failed, when the path is empty.
		 * With the former code, the relative urls //g and #s failed
		 */
		$this->path     = $m[5][0];
		$this->query    = $m[7][0];
		$this->fragment = $m[9][0];
	}

	public function to_str() {
		$ret = '';
		if ( ! empty( $this->scheme ) ) {
			$ret .= "{$this->scheme}:";
		}
		if ( ! empty( $this->authority ) ) {
			$ret .= "//{$this->authority}";
		}
		$ret .= $this->normalize_path( $this->path );
		if ( ! empty( $this->query ) ) {
			$ret .= "?{$this->query}";
		}
		if ( ! empty( $this->fragment ) ) {
			$ret .= "#{$this->fragment}";
		}

		return $ret;
	}

	private function normalize_path( $path ) {
		if ( empty( $path ) ) {
			return '';
		}
		$normalized_path = $path;
		$normalized_path = preg_replace( '`//+`', '/', $normalized_path, - 1, $c0 );
		$normalized_path = preg_replace( '`^/\\.\\.?/`', '/', $normalized_path, - 1, $c1 );
		$normalized_path = preg_replace( '`/\\.(/|$)`', '/', $normalized_path, - 1, $c2 );
		/**
		 * CHANGE:
		 * @author Dominik Habichtsberg <Dominik.Habichtsberg@Hbg-IT.de>
		 * @since  24 Mai 2015 10:05 Uhr
		 * changed limit form -1 to 1, because climbing up the directory-tree failed
		 */
		$normalized_path = preg_replace( '`/[^/]*?/\\.\\.(/|$)`', '/', $normalized_path, 1, $c3 );
		$num_matches     = $c0 + $c1 + $c2 + $c3;

		return ( $num_matches > 0 ) ? $this->normalize_path( $normalized_path ) : $normalized_path;
	}

	/**
	 * Parse an url string
	 *
	 * @param string $url the url to parse
	 *
	 * @return null|phpUri
	 */
	public static function parse( $url ) {
		try{
			$uri = new phpUri( $url );
		}catch (\Exception $ex){
			return null;
		}
		
		/**
		 * CHANGE:
		 * @author Dominik Habichtsberg <Dominik.Habichtsberg@Hbg-IT.de>
		 * @since  24 Mai 2015 10:25 Uhr
		 * The base-url should always have a path
		 */
		if ( empty( $uri->path ) ) {
			$uri->path = '/';
		}

		return $uri;
	}

	/**
	 * Join with a relative url
	 *
	 * @param string $relative the relative url to join
	 *
	 * @return string
	 */
	public function join( $relative ) {
		$uri = new phpUri( $relative );
		switch ( true ) {
			case ! empty( $uri->scheme ):
				break;
			case ! empty( $uri->authority ):
				break;
			case empty( $uri->path ):
				$uri->path = $this->path;
				if ( empty( $uri->query ) ) {
					$uri->query = $this->query;
				}
				break;
			case strpos( $uri->path, '/' ) === 0:
				break;
			default:
				$base_path = $this->path;
				if ( strpos( $base_path, '/' ) === false ) {
					$base_path = '';
				} else {
					$base_path = preg_replace( '/\/[^\/]+$/', '/', $base_path );
				}
				if ( empty( $base_path ) && empty( $this->authority ) ) {
					$base_path = '/';
				}
				$uri->path = $base_path . $uri->path;
		}
		if ( empty( $uri->scheme ) ) {
			$uri->scheme = $this->scheme;
			if ( empty( $uri->authority ) ) {
				$uri->authority = $this->authority;
			}
		}

		return $uri->to_str();
	}
	
	public function getUniDomain(){
		$domain = $this->authority;
		if(strpos($domain, "www.") === 0){
			$domain = str_replace_first("www.", "", $domain);
		}
		return $domain;
	}
	
	public static function urlEncode($link){
		$is_encoded = preg_match('~%[0-9A-F]{2}~i', $link);
		if($is_encoded){
			return $link;
		}
		$matches = [];
		$is_match = preg_match('/^https?\:\/\/([\w\-]+\.)+[\w\-]+\/?/', $link, $matches);
		if($is_match){
			$base = $matches[0];
			$remain = str_replace($base, '', $link);
		}else{
			$base = "";
			$remain = $link;
		}
		$remain = str_replace("/", '__slash__', $remain);
		$remain = str_replace("?", '__question_mark__', $remain);
		$remain = str_replace("&", '__and_mark__', $remain);
		$remain = str_replace("=", '__equal_mark__', $remain);
		$remain = str_replace(",", '__comma_mark__', $remain);
		$remain = str_replace("#", '__sharp_mark__', $remain);
		$remain = urlencode($remain);
		$remain = str_replace("__slash__", '/', $remain);
		$remain = str_replace("__question_mark__", '?', $remain);
		$remain = str_replace("__and_mark__", '&', $remain);
		$remain = str_replace("__equal_mark__", '=', $remain);
		$remain = str_replace("__comma_mark__", ',', $remain);
		$remain = str_replace("__sharp_mark__", '#', $remain);
		return $base . $remain;
	}

	public static function googleSitesUniLink($url){
		$matches = [];
		$is_match = preg_match('/^https?\:\/\/sites\.google\.com\/site\/[\w\-\.]+\/?/ui', $url, $matches);
		if($is_match){
			return rtrim($matches[0], "/");
		}else{
			$is_match = preg_match('/^https?\:\/\/sites\.google\.com\/a\/[\w\-\.]+\/[\w\-\.]+\/?/ui', $url, $matches);
			if($is_match){
				return rtrim($matches[0], "/");
			}else{
				return false;
			}
		}
	}

	public static function googleSiteName($url){
		if(strpos($url, '/site/')){
			$remain = preg_replace('/^https?\:\/\/sites\.google\.com\/(site\/[\w\-\.]+)\/?.*/ui', "$1", $url);
		}else{
			$remain = preg_replace('/^https?\:\/\/sites\.google\.com\/(a\/[\w\-\.]+\/[\w\-\.]+)\/?.*/ui', "$1", $url);
		}
		return $remain;
	}
}