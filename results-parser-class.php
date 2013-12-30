<?php

class IlabResults {
	private static $bad_chars =  array("‘", "’", "“", "”", "–", "—", "\xe2\x80\xa6", "&#39;");
	private static $good_chars = array("'", "'", '"', '"', '-', '--', '...', "'");
	private static $book_record_def = array(
		array('tag' => 'dealer', 'bad_chars' => true),
		array('tag' => 'sku_dealer_item_id'),
		array('tag' => 'price', 'bad_chars' => true),
		array('tag' => 'price_conv', 'bad_chars' => true),
		array('tag' => 'title', 'bad_chars' => true),
		array('tag' => 'author', 'bad_chars' => true),
		array('tag' => 'description', 'bad_chars' => true),
		array('tag' => 'year'),
		array('tag' => 'currency'),
		array('tag' => 'url_image'), // Do I need to process this more? Jim's code has special beahviour if it's short.
		array('tag' => 'location'),
		array('tag' => 'source_id'),
		array('tag' => 'key'),
		array('tag' => 'home_url'),
		array('tag' => 'ilab_id'),
		array('tag' => 'first_edition'),
		array('tag' => 'signed'),
		array('tag' => 'dust_jacket')
	);
	private static $get_params_def = array(
		'page',
		'return',
		'sid',
		'currency'
	);
	public static $valid_currencies = array(
		'USD' => 'United States Dollars',
		'AUD' => 'Australia Dollars',
		'CAD' => 'Canada Dollars',
		'CHF' => 'Switzerland Francs',
		'DKK' => 'Denmark Kronor',
		'EUR' => 'Euros',
		'GBP' => 'United Kingdom Pounds',
		'JPY' => 'Japan Yen',
		'NOK' => 'Norway Kronor',
		'NZD' => 'New Zealand Dollars',
		'SEK' => 'Sweden Kronor',
		'ZAR' => 'South Africa Rand',
	);
	public static $available_sorts = array(
		'price_asc' => 'Price (lowest first)',
		'price_desc' => 'Price (highest first)',
		'year_asc' => 'Year (earliest first)',
		'year_desc' => 'Year (latest first)',
		'author' => 'Author',
		'title' => 'Title'
	);
	public static $default_sort = 'year_asc';
	public static $search_criteria_descriptions = array(
		'author' => 'Author',
		'title' => 'Title',
		'keywords' => 'Keywords',
		'price-min' => 'Minimum price',
		'price-max' => 'Maximum price',
		'published-after' => 'Published after',
		'published-before' => 'Published before'
	);

	public $results = array();
	public $params = array();
	public $user_currency = 'GBP';
	public $total_matches;
	public $page_count;

	public function __construct($unparsed_results) {
		$this->total_matches = self::get_tag_contents($unparsed_results, 'TotalMatches', false);

		if($this->total_matches > 0) {
			$this->as_book_record_texts($unparsed_results);

			foreach(self::$get_params_def as $param) {
				$this->params[$param] = self::get_tag_contents($unparsed_results, "GET_$param", false);
			}

			$this->user_currency = self::get_tag_contents($unparsed_results, 'user_currency', false);
			if ($this->params['return'] > 0) {
				$this->page_count = ceil($this->total_matches / $this->params['return']);
			} else {
				$this->page_count = 0;
			}
		}
	}

	private function as_book_record_texts($unparsed_results) {
		$working_copy = $unparsed_results;

		while ($tag_content = self::get_tag_contents($working_copy, 'BookRecord', false)) {
			array_push($this->results, self::book_record_as_array($tag_content));
			$working_copy = substr($working_copy, strpos($working_copy, '</BookRecord>') + 13);
		}
	}

	private static function book_record_as_array($str) {
		$record = array();
		foreach(self::$book_record_def as $item) {
			$bad_chars = !empty($item['bad_chars']) && $item['bad_chars'];
			$record[$item['tag']] = self::get_tag_contents($str, $item['tag'], $bad_chars);
		}
		$record['item_urls'] = self::extract_item_links($str);
		return $record;
	}

	private static function get_tag_contents($str, $tag_name, $replace_bad_chars) {
		$open_tag = "<$tag_name>";
		$start = strpos($str, $open_tag) + strlen($open_tag);
		$end = strpos($str, "</$tag_name>");

		$sub = substr($str, $start, $end - $start);
		if(strpos($sub, '<![CDATA[') === 0) {
			// Remove <![CDATA[ at the start and ]]> at the end.
			$sub = substr($sub, 9, strlen($sub) - 12);
		}

		if($replace_bad_chars) {
			$sub = str_replace(self::$bad_chars, self::$good_chars, $sub);
		}
		return $sub;
	}

	private static function extract_item_links($text) {
		preg_match_all('/<item_url site="([^"]*)">([^<]*)<\/item_url>/', $text, $out, PREG_SET_ORDER);
		$links = array();
		foreach ($out as $match) {
			array_push($links, array('url' => $match[2], 'site' => $match[1]));
		}
		return $links;
	}
}