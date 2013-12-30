(function ($) {
	$(function () {
		$('.ilab-description').shorten({
			moreText: '(more)',
			lessText: '(less)',
			showChars: '550'
		});

		$('.ilab-thumbnail img').error(function () {
			$(this).closest('.ilab-thumbnail').hide();
		});

		$('.ilab-thumbnail img')
			.each(setThumbnailHeight)
			.load(setThumbnailHeight);

		function setThumbnailHeight() {
			$(this).closest('.ilab-thumbnail').height('');
			$(this).closest('.ilab-thumbnail').height($(this).height());
		}
	});
})(jQuery);