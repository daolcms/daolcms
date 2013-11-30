jQuery(function($){
	$(window).load(function(){
		$("#gnb_menu .sub").each(function(){
			$(this).css("margin-left",-($(this).width()/2+10));
		});
	});

	$("#gnb_menu>ul>li").hover(function(){
		$(this).find(">ul").stop().css({"display":"block", "opacity":0}).animate({"opacity":1}, 300);
	},function(){
		$(this).find(">ul").stop().animate({"opacity":0}, 300, function(){
			$(this).css("display","none");
		});
	});
});