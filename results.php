<div id="ilab-waiting">Please wait&hellip;</div>
<?php

include_once 'results-parser-class.php';

function vialibri_ilab_has_criteria() {
	return vialibri_ilab_get('author') != ''
		|| vialibri_ilab_get('title') != ''
		|| vialibri_ilab_get('keywords') != '';
}

function vialibri_ilab_get($key) {
	$defaults = array(
		'sortby' => isset($_SESSION['vialibri_ilab_sortby']) ? $_SESSION['vialibri_ilab_sortby'] : IlabResults::$default_sort
	);
	if(!empty($_GET["ilab-$key"])) {
		return $_GET["ilab-$key"];
	} else if(!empty($defaults[$key])) {
		return $defaults[$key];
	} else {
		return '';
	}
}

function get_ilab_url() {
	return "http://www.vldhs.com/ILAB/searchAPI.php?".http_build_query(array(
		'author' => vialibri_ilab_get('author'),
		'title' => vialibri_ilab_get('title'),
		'alltext' => vialibri_ilab_get('keywords'),
		'pricemin' => vialibri_ilab_get('price-min'),
		'pricemax' => vialibri_ilab_get('price-max'),
		'ymin' => vialibri_ilab_get('published-after'),
		'ymax' => vialibri_ilab_get('published-before'),
		'currency' => vialibri_ilab_get('currency'),
		'sortby' => vialibri_ilab_get('sortby'),
		'page' => vialibri_ilab_get('page'),
		'sid' => vialibri_ilab_get('sid')
	));
}

function get_ilab_results($tries = 0) {
	$request_result = wp_remote_get(get_ilab_url());
	if ($request_result instanceof WP_Error) {
		if($tries == 3) {
			// We've already tried three times and it hasn't worked.
			// Display the error.
			return $request_result;
		} else {
			return get_ilab_results($tries++);
		}
	} else {
		$unparsed_results = $request_result['body'];
		return new IlabResults($unparsed_results);	
	}
}

$ilab_results = null;
if(vialibri_ilab_has_criteria()) {
	$ilab_results = get_ilab_results();

	$_SESSION['vialibri_ilab_currency'] = vialibri_ilab_get('currency');
	$_SESSION['vialibri_ilab_sortby'] = vialibri_ilab_get('sortby');
}

function vialibri_ilab_get_reset_url() {
	$to_copy = array(
		'currency',
		'debug'
	);
	$page_params = array(
		'page_id' => $_GET['page_id']
	);
	foreach($to_copy as $name) {
		if(vialibri_ilab_get($name) != '') {
			$page_params["ilab-$name"] = vialibri_ilab_get($name);
		}
	}
	return "?".http_build_query($page_params);
}

function vialibri_ilab_get_page_url($page, $sid) {
	$to_copy = array(
		'author',
		'title',
		'keywords',
		'price-min',
		'price-max',
		'published-after',
		'published-before',
		'currency',
		'sortby',
		'debug'
	);
	$page_params = array(
		'page_id' => $_GET['page_id'],
		'ilab-page' => $page,
		'ilab-sid' => $sid
	);
	foreach($to_copy as $name) {
		if(vialibri_ilab_get($name) != '') {
			$page_params["ilab-$name"] = vialibri_ilab_get($name);
		}
	}
	return "?".http_build_query($page_params);
}

function vialibri_ilab_page_a($text, $page, $current, $sid) {
	$url = esc_url(vialibri_ilab_get_page_url($page, $sid));
	$class = $page == $current ? 'selected' : '';
	echo "<a href=\"$url\" class=\"$class\">$text</a>";
}

function vialibri_ilab_show_pager($total, $current, $sid) {
	if ($total > 1) {
		echo '<div class="ilab-pager">Page:';

		if($current > 1) {
			echo vialibri_ilab_page_a('« First', 1, $current, $sid);
			echo vialibri_ilab_page_a('< Previous', $current - 1, $current, $sid);
		}

		$padding = 3;
		$start = max(1, $current - $padding);
		$end = min($total, $current + $padding);

		if($start > 1) {
			echo "<a>&hellip;</a>";
		}

		for($i = $start; $i <= $end; $i++) {
			echo vialibri_ilab_page_a($i, $i, $current, $sid);
		}

		if($end < $total) {
			echo "<a>&hellip;</a>";
		}

		if($current < $total) {
			echo vialibri_ilab_page_a('Next >', $current + 1, $current, $sid);
			echo vialibri_ilab_page_a('Last »', $total, $current, $sid);
		}

		echo '</div>';
	}
}

?>

<script type="text/javascript">
	document.getElementById('ilab-waiting').style.display = 'none';
</script>

<?php if(vialibri_ilab_has_criteria()) {
	if ($ilab_results instanceof IlabResults) { ?>
		<div class="ilab-criteria">
			<div class="ilab-header">Criteria</div>
			<ul>
				<?php foreach (IlabResults::$search_criteria_descriptions as $key => $description) {
					if (vialibri_ilab_get($key) != '') { ?>
						<li>
							<?php echo $description; ?>
							=
							<?php echo esc_html(vialibri_ilab_get($key)); ?>
						</li>
					<?php }
				} ?>
			</ul>
			<button onclick="location.href='#ilab-advanced-search';">Advanced Search</a>
		</div>
		<?php if ($ilab_results->total_matches > 0) { ?>
			<div class="ilab-result-count">
				Showing:
				<strong>
					<?php echo (($ilab_results->params['page'] - 1) * $ilab_results->params['return']) + 1; ?>
					-
					<?php echo min($ilab_results->params['page'] * $ilab_results->params['return'], $ilab_results->total_matches); ?>
				</strong>
				of
				<?php echo $ilab_results->total_matches; ?>
			</div>
			<?php vialibri_ilab_show_pager($ilab_results->page_count, $ilab_results->params['page'], $ilab_results->params['sid']) ?>
			<div class="ilab-results">
				<?php foreach($ilab_results->results as $result) { ?>
					<div class="ilab-result">
						<div class="ilab-author">
							<?php echo esc_html($result['author']) ?>
						</div>
						<div class="ilab-title">
							<?php echo esc_html($result['title']) ?>
						</div>
						<div class="ilab-left">
							<div class="ilab-description">
								<?php echo esc_html($result['description']) ?>
							</div>
							<div class="ilab-price">
								Price:
								<?php if ($result['currency'] != $ilab_results->user_currency) { ?>
									<span class="ilab-actual-price">
										<?php echo number_format(floatval($result['price_conv']), 2)." ".$ilab_results->user_currency; ?>
									</span>
									[<?php echo number_format(floatval($result['price']), 2)." ".$result['currency']; ?>]
								<?php } else { ?>
									<span class="ilab-actual-price">
										<?php echo number_format(floatval($result['price']), 2)." ".$result['currency']; ?>
									</span>
								<?php } ?>
							</div>
						</div>
						<div class="ilab-right">
							<?php if (strpos($result['url_image'], 'http') === 0) { ?>
								<div class="ilab-thumbnail">
									<a href="<?php echo esc_url($result['url_image']); ?>" target="_blank">
										<img src="<?php echo esc_attr($result['url_image']); ?>" alt="Thumbnail image of book" />
									</a>
								</div>
							<?php } ?>
							<div>Bookseller:</div>
							<div class="ilab-bookseller"><?php echo esc_html($result['dealer']) ?></div>

							<div class="ilab-buy-list">
								<div>Buy from:</div>
								<ul>
									<?php foreach($result['item_urls'] as $link) { ?>
										<li><a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['site']); ?></a></li>
									<?php } ?>
								</ul>
							</div>
						</div>
					</div>
				<?php } ?>
			</div>
			<?php vialibri_ilab_show_pager($ilab_results->page_count, $ilab_results->params['page'], $ilab_results->params['sid']) ?>
		<?php } else { // No results ?>
			<p>No results found, please try different search terms.</p>
		<?php } ?>
	<?php } else { // Not an IlabResults object, must be an error ?>
		<p>Sorry, ILAB search encountered an error. Please try again later.</p>
	<?php } 
}?>
<form id="ilab-advanced-search">
	<div class="ilab-header">Advanced Search</div>
	<input name="page_id" value="<?php echo get_option('vialibri_ilab_page_id'); ?>" type="hidden" />
	<div class="ilab-advanced-search-form">
		<div class="ilab-control-row">
			<div class="ilab-control-group">
				<label for="ilab-author">Author:</label>
				<div class="ilab-controls">
					<input type="text"
						id="ilab-author"
						name="ilab-author"
						value="<?php echo esc_attr(vialibri_ilab_get('author')); ?>" />
				</div>
			</div>
			<div class="ilab-control-group">
				<label for="ilab-published-after">Year, minimum:</label>
				<div class="ilab-controls">
					<input type="text"
						id="ilab-published-after"
						name="ilab-published-after"
						class="ilab-smaller"
						value="<?php echo esc_attr(vialibri_ilab_get('published-after')); ?>" />
				</div>
			</div>
			<div class="ilab-control-group">
				<label for="ilab-published-before">maximum:</label>
				<div class="ilab-controls">
					<input type="text"
						id="ilab-published-before"
						name="ilab-published-before"
						class="ilab-smaller"
						value="<?php echo esc_attr(vialibri_ilab_get('published-before')); ?>" />
				</div>
			</div>
		</div>
		<div class="ilab-control-row">
			<div class="ilab-control-group">
				<label for="ilab-title">Title:</label>
				<div class="ilab-controls">
					<input type="text"
						id="ilab-title"
						name="ilab-title"
						value="<?php echo esc_attr(vialibri_ilab_get('title')); ?>" />
				</div>
			</div>
			<div class="ilab-control-group">
				<label for="ilab-price-min">Price, minimum:</label>
				<div class="ilab-controls">
					<input type="text"
						id="ilab-price-min"
						name="ilab-price-min"
						class="ilab-smaller"
						value="<?php echo esc_attr(vialibri_ilab_get('price-min')); ?>" />
				</div>
			</div>
			<div class="ilab-control-group">
				<label for="ilab-price-max">maximum:</label>
				<div class="ilab-controls">
					<input type="text"
						id="ilab-price-max"
						name="ilab-price-max"
						class="ilab-smaller"
						value="<?php echo esc_attr(vialibri_ilab_get('price-max')); ?>" />
				</div>
			</div>
		</div>
		<div class="ilab-control-row">
			<div class="ilab-control-group">
				<label for="ilab-keywords">Keywords:</label>
				<div class="ilab-controls">
					<input type="text"
						id="ilab-keywords"
						name="ilab-keywords"
						value="<?php echo esc_attr(vialibri_ilab_get('keywords')); ?>" />
				</div>
			</div>
			<div class="ilab-control-group">
				<label for="ilab-sortby">Sort by:</label>
				<div class="ilab-controls">
					<select name="ilab-sortby" id="ilab-sortby">
						<?php foreach(IlabResults::$available_sorts as $code => $desc) { ?>
							<option value="<?php echo $code; ?>"
								<?php if ($code == vialibri_ilab_get('sortby')) { ?>
									selected="selected"
								<?php } ?>>
								<?php echo esc_html($desc); ?>
							</option>
						<?php } ?>
					</select>
				</div>
			</div>
			<div class="ilab-control-group">
				<label for="ilab-currency">Currency:</label>
				<div class="ilab-controls">
					<select name="ilab-currency" id="ilab-currency">
						<?php foreach(IlabResults::$valid_currencies as $code => $cur) { ?>
							<option value="<?php echo $code; ?>"
								<?php if ($code == vialibri_ilab_get('currency')) { ?>
									selected="selected"
								<?php } ?>>
								<?php echo esc_html($code); ?>
							</option>
						<?php } ?>
					</select>
				</div>
			</div>
		</div>
	</div>
	<div class="ilab-advanced-search-controls">
		<a href="<?php echo esc_url(vialibri_ilab_get_reset_url()); ?>">Reset</a>
		<input type="submit" value="Search" />
		<a href="http://www.ilab.org/">ILAB</a>
	</div>
</form>
<?php if (vialibri_ilab_get('debug') == 'true') {
	echo get_ilab_url();
	?>
	<pre>
		<?php echo print_r($ilab_results); ?>
	</pre>
<?php } ?>