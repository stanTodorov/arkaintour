$(document).ready(function() {
	"use strict";

	var login_timeout = window.setInterval( function() {
		var ctime = parseInt($("#timeout strong").text(), 10) - 1;

		if (ctime > 5) {
			$("#timeout strong").html(ctime);
		} else if (ctime <= 5 && ctime >= 0) {
			$("#timeout strong").css("color", "#ff8000").html(ctime);
		} else {
			window.clearInterval(login_timeout);
		}
	}, 60000);

	$("form:first input[type=text]:first").focus();

	$("a[data-link-external=true]").attr("target", "_blank");

	$("a[data-warning=true]").on("click", function() {
		return window.confirm("Сигурни ли сте че искате да продължите?");
	});

	$("#goback").on("click", function() {
		window.location.href = URL.site;
	});

	$("a, .hint").tooltip({
		showURL: false,
		loadURL: false,
		track: true,
		delay: 100,
		left: 16,
		top: 16,
		showBody: "|"
	});

	$("a.zoom").fancybox({"hideOnContentClick": true});

	$("select.autosubmit").on("change", function() {
		$(this).closest("form").submit();
	});

	$(".datepicker").datepicker();

	$("#select-all").on("click", function() {
		if ($(this).is(":checked")) {
			$("input[type=checkbox].toSelect").attr("checked", true);
			$("input[type=checkbox].toSelect").parents("tr").addClass("selected");
			return;
		}
		$("input[type=checkbox].toSelect").attr("checked", false);
		$("input[type=checkbox].toSelect").parents("tr").removeClass("selected");
	});

	$("input[type=checkbox].toSelect").on("click", function() {
		if ($(this).is(":checked")) {
			$(this).parents("tr").addClass("selected");
			return;
		}
		$(this).parents("tr").removeClass("selected");
	});

	$("a.setting").on("click", function() {
		var name = $(this).attr("href").replace(/[^a-z0-9\._]+/g, "");
		var value = null;
		var field = $("input[name=\"" + name + "\"]");

		if ($(field).is("[type=checkbox]")) {
			value = $(field).is(":checked");
		} else {
			value = $(field).val();
		}

		jQuery.ajax({
			url: URL.site + "admin/?page=ajax&action=setting",
			dataType: "json",
			type: "POST",
			data: {"name": name, "value": value},
			success: function(data, status){
				HandleJSON(data, status);
			}
		});

		return false;
	});

	$("#addUploadField").on("click", function() {
		$("#uploadFields input[type=file]:last").parent().after("<div><input type=\"file\" name=\"pictures[]\" /></div>");
		return false;
	});

	$("#menu a:not(.active)")
		.css({backgroundPosition: "0px 0px"})
		.mouseover(function(){
			var _this = $(this);
			$(this).stop().animate({backgroundPosition: "(0 38px)"}, 200, function() {
					$(this).css("color", "#ffffff");
				});
		})
		.mouseout(function(){
			$(this).stop().animate({backgroundPosition: "(0 0)"}, 200, function() {
					$(this).css("color", "#444444");
				});
		});


	$("table.list tr td").click(function (e) {
		var checkbox = $(this).parents("tr").find("input[type=checkbox].toSelect");
		if (e.target.tagName.toLowerCase() == "td") {
			if ($(this).parent().hasClass("selected")) {
				$(this).parent().removeClass("selected");
				$(checkbox).attr("checked", false);
			} else {
				$(this).parent().addClass("selected");
				$(checkbox).attr("checked", true);
			}
			return false;
		}
		return true;
	});

	$("span.showThumb").each(function() {
		$.preLoadImages($(this).attr("data-url"));
	});

	$("span.showThumb").on("hover", function(e) {
		var img = $(this).attr("data-url");
		if ($("#thumb-box").length == 0) {
			$("body").append('<div id="thumb-box"></div>');
		}

		$("#thumb-box").css({
			"left": e.pageX+18,
			"top": e.pageY+18,
			"background-image": 'url("'+img+'")'
		});

		$(this).css("cursor", "pointer");
	}).on("mousemove", function(e) {
		$("#thumb-box").css({
			"left": e.pageX+18,
			"top": e.pageY+18
		});
	}).on("mouseout", function() {
		$("#thumb-box").remove();
	});

	$("#addDateRange").on("click", function() {
		var where = $(this).parents("tr").find("div.dateRange:last");
		var count = $(this).parents("tr").find("div.dateRange").length + 1;
		var fields = '\
			<div class="dateRange">\
				<input type="text" id="dpdf_' + count + '" class="datepicker" name="date[]" value="" />\
				<a class="icon removeDateRange" href="#"><span class="remove"></span></a>\
			</div>';

		if (count == 1) {
			where = $(this).parents("tr").find("td");
			$(where).html(fields);
		} else {
			$(where).after(fields);
		}

		$(".datepicker").datepicker("destroy").datepicker();

		return false;
	});

	$("#addImage").on("click", function() {
		var where = $(this).parents("tr").find("div.filesList:last");
		var count = $(this).parents("tr").find("div.filesList").length + 1;
		var fields = '\
			<div class="filesList">\
				<input type="file" id="dpdf_' + count + '" name="pictures[]" value="" />\
				<a class="icon removePicture" href="#"><span class="remove"></span></a>\
			</div>';

		if (count == 1) {
			where = $(this).parents("tr").find("td");
			$(where).html(fields);
		} else {
			$(where).after(fields);
		}

		return false;
	});

	$("a.removeDateRange, a.removePicture").live("click", function() {
		$(this).parent().remove();
		return false;
	});

	$("#getCategoryListByPage").on("change", function() {
		var article = $(this).val();

		jQuery.ajax({
			url: URL.site + "admin/?page=ajax&action=options",
			dataType: "json",
			type: "POST",
			data: {"what": "categories", "article": article},
			success: function(data, status){
				HandleJSON(data, status);
			}
		});
	});


	$("div.tabs ul li:first").each(function() {
		$(".tab").hide();
		var activeTab = window.location.hash;

		if (activeTab.substr(0, 1) !== "#") {
			$(".tab:first").show();
			$(this).addClass("active");
		} else {
			$(this).removeClass("active");
			$(activeTab).show();
			$("div.tabs ul li a[href=" + activeTab + "]").parent().addClass("active");
		}

		$("div.tabs ul li a").click(function() {
			$("div.tabs ul li").removeClass("active");
			$(this).parent().addClass("active");
			$(".tab").hide();

			var activeTab = $(this).attr("href");
			if (activeTab.substr(0, 1) !== "#") {
				return false;
			}

			$(activeTab).show(0, function() {
				$(this).find("dl.charts:visible").each(function(i, v) {
					DrawGoogleChart(v);
				});
			});

			return false;
		});
	});

	$("#onChangeLanguage").on("change", function() {
		$("#pages").val("0").attr("disabled", "disabled").html("");
		jQuery.ajax({
			url: URL.site + "admin/?page=ajax&action=languages",
			dataType: "json",
			type: "POST",
			data: {
				"lang": $(this).val(),
				"which": $(this).data("which")
			},
			success: function(data, status){
				HandleJSON(data, status);
				if ($("#pages option").length) {
					$("#pages").attr("disabled", false);
				}
			}
		});
	});

	$("textarea.tinymce").tinymce({
		script_url: URL.js + "TinyMCE/tiny_mce.js",
		content_css: URL.css + "TinyMCE.css",

		// General options
		theme: "advanced",
		plugins: "pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,ibrowser",

		// Theme options
		theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
		theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
		theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen,|,ibrowser",
		theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true,
		width: "700",
		height: "450",
		language : "bg",

		// Drop lists for link/image/media/template dialogs
		template_external_list_url : "lists/template_list.js",
		external_link_list_url : "lists/link_list.js",
		external_image_list_url : "lists/image_list.js",
		media_external_list_url : "lists/media_list.js"
	});
});
