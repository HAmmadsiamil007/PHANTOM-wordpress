jQuery(function ($) {
	$(document).on('click', '#submit', function () {
		$.validator.addMethod('phone', function (value, element) {
			return this.optional(element) || /^[\d\s\-\+\(\)]{7,20}$/.test(value);
		}, 'Please enter a valid phone number.');
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
			var apiUrl = (window.phantomData && window.phantomData.rest_url) ? window.phantomData.rest_url.replace(/\/+$/, '') : (window.wpApiSettings && window.wpApiSettings.root ? window.wpApiSettings.root.replace(/\/+$/, '') + '/phantom/v1' : '/index.php?rest_route=/phantom/v1');
			$.ajax({
				url: apiUrl + '/contact',
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