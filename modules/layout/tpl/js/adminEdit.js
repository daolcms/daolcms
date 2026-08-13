jQuery(document).ready(function($){
	$('.uploaded_image').css('cursor', 'pointer');
	$('.uploaded_image_path').hide();
	$('.uploaded_image').bind('click', function(e){
		var path = $(this).siblings('.uploaded_image_path').html();
		var html = '<div class="selected_image_path">' + path + '</div>';

		$('.selected_image_path').remove();
		$('.uploaded_image_list').after(html);
	});
});
