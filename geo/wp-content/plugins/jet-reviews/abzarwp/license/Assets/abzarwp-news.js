/**
 * AbzarWP News Widget Scripts
 *
 * Depends on: jQuery, wp_localize_script "abzarwpNews"
 *
 * @package AbzarWP\License
 */
(function ($) {
	"use strict";

	if (typeof abzarwpNews === "undefined") {
		return;
	}

	var ajaxUrl      = abzarwpNews.ajaxUrl;
	var nonce        = abzarwpNews.nonce;
	var dashboardUrl = abzarwpNews.dashboardUrl;
	var isDashboard  = abzarwpNews.isDashboard;

	/* ─── Admin bar bell (runs on ALL admin pages) ────── */
	$(document).ready(function () {
		$("#wp-admin-bar-abzarwp-urgent-news a").on("click", function (e) {
			e.preventDefault();
			var bell = $(this);

			$.ajax({
				url: ajaxUrl,
				type: "POST",
				data: { action: "abzarwp_dismiss_urgent", nonce: nonce },
				success: function (response) {
					if (!response.success) return;

					bell.closest("li").fadeOut(300, function () {
						$(this).remove();
					});

					var href = window.location.href;
					if (href.indexOf("index.php") > -1 ||
						(href.indexOf("wp-admin") > -1 &&
						 href.indexOf("wp-admin/") + 9 === href.length)) {
						$("html, body").animate({
							scrollTop: $("#abzarwp_news_widget").offset().top - 100
						}, 500);
					} else {
						window.location.href = dashboardUrl;
					}
				}
			});
		});
	});

	/* ─── Dashboard-only functionality ──────────────── */
	if (!isDashboard) {
		return;
	}

	/* Vazir font fallback */
	if ("fonts" in document) {
		var fontTimeout = setTimeout(function () {
			$(".abzarwp-news-widget").css("font-family", "Tahoma,Arial,sans-serif");
		}, 3000);

		Promise.race([
			document.fonts.load("400 12px Vazir"),
			document.fonts.load("700 12px Vazir")
		]).then(function () {
			clearTimeout(fontTimeout);
		}).catch(function () {
			clearTimeout(fontTimeout);
			$(".abzarwp-news-widget").css("font-family", "Tahoma,Arial,sans-serif");
		});
	} else {
		setTimeout(function () {
			if (!document.fonts || document.fonts.check("12px Vazir") === false) {
				$(".abzarwp-news-widget").css("font-family", "Tahoma,Arial,sans-serif");
			}
		}, 3000);
	}

	$(document).ready(function () {

		/* ── Tab switching ────────────────────────────── */
		$(".abzarwp-tab-button").on("click", function () {
			var tab = $(this).data("tab");
			$(".abzarwp-tab-button").removeClass("active");
			$(this).addClass("active");
			$(".abzarwp-tab-content").removeClass("active");
			$("#abzarwp-tab-" + tab).addClass("active");
		});

		/* ── Archive notification ─────────────────────── */
		$(".abzarwp-close-notification").on("click", function () {
			var button         = $(this);
			var notificationId = button.data("notification-id");
			var item           = button.closest(".abzarwp-notification-item");

			if (!confirm("آیا می‌خواهید این اطلاعیه را به آرشیو منتقل کنید؟")) return;

			$.ajax({
				url: ajaxUrl,
				type: "POST",
				data: {
					action: "abzarwp_archive_notification",
					nonce: nonce,
					notification_id: notificationId
				},
				beforeSend: function () { button.prop("disabled", true); },
				success: function (response) {
					if (response.success) {
						item.fadeOut(function () {
							item.remove();
							if ($(".abzarwp-notifications-list .abzarwp-notification-item").length === 0) {
								$(".abzarwp-notifications-list").html(
									'<div class="abzarwp-empty-state"><p>هیچ اطلاعیه جدیدی وجود ندارد</p></div>'
								);
							}
						});
					} else {
						alert(response.data.message || "خطا در انتقال به آرشیو");
						button.prop("disabled", false);
					}
				},
				error: function () {
					alert("خطا در ارتباط با سرور");
					button.prop("disabled", false);
				}
			});
		});

		/* ── Read-more modal ─────────────────────────── */
		$(document).on("click", ".abzarwp-read-more", function (e) {
			e.preventDefault();
			var link = $(this);
			$("#abzarwp-modal-title").text(link.data("title"));
			$("#abzarwp-modal-content").html(link.data("content"));
			$("#abzarwp-read-more-modal").fadeIn();
		});

		$(".abzarwp-modal-close, .abzarwp-modal-overlay").on("click", function () {
			$("#abzarwp-read-more-modal").fadeOut();
		});

		/* ── Label filter ────────────────────────────── */
		$("#abzarwp-label-filter").on("change", function () {
			var labelId = $(this).val();
			if (labelId === "") {
				$(".abzarwp-archived-item").show();
			} else {
				$(".abzarwp-archived-item").each(function () {
					$(this).toggle($(this).data("label-id") == labelId);
				});
			}
		});

		/* ── Message form ────────────────────────────── */
		$("#abzarwp-message-form").on("submit", function (e) {
			e.preventDefault();

			var form         = $(this);
			var subject      = $("#abzarwp-message-subject").val();
			var phone        = $("#abzarwp-message-phone").val();
			var message      = $("#abzarwp-message-text").val();
			var fileInput    = document.getElementById("abzarwp-message-attachment");
			var submitButton = form.find('button[type="submit"]');
			var responseDiv  = $("#abzarwp-message-response");

			if (!subject.trim()) {
				responseDiv.removeClass("success").addClass("error").html("لطفاً موضوع را وارد کنید").fadeIn();
				return;
			}
			if (!phone.trim()) {
				responseDiv.removeClass("success").addClass("error").html("لطفاً شماره همراه را وارد کنید").fadeIn();
				return;
			}
			if (!message.trim()) {
				responseDiv.removeClass("success").addClass("error").html("لطفاً متن پیام را وارد کنید").fadeIn();
				return;
			}

			if (fileInput.files.length > 0) {
				var file         = fileInput.files[0];
				var maxSize      = 5242880;
				var allowedTypes = ["image/jpeg", "image/png", "image/gif", "application/zip", "application/x-zip-compressed"];

				if (file.size > maxSize) {
					responseDiv.removeClass("success").addClass("error").html("حجم فایل نباید بیشتر از 5MB باشد").fadeIn();
					return;
				}
				if (allowedTypes.indexOf(file.type) === -1) {
					responseDiv.removeClass("success").addClass("error").html("فقط فایل‌های JPG, PNG, GIF و ZIP مجاز هستند").fadeIn();
					return;
				}
			}

			var formData = new FormData();
			formData.append("action", "abzarwp_send_message");
			formData.append("nonce", nonce);
			formData.append("subject", subject);
			formData.append("phone", phone);
			formData.append("message", message);
			if (fileInput.files.length > 0) {
				formData.append("attachment", fileInput.files[0]);
			}

			$.ajax({
				url: ajaxUrl,
				type: "POST",
				data: formData,
				processData: false,
				contentType: false,
				beforeSend: function () {
					submitButton.prop("disabled", true).text("در حال ارسال...");
					responseDiv.hide();
				},
				success: function (response) {
					if (response.success) {
						responseDiv.removeClass("error").addClass("success").html(response.data.message).fadeIn();
						form[0].reset();
						setTimeout(function () { responseDiv.fadeOut(); }, 5000);
					} else {
						responseDiv.removeClass("success").addClass("error").html(response.data.message || "خطا در ارسال پیام").fadeIn();
					}
				},
				error: function () {
					responseDiv.removeClass("success").addClass("error").html("خطا در ارتباط با سرور").fadeIn();
				},
				complete: function () {
					submitButton.prop("disabled", false).html('<span class="dashicons dashicons-email"></span> ارسال فیدبک');
				}
			});
		});

		/* ── Sync now ────────────────────────────────── */
		$("#abzarwp-sync-now").on("click", function () {
			var button = $(this);

			$.ajax({
				url: ajaxUrl,
				type: "POST",
				data: { action: "abzarwp_sync_now", nonce: nonce },
				beforeSend: function () {
					button.prop("disabled", true).addClass("spinning");
				},
				success: function (response) {
					if (response.success) {
						location.reload();
					} else {
						alert(response.data.message || "خطا در همگام‌سازی");
						button.prop("disabled", false).removeClass("spinning");
					}
				},
				error: function () {
					alert("خطا در ارتباط با سرور");
					button.prop("disabled", false).removeClass("spinning");
				}
			});
		});

		/* ── Track views ─────────────────────────────── */
		var viewedIds = [];
		$(".abzarwp-notification-item").each(function () {
			var id = $(this).data("notification-id");
			if (id && viewedIds.indexOf(id) === -1) {
				viewedIds.push(id);
				$.ajax({
					url: ajaxUrl,
					type: "POST",
					data: {
						action: "abzarwp_track_action",
						nonce: nonce,
						notification_id: id,
						action_type: "view"
					}
				});
			}
		});

		/* ── Track clicks ────────────────────────────── */
		$(document).on("click", ".abzarwp-notification-link, .abzarwp-action-button", function () {
			var id = $(this).data("notification-id");
			if (id) {
				$.ajax({
					url: ajaxUrl,
					type: "POST",
					data: {
						action: "abzarwp_track_action",
						nonce: nonce,
						notification_id: id,
						action_type: "click"
					}
				});
			}
		});
	});

})(jQuery);
