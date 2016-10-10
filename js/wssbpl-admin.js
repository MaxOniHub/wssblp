jQuery(document).ready(function() {
	(function ($) {
		$('.datepicker').datepicker();


		$("#users-list").sieve({
			itemSelector: "p"
		});
		$("#sieve-search").hide();

		$('body').on('click', 'input[name="users"]', function() {
			$('#user').val($(this).val());
			$("#borrow-by-btn").show();
		});

		$('#status').click(function(){
			var status = $(this).val();

			status == 'available' ? $('.borrow-by').parent().hide() : $('.borrow-by').parent().show();

			data = {
				action: 'show_users_list',
				status: status
			};
			$.post(ajaxurl, data, function (response) {
				if (response) {
					$("#sieve-search").show();
					$("#users-list").html(response).show();
				} else {
					$("#sieve-search").hide();
					$("#users-list").html(response).hide();
				}
			});

		})

		//Submit the search user form

		$("body").on('click', '#borrow-by-btn', function (e) {
			var user_id = $("#user").val();
			var post_id = $("#post_ID").val();

			var data = {
				action: 'save_borrow_by',
				user_id: user_id,
				post_id: post_id
			};
			$.post(ajaxurl, data, function (response) {
				if (response) {
					$("#sieve-search").hide();
					$("#users-list").hide();
					$('.borrow-by').html(response);
				}
			});
			return false;
		});

		// retrieve ISBN 10
		$("#retrieve-isbn-10").click(function () {
			var isbn10 = $("#isbn-10").val();
			var post_id = $("#post_ID").val();
			if (isbn10) {
				jQuery("#isbn-error-10").html("");

				var data = {
					action: 'retrieve_by_isbn',
					isbn: isbn10,
					post_id: post_id,
					contentType: 'json'
				};
				$("#isbn-loader-10").show();
				search_by_isbn(data, 10);
			} else {
				jQuery("#isbn-error-10").html("Enter isbn number");
			}
		});

		// retrieve ISBN 13
		$("#retrieve-isbn-13").click(function () {
			var isbn13 = $("#isbn-13").val();
			var post_id = $("#post_ID").val();
			if (isbn13) {
			jQuery("#isbn-error-13").html("");
			var data = {
				action: 'retrieve_by_isbn',
				isbn: isbn13,
				post_id: post_id,
				contentType: 'json'
			};
			$("#isbn-loader-13").show();
			search_by_isbn(data, 13);
			} else {
				jQuery("#isbn-error-13").html("Enter isbn number");
			}
		});
	})(jQuery);

});

function search_by_isbn(data, isbn_number) {
	jQuery.ajax({
		url: ajaxurl,
		type: 'POST',
		dataType: 'json',
		data: data
	}).done(function (success) {
		if (success) {
			jQuery("#title").val(success['title']);
			jQuery("#title-prompt-text").html("");
			jQuery("#author").val(success['author']);
			var image = "<img width='"+success['thumbnail']['attach_data']['width']+"' " +
				"height='"+success['thumbnail']['attach_data']['height']+"' src='"+success['thumbnail']['attach_data']['file']+"' class='attachment-post-thumbnail'>";
			jQuery("#set-post-thumbnail").html(image);

			jQuery("#isbn-loader-" + isbn_number).hide();
			jQuery("#isbn-error-" + isbn_number).html("");
		}
	}).fail(function () {
		jQuery("#isbn-loader-" + isbn_number).hide();
		jQuery("#isbn-error-" + isbn_number).html("Invalid isbn number");
		console.log("Invalid");
	});
}