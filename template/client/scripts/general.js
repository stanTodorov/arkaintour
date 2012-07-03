$(document).ready(function() {
	"use strict";

	$("a.zoom").fancybox({"hideOnContentClick": true});

	$("#slideshow").SlideShow({
		"delay": 5000,
		"fading": 1000,
		"nav": ".slides-bullets"
	});

	$(".hidden").hide();
	$(".no-hidden").each(function() {
		var div = $(this);

		$(this).slideDown("fast", "swing", function() {
			$("html, body").stop().animate({
				scrollTop: $(div).offset().top
			}, 800);
		});

		return;
	});

	$("#main .options a").on("click", function(e) {
		var top = 0;
		e.preventDefault();
		$(".hidden").slideUp("fast");

		switch($(this).attr("class")) {
		case "print":
			window.print();
			return false;
		case "query":
			$("#query").slideDown("normal");
			top = $("#query").offset().top;
			break;
		case "friend":
			$("#send-to-friend").slideDown("normal");
			top = $("#send-to-friend").offset().top;
			break;
		}

		$("html, body").stop().animate({
			scrollTop: top
		}, 1000);

		return false;
	});

	$("#main ul.dropdown-offers li a.button").on("click", function(e) {
		var button = this;
		if ($(button).hasClass("do-it")) return false;

		// fix ie7 issue
		$(button).parents("ul").find("li").css("z-index", "1");
		$(button).parents("li").css("z-index", "2");

		$("#main ul.dropdown-offers li .offers-list").slideUp(100);

		$(button).addClass("do-it").parent().find(".offers-list").slideDown("fast", function() {
			$(button).removeClass("do-it");
		});

		e.preventDefault();
		return false;
	});

	$("#main .more-offers").on("click", function(e) {
		var id = parseInt($(this).attr("data-offer-id"), 10);

		jQuery.ajax({
			url: URL.site + "ajax?action=more-offers",
			dataType: "json",
			type: "POST",
			data: {"id": id},
			success: function(data, status){
				HandleJSON(data, status);
			}
		});

		e.preventDefault();
		return false;
	});
});