jQuery(document).ready(function($) {
	
	var spGallery = {
		timeout: 0,
		speed: 500,
		easing: "easeOutExpo",
		pager: ".sp-gallery-nav",
		next: ".sp-gallery-next",
		prev: ".sp-gallery-prev",
		thumbWidth: false,
		init: false,
		loading: true,
		pagerAnchorBuilder: function(idx, slide) {
			var this_gallery = jQuery(slide).parent().parent();
			var spGalleryData =jQuery(this_gallery).data('images'); 
			return "<a href='#"+idx+"'><img src='"+spGalleryData[idx].thumbnail+"' /></a>";
		},
		before: function(currSlideElement, nextSlideElement, options, forwardFlag) {
			var this_gallery = jQuery(this).parent().parent();
			var spGalleryData =jQuery(this_gallery).data('images'); 
			var slideNumber = $(nextSlideElement).prevAll().length;
			var spGallery = $(this_gallery).data('options');

			var this_gallery_meta = jQuery('#sp-gallery-meta' + jQuery(this_gallery).data('id'));
			//increment gallery count
			$(".sp-gallery-count",this_gallery_meta).text(slideNumber+1);
			//change gallery title/description
			$(".sp-gallery-title",this_gallery_meta).text(spGalleryData[slideNumber].title);
			$(".sp-gallery-caption",this_gallery_meta).text(spGalleryData[slideNumber].caption);

			if(spGalleryData[slideNumber].caption){
				$(".sp-gallery-title",this_gallery_meta).hide();
			}else{
				$(".sp-gallery-title",this_gallery_meta).show();
			}

			$(".sp-gallery-description",this_gallery_meta).text(spGalleryData[slideNumber].description);

			// we don't want to run the scrolling crap until after the gallery is init'd
			if (!jQuery(this_gallery).data('init')) {
				jQuery(this_gallery).data('init',true)
				$(".sp-gallery-loading",this_gallery).hide();
				return false;
			}

			//establish our thumbnail total width
			if (!spGallery.thumbWidth ) {
				var $thumb = jQuery(".sp-gallery-nav a:eq(0)",this_gallery);
				if ($thumb.length > 0) {
					spGallery.thumbWidth = $thumb.outerWidth() + parseInt($thumb.css("marginRight"), 10) + parseInt($thumb.css("marginLeft"), 10);
					//console.log(spGallery.thumbWidth);
				}
			}
			//move the bottom thumbnails appropriately
			var offset = $(".sp-gallery-nav a:eq("+slideNumber+")",this_gallery).position();

			var totalItems = $("ol",this_gallery).children().length;
			var totalWidth = totalItems * spGallery.thumbWidth;
			var parentWidth = $(".sp-gallery-nav-inner",this_gallery).width();
			var itemsPerWidth = parentWidth / spGallery.thumbWidth;
			var left = offset.left - (itemsPerWidth / 2 * spGallery.thumbWidth );
			var maxLeft = -(parentWidth - totalWidth);

			// set a maximum so that we stop moving once we hit the last thumb
			if (left > maxLeft) {
				left = maxLeft;
			}

			if (left > 0 && totalWidth > parentWidth) {
				$(".sp-gallery-nav",this_gallery).animate({left: -left}, 150, "easeInOutExpo");
			}
			else if (left < 0) {
				$(".sp-gallery-nav",this_gallery).animate({left: 0}, 150, "easeInOutExpo");
			}

			$(this_gallery).data('options',spGallery);
		}
	};

	$(".sp-gallery").each(function(){$('ol',this).cycle(spGallery_options(this));});

	function spGallery_options(ths){
		
		var this_gallery = jQuery(ths);
		spGallery.next = '#'+this_gallery.attr('id')+" .sp-gallery-next";
		spGallery.prev = '#'+this_gallery.attr('id')+" .sp-gallery-prev";
		spGallery.pager = '#'+this_gallery.attr('id')+" .sp-gallery-nav";

		$(this_gallery).data('options',spGallery);
		return spGallery;
	}	
});


jQuery.extend(jQuery.easing,{easeInExpo:function(x,t,b,c,d){return(t==0)?b:c*Math.pow(2,10*(t/d-1))+b;},easeOutExpo:function(x,t,b,c,d){return(t==d)?b+c:c*(-Math.pow(2,-10*t/d)+1)+b;},easeInOutExpo:function(x,t,b,c,d){if(t==0)return b;if(t==d)return b+c;if((t/=d/2)<1)return c/2*Math.pow(2,10*(t-1))+b;return c/2*(-Math.pow(2,-10*--t)+2)+b;}});
