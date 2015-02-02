<?php
echo $before_widget;
echo $before_title
	. 'Find books for sale from the world\'s leading antiquarian booksellers'
	. $after_title;
?>
	<style type="text/css">
		.ilab-widget-form {
			overflow: hidden;
		}
		.ilab-field {
			margin-bottom: 0.5em;
		}
		.ilab-field input {
			width: 100%;
			-moz-box-sizing: border-box;
			box-sizing: border-box;
		}
		.ilab-field + input[type='submit'] {
			margin-top: 0.5em;
		}
		.ilab-widget-form img {
			float: right;
			margin-top: 0.5em;
		}
	</style>
	<form class="ilab-widget-form">
		<input name="page_id" value="<?php echo get_option('vialibri_ilab_page_id'); ?>" type="hidden" />
		<input name="ilab-currency" value="<?php echo isset($_SESSION['vialibri_ilab_currency']) ? $_SESSION['vialibri_ilab_currency'] : $instance['currency']; ?>" type="hidden" />
		<div class="ilab-field"><input name="ilab-author" placeholder="Author" /></div>
		<div class="ilab-field"><input name="ilab-title" placeholder="Title" /></div>
		<div class="ilab-field"><input name="ilab-keywords" placeholder="Keywords" /></div>
		<input type="submit" value="Search" />
		<img src="<?php echo plugins_url(); ?>/ilab-rare-book-search/logo-ilab-eng.png"/>
	</form>
<?php
echo $after_widget;
?>