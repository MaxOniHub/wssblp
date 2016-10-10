jQuery(document).ready(function () {
    (function ($) {

        var popup = $('#review-modal');
        var check_out_popup =  $("#check-out-modal");

        //click on link 'Review this book now'
        $(".review-post").on("click", function () {
            var post_id = $(this).attr('data-post');
            var data = {
                action: 'render_post_modal',
                post_id: post_id
            };
            $.post(AjaxRequestVar.ajaxurl, data, function (response) {
                if (response) {
                    popup.modal("show");
                    popup.find('.modal-body').html(response);
                }
            });
        });

        $("body").on('submit', '#review-form', function (e) {
            var formData = $(this).serialize();
            var data = {
                action: 'check_in',
                dataForm: formData
            };
           $.post(AjaxRequestVar.ajaxurl, data, function (response) {
               popup.modal("hide");
               window.location.reload(true);
            });
            return false;
        });


        //Submit the review form
        $("body").on('click', '#review-form-btn', function () {
            $("#review-form").submit();
        });

        //click on button when it's 'Check-out/Check-in'

        var toggle_btn = $('.toggle-check');

        toggle_btn.change(function () {
            var post_id = $(this).attr('data-post');
            var data = {};

            if ( $(this).hasClass("reset-check-in")) {
                data = {
                    action: 'render_post_modal',
                    post_id: post_id
                };

                $(this).removeClass("reset-check-in");
                $(this).addClass("reset-check-out");
                $(this).addClass('active');

                $.post(AjaxRequestVar.ajaxurl, data, function (response) {
                    if (response) {
                        popup.modal("show");
                        popup.find('.modal-body').html(response);
                    }
                });
            } else {
                data = {
                    action: 'render_post_modal_check_out',
                    post_id: post_id
                };

                $(this).removeClass("reset-check-out");
                $(this).addClass("reset-check-in");
                $(this).addClass('active');

                $.post(AjaxRequestVar.ajaxurl, data, function (response) {
                    if (response) {
                        check_out_popup.modal("show");
                        check_out_popup.find('.modal-body').html(response);
                    }
                });

            }
        });

        $("body").on('submit', '#check-out-form', function (e) {
            var formData = $(this).serialize();

            var data = {
                action: 'check_out',
                dataForm: formData
            };
            $.post(AjaxRequestVar.ajaxurl, data, function (response) {
                window.location.reload(true);

            });
            return false;
        });


        //Submit the check-out form
        $("body").on('click', '#check-out-form-btn', function () {
            $("#check-out-form").submit();
        });

		/*Hide modal event listener*/

        //revert toggle to "Check-in"
		popup.on('hidden.bs.modal', function () {
			var review_form = $("#review-form");
			var post_id = review_form.find("#post_id").val();

			data = {
				action: 'revert_ratings',
				post_id: post_id
			};

			$.post(AjaxRequestVar.ajaxurl, data, function (response) {

			});

			//revert toggle to "Check-in"
            $('.active').each(function () {
                if ($(this).hasClass('reset-check-out')) {
                    $(this).removeClass("reset-check-out");
                    $(this).addClass("reset-check-in");
                    $(this).parent().removeClass("off");
                    $(this).removeClass('active');
                }
            });

		});

        //revert toggle to "Check-out"
        check_out_popup.on('hidden.bs.modal', function () {
            $('.active').each(function () {
                if ($(this).hasClass('reset-check-in')) {
                    $(this).removeClass("reset-check-in");
                    $(this).addClass("reset-check-out");
                    $(this).parent().addClass("off");
                    $(this).removeClass('active');
                }
            });
        });

    })(jQuery);

});