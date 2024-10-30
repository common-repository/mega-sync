jQuery(document).ready(function($) {

	var field_names_wrap = $(".cf7sffi_form_field_names_wrap");
	var field_names_first = $(".cf7sffi_form_field_names_wrap > .cf7sffi_form_field_names.first_field");
	var cf7_field = $('.cf7sffi input.cf7-field');
	var cf7_field_names = cf7sffi_get_cf7_fields();

	if( cf7_field_names ) $('.cf7sffi .cf7_field_names').html('Contact Form 7 Fields: ' + cf7_field_names);

	$('.cf7sffi span.mailtag').click(function(event) {

		var range = document.createRange();

		range.selectNodeContents(this);
		window.getSelection().addRange(range);

	});
	var limit = 6;
	field_names_wrap.on("click", ".add_field", function(event) {
		if($("input[name='cf7sffi_sf_field[]']").length < limit) {
			event.preventDefault();

			var cloned = field_names_first.clone();

			cloned.removeClass("first_field");
			cloned.find("input").val("");
			cloned.append('<a href="#" class="button remove_field">Remove Field</a>');
			cloned.appendTo(field_names_wrap);
			cloned.hide();
			
			cloned.slideDown(150, function() {
				
				cloned.find("input").eq(0).focus();

			});
		}
		else{
			alert("Only 6 fields can map in the basic version of the plugin.");
		}
	});

	field_names_wrap.on("click", ".remove_field", function(event) {

		event.preventDefault();

		var parent_field = $(this).parent(".cf7sffi_form_field_names");

		parent_field.slideUp(150, function() {

			parent_field.remove();
			
		});

	});

	function cf7sffi_get_cf7_fields() {

		var mail_tags = $('#wpcf7-mail span.mailtag');
		var cf7_field_names = '';

		if( mail_tags.length > 0 ) {

			mail_tags.each(function(index, el) {

				var value = $(el).text();

				cf7_field_names += '<span class="mailtag code">' + value + '</span>';

			});

		}

		return cf7_field_names;

	}

});