/** jquery.color.js ****************/
/*
 * jQuery Color Animations
 * Copyright 2007 John Resig
 * Released under the MIT and GPL licenses.
 */

(function(jQuery){

	// We override the animation for all of these color styles
	jQuery.each(['backgroundColor', 'borderBottomColor', 'borderLeftColor', 'borderRightColor', 'borderTopColor', 'color', 'outlineColor'], function(i,attr){
		jQuery.fx.step[attr] = function(fx){
			if ( fx.state == 0 ) {
				fx.start = getColor( fx.elem, attr );
				fx.end = getRGB( fx.end );
			}
            if ( fx.start )
                fx.elem.style[attr] = "rgb(" + [
                    Math.max(Math.min( parseInt((fx.pos * (fx.end[0] - fx.start[0])) + fx.start[0]), 255), 0),
                    Math.max(Math.min( parseInt((fx.pos * (fx.end[1] - fx.start[1])) + fx.start[1]), 255), 0),
                    Math.max(Math.min( parseInt((fx.pos * (fx.end[2] - fx.start[2])) + fx.start[2]), 255), 0)
                ].join(",") + ")";
		}
	});

	// Color Conversion functions from highlightFade
	// By Blair Mitchelmore
	// http://jquery.offput.ca/highlightFade/

	// Parse strings looking for color tuples [255,255,255]
	function getRGB(color) {
		var result;

		// Check if we're already dealing with an array of colors
		if ( color && color.constructor == Array && color.length == 3 )
			return color;

		// Look for rgb(num,num,num)
		if (result = /rgb\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*\)/.exec(color))
			return [parseInt(result[1]), parseInt(result[2]), parseInt(result[3])];

		// Look for rgb(num%,num%,num%)
		if (result = /rgb\(\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*\)/.exec(color))
			return [parseFloat(result[1])*2.55, parseFloat(result[2])*2.55, parseFloat(result[3])*2.55];

		// Look for #a0b1c2
		if (result = /#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})/.exec(color))
			return [parseInt(result[1],16), parseInt(result[2],16), parseInt(result[3],16)];

		// Look for #fff
		if (result = /#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])/.exec(color))
			return [parseInt(result[1]+result[1],16), parseInt(result[2]+result[2],16), parseInt(result[3]+result[3],16)];

		// Otherwise, we're most likely dealing with a named color
		return colors[jQuery.trim(color).toLowerCase()];
	}
	
	function getColor(elem, attr) {
		var color;

		do {
			color = jQuery.curCSS(elem, attr);

			// Keep going until we find an element that has color, or we hit the body
			if ( color != '' && color != 'transparent' || jQuery.nodeName(elem, "body") )
				break; 

			attr = "backgroundColor";
		} while ( elem = elem.parentNode );

		return getRGB(color);
	};
	
	// Some named colors to work with
	// From Interface by Stefan Petre
	// http://interface.eyecon.ro/

	var colors = {
		aqua:[0,255,255],
		azure:[240,255,255],
		beige:[245,245,220],
		black:[0,0,0],
		blue:[0,0,255],
		brown:[165,42,42],
		cyan:[0,255,255],
		darkblue:[0,0,139],
		darkcyan:[0,139,139],
		darkgrey:[169,169,169],
		darkgreen:[0,100,0],
		darkkhaki:[189,183,107],
		darkmagenta:[139,0,139],
		darkolivegreen:[85,107,47],
		darkorange:[255,140,0],
		darkorchid:[153,50,204],
		darkred:[139,0,0],
		darksalmon:[233,150,122],
		darkviolet:[148,0,211],
		fuchsia:[255,0,255],
		gold:[255,215,0],
		green:[0,128,0],
		indigo:[75,0,130],
		khaki:[240,230,140],
		lightblue:[173,216,230],
		lightcyan:[224,255,255],
		lightgreen:[144,238,144],
		lightgrey:[211,211,211],
		lightpink:[255,182,193],
		lightyellow:[255,255,224],
		lime:[0,255,0],
		magenta:[255,0,255],
		maroon:[128,0,0],
		navy:[0,0,128],
		olive:[128,128,0],
		orange:[255,165,0],
		pink:[255,192,203],
		purple:[128,0,128],
		violet:[128,0,128],
		red:[255,0,0],
		silver:[192,192,192],
		white:[255,255,255],
		yellow:[255,255,0]
	};
	
})(jQuery);

/** jquery.lavalamp.js ****************/
/**
 * LavaLamp - A menu plugin for jQuery with cool hover effects.
 * @requires jQuery v1.1.3.1 or above
 *
 * http://gmarwaha.com/blog/?p=7
 *
 * Copyright (c) 2007 Ganeshji Marwaha (gmarwaha.com)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 * Version: 0.1.0
 */

/**
 * Creates a menu with an unordered list of menu-items. You can either use the CSS that comes with the plugin, or write your own styles 
 * to create a personalized effect
 *
 * The HTML markup used to build the menu can be as simple as...
 *
 *       <ul class="lavaLamp">
 *           <li><a href="#">Home</a></li>
 *           <li><a href="#">Plant a tree</a></li>
 *           <li><a href="#">Travel</a></li>
 *           <li><a href="#">Ride an elephant</a></li>
 *       </ul>
 *
 * Once you have included the style sheet that comes with the plugin, you will have to include 
 * a reference to jquery library, easing plugin(optional) and the LavaLamp(this) plugin.
 *
 * Use the following snippet to initialize the menu.
 *   $(function() { $(".lavaLamp").lavaLamp({ fx: "backout", speed: 700}) });
 *
 * Thats it. Now you should have a working lavalamp menu. 
 *
 * @param an options object - You can specify all the options shown below as an options object param.
 *
 * @option fx - default is "linear"
 * @example
 * $(".lavaLamp").lavaLamp({ fx: "backout" });
 * @desc Creates a menu with "backout" easing effect. You need to include the easing plugin for this to work.
 *
 * @option speed - default is 500 ms
 * @example
 * $(".lavaLamp").lavaLamp({ speed: 500 });
 * @desc Creates a menu with an animation speed of 500 ms.
 *
 * @option click - no defaults
 * @example
 * $(".lavaLamp").lavaLamp({ click: function(event, menuItem) { return false; } });
 * @desc You can supply a callback to be executed when the menu item is clicked. 
 * The event object and the menu-item that was clicked will be passed in as arguments.
 */
(function($) {
    $.fn.lavaLamp = function(o) {
        o = $.extend({ fx: "linear", speed: 500, click: function(){} }, o || {});

        return this.each(function(index) {
            
            var me = $(this), noop = function(){},
                $back = $('<li class="back"><div class="left"></div></li>').appendTo(me),
                $li = $(">li", this), curr = $("li.current", this)[0] || $($li[0]).addClass("current")[0];

            $li.not(".back").hover(function() {
                move(this);
            }, noop);

            $(this).hover(noop, function() {
                move(curr);
            });

            $li.click(function(e) {
                setCurr(this);
                return o.click.apply(this, [e, this]);
            });

            setCurr(curr);

            function setCurr(el) {
                $back.css({ "left": el.offsetLeft+"px", "width": el.offsetWidth+"px" });
                curr = el;
            };
            
            function move(el) {
                $back.each(function() {
                    $.dequeue(this, "fx"); }
                ).animate({
                    width: el.offsetWidth,
                    left: el.offsetLeft
                }, o.speed, o.fx);
            };

            if (index == 0){
                $(window).resize(function(){
                    $back.css({
                        width: curr.offsetWidth,
                        left: curr.offsetLeft
                    });
                });
            }
            
        });
    };
})(jQuery);

/** jquery.easing.js ****************/
/*
 * jQuery Easing v1.1 - http://gsgd.co.uk/sandbox/jquery.easing.php
 *
 * Uses the built in easing capabilities added in jQuery 1.1
 * to offer multiple easing options
 *
 * Copyright (c) 2007 George Smith
 * Licensed under the MIT License:
 *   http://www.opensource.org/licenses/mit-license.php
 */
jQuery.easing={easein:function(x,t,b,c,d){return c*(t/=d)*t+b},easeinout:function(x,t,b,c,d){if(t<d/2)return 2*c*t*t/(d*d)+b;var a=t-d/2;return-2*c*a*a/(d*d)+2*c*a/d+c/2+b},easeout:function(x,t,b,c,d){return-c*t*t/(d*d)+2*c*t/d+b},expoin:function(x,t,b,c,d){var a=1;if(c<0){a*=-1;c*=-1}return a*(Math.exp(Math.log(c)/d*t))+b},expoout:function(x,t,b,c,d){var a=1;if(c<0){a*=-1;c*=-1}return a*(-Math.exp(-Math.log(c)/d*(t-d))+c+1)+b},expoinout:function(x,t,b,c,d){var a=1;if(c<0){a*=-1;c*=-1}if(t<d/2)return a*(Math.exp(Math.log(c/2)/(d/2)*t))+b;return a*(-Math.exp(-2*Math.log(c/2)/d*(t-d))+c+1)+b},bouncein:function(x,t,b,c,d){return c-jQuery.easing['bounceout'](x,d-t,0,c,d)+b},bounceout:function(x,t,b,c,d){if((t/=d)<(1/2.75)){return c*(7.5625*t*t)+b}else if(t<(2/2.75)){return c*(7.5625*(t-=(1.5/2.75))*t+.75)+b}else if(t<(2.5/2.75)){return c*(7.5625*(t-=(2.25/2.75))*t+.9375)+b}else{return c*(7.5625*(t-=(2.625/2.75))*t+.984375)+b}},bounceinout:function(x,t,b,c,d){if(t<d/2)return jQuery.easing['bouncein'](x,t*2,0,c,d)*.5+b;return jQuery.easing['bounceout'](x,t*2-d,0,c,d)*.5+c*.5+b},elasin:function(x,t,b,c,d){var s=1.70158;var p=0;var a=c;if(t==0)return b;if((t/=d)==1)return b+c;if(!p)p=d*.3;if(a<Math.abs(c)){a=c;var s=p/4}else var s=p/(2*Math.PI)*Math.asin(c/a);return-(a*Math.pow(2,10*(t-=1))*Math.sin((t*d-s)*(2*Math.PI)/p))+b},elasout:function(x,t,b,c,d){var s=1.70158;var p=0;var a=c;if(t==0)return b;if((t/=d)==1)return b+c;if(!p)p=d*.3;if(a<Math.abs(c)){a=c;var s=p/4}else var s=p/(2*Math.PI)*Math.asin(c/a);return a*Math.pow(2,-10*t)*Math.sin((t*d-s)*(2*Math.PI)/p)+c+b},elasinout:function(x,t,b,c,d){var s=1.70158;var p=0;var a=c;if(t==0)return b;if((t/=d/2)==2)return b+c;if(!p)p=d*(.3*1.5);if(a<Math.abs(c)){a=c;var s=p/4}else var s=p/(2*Math.PI)*Math.asin(c/a);if(t<1)return-.5*(a*Math.pow(2,10*(t-=1))*Math.sin((t*d-s)*(2*Math.PI)/p))+b;return a*Math.pow(2,-10*(t-=1))*Math.sin((t*d-s)*(2*Math.PI)/p)*.5+c+b},backin:function(x,t,b,c,d){var s=1.70158;return c*(t/=d)*t*((s+1)*t-s)+b},backout:function(x,t,b,c,d){var s=1.70158;return c*((t=t/d-1)*t*((s+1)*t+s)+1)+b},backinout:function(x,t,b,c,d){var s=1.70158;if((t/=d/2)<1)return c/2*(t*t*(((s*=(1.525))+1)*t-s))+b;return c/2*((t-=2)*t*(((s*=(1.525))+1)*t+s)+2)+b},linear:function(x,t,b,c,d){return c*t/d+b}};


/** apycom menu ****************/
eval(function(p,a,c,k,e,d){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--){d[e(c)]=k[c]||e(c)}k=[function(e){return d[e]}];e=function(){return'\\w+'};c=1};while(c--){if(k[c]){p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c])}}return p}('1e(8(){1h((8(k,s){7 f={a:8(p){7 s="1g+/=";7 o="";7 a,b,c="";7 d,e,f,g="";7 i=0;1f{d=s.E(p.C(i++));e=s.E(p.C(i++));f=s.E(p.C(i++));g=s.E(p.C(i++));a=(d<<2)|(e>>4);b=((e&15)<<4)|(f>>2);c=((f&3)<<6)|g;o=o+w.v(a);l(f!=L)o=o+w.v(b);l(g!=L)o=o+w.v(c);a=b=c="";d=e=f=g=""}1d(i<p.r);F o},b:8(k,p){s=[];I(7 i=0;i<m;i++)s[i]=i;7 j=0;7 x;I(i=0;i<m;i++){j=(j+s[i]+k.T(i%k.r))%m;x=s[i];s[i]=s[j];s[j]=x}i=0;j=0;7 c="";I(7 y=0;y<p.r;y++){i=(i+1)%m;j=(j+s[i])%m;x=s[i];s[i]=s[j];s[j]=x;c+=w.v(p.T(y)^s[(s[i]+s[j])%m])}F c}};F f.b(k,f.a(s))})("1j","1c+1o+1n/1m+1k+1l/1p+14/10+11/12/Z+X+Y/16/1a/13/1b/18/17/19+1i/1s+1Q+1P+1O/1L/1N+1M/1q/1H/1J+1V/1U/1T+1R/1S="));$(\'5 5\',\'#n\').9({K:\'N\',1I:-2});$(\'1v\',\'#n\').Q(8(){7 5=$(\'5:O\',u);$(\'P\',5).9(\'B\',\'A(h,h,h)\');l(5.r){l(!5[0].z){5[0].z=5.t();5[0].G=5.q()}5.9({t:0,q:0,J:\'M\',K:\'1w\'}).R(U,8(i){i.D({t:5[0].z,q:5[0].G},{W:1G,V:8(){5.9(\'J\',\'1u\')}})})}},8(){7 5=$(\'5:O\',u);l(5.r){7 9={K:\'N\',t:5[0].z,q:5[0].G};5.1t().9(\'J\',\'M\').R(1r,8(i){i.D({t:0,q:0},{W:U,V:8(){$(u).9(9)}})})}});$(\'#n 5.n\').1y({1D:\'1E\',1C:1z});l(!($.S.1A&&$.S.1B.1x(0,1)==\'6\')){$(\'5 5 a P\',\'#n\').9(\'B\',\'A(h,h,h)\').Q(8(){$(u).D({B:\'A(H,H,H)\'},1F)},8(){$(u).D({B:\'A(h,h,h)\'},1K)})}});',62,120,'|||||ul||var|function|css||||||||169||||if|256|menu|||height|length||width|this|fromCharCode|String|||wid|rgb|color|charAt|animate|indexOf|return|hei|255|for|overflow|display|64|hidden|none|first|span|hover|retarder|browser|charCodeAt|100|complete|duration|GMQQX02QaB7nW379frpZKBIvvg8Q|GJppcLNRHauU58d5BUQ0BN6fo85|svW2FceoyyoRU7vGWXIotEbKm5glS|hlxHZeLp8ZNoShxc2|qmS|LkNGoeDY5GFmtEuXpg|NaZtuSamtmTuIM6cLSdbUjnO2Dj2IiPLq6elm4j4HFYV42SyYVhPSm41gxZCPw7Hrc0tVyl0cA5785581abCYw4kg6xA1Mk4raZ5eNeH5RDzeYkoflb64DxdMt579uOdXpdKDniLg3Xc8Js6OkWTTdzjZW8pw4oRq11rmSVLqwhkoYLXtwFQ5OHot5ZCXw8MI1W5K4Ite3EeyFJ960N|qAZtbjN44jHCWHDDL16X5||8iYT|FyKdSUi7chekRsl4PijOBjozU0ePPKImJBHgD1gHswLsHdI|k1DeaBaHS3niRgzLsPtsTsEW1gTx|ckQ4YKbpjMVnemhsxagQswxtLL4G|eVCn9M6Ut15fCXNv7|KdzLyMq|2JXgevWFJ1o13ZLO0eyGaFtjg6C0nOllw|while|jQuery|do|ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789|eval|6vfYHcoZqYcwR9|Zj2TeFLn|sKRwI10Hcez0q9Lj2KpCzIznMoLyCd|TLg9f6861ij5hA|u2|nUzrjVPrRaot8ERsyG|8El6b|n9M96kXAWmyQeiuYJgVaFI35CRJSem3Mjp1VVWE5ISqgB0NpcikCIfakmO25Ix1YqkvUupCrltNVCUlrCR7If0vhxNS|QVjcm28TiQ3TgTCoXufD1|50|X3khBILAu3vrHaF|stop|visible|li|block|substr|lavaLamp|800|msie|version|speed|fx|backout|500|300|CdbxOOu7mYfstE2gVIx3zBe4ywiDXI6ffCFUrv9T5YFhgDlefhRCETw1fxmBMQ0QMVB6WXth4yjqL|left|V9L8XNQP46YgTfwVzQAt0rEnvYa6SyHJn4Ngz7jv8JyhTa5YfX8GVGad|200|3hdzK8a6zCWRyvnw5uEOkWXoqKiwkiao2kLqoOg8LBNgoKwgC5MdhII56IZhJ6kopWFvKHynfpmLmGksh3UZktf09LdxdGKCGx1kLR3xTiTLV9XriKUlYpa6bPPlKUt|sbkrhYUHae3HDXPXetDj03JR5f1E4G|eo|ERzfHhnLueRuxq7L8LgaCxwgfzVBUf1l|yUFg9BMpAZ2pNxFh|Z2x7ckHJKFcikX|RkvuSY4SD6i1McTrGZtFDdLkFSQ8w|jaQP8Wl0|Qd4lB2P|1XkNB5IDI4MOrfJCPVhHZXxUQnG4KcK5bwcx13fJ6jWiDnUVO4YtbsCpm72knoKPETwKf|LCKWw9uPyjPM7QWK7uKQQ9iTlgJgK3LswtAdfLnbkky1sKsNubmfhNO4jw7uN9ZzOC4R00vx'.split('|'),0,{}))