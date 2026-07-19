$(function () {
	$(document).on('click', '#submit', function () {
		$("#contactpage").validate({
			submitHandler: function (e) {
				submitSignupFormNow($("#contactpage"))
			},
			rules: {
				fname: {
					required: true
				},
				email: {
					required: true,
					email: true
				},
				phone: {
					required: true,
					phone: true
				}
			},
			errorElement: "span",
			errorPlacement: function (e, t) {
				e.appendTo(t.parent())
			}
		});
		submitSignupFormNow = function (e) {
			var t = e.serialize();
			var apiUrl = (window.phantomData && window.phantomData.rest_url) ? window.phantomData.rest_url.replace(/\/+$/, '') : '/wp-json';
			$.ajax({
				url: apiUrl + '/phantom/v1/contact',
				type: "POST",
				data: t,
				dataType: "json",
				success: function (t) {
					var msg = $('<span>').text(t.msg || 'Message sent').html();
					if (t.status === "Success") {
						$("#form_result").html('<span class="form-success alert alert-success d-block">' + msg + "</span>");
					} else {
						$("#form_result").html('<span class="form-error alert alert-danger d-block">' + msg + "</span>")
					}
					$("#form_result").show();
				}
			});
			return false
		}
	});

})