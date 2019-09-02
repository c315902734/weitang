var J_widget = (J_widget||{}).prototype={
	J_Tabs:function(o,$config){
		var s = $.extend({
				is_full: false,
				effect: 'none',
				navCls: '.ks-switchable-nav',
				contentCls: '.ks-switchable-content',
				delay: 1,
				triggerType: 'click',
				hasTriggers: true,
				steps: 1,
				viewSize: '',
				activeIndex: 1,
				activeTriggerCls: 'ks-active',
				circular: true,
				prevBtnCls: '',
				nextBtnCls: '',
				disableBtnCls: '',
				duration: 1,
				autoplay:true,
				countdown:false,
				countdownFromStyle:'',
				countdownCls:'.ks-switchable-trigger-mask',
				callback: ''
				},$config || {}),
			mytime = false,
			c = $(s.contentCls,o),
			c_l		= c.children(),
			n_l		= $(s.navCls,o).children(),
			len		= c_l.length,
			page    = Math.ceil(len/s.steps);
			//是否全屏
			if(s.is_full == 1){
				var body_width = $('body').width();
				if(body_width > 1920){
					body_width = 1920;
				}
				c_l.width(body_width);
			}
			var u_w     = (s.viewSize) ? s.viewSize:c_l.eq(0).width()*s.steps,//单位尺寸
			u_h     = (s.viewSize) ? s.viewSize:c_l.eq(0).height()*s.steps,
			allwidth   = u_w*page,//总尺寸
			allheight  = u_h*page;
			s.duration = s.duration*1000;
		if (len<s.steps) return;
		
		if (s.effect=="fade"||s.effect=="none") {
			c_l.css({position: "inherit"}).hide();
		}else{
			c.css({position: "absolute",top:"0",left:"0"});
			c_l.css({float:"left",display:"inline",overflow:"hidden"});
			if (s.circular) c.append(c.html());
			if (s.effect=="scrollx"){c.css({width:"9999px"});
			}else if (s.effect=="scrolly") c_l.css({clear:"left"});
		}
		myShow(checksw(s.activeIndex),true);
		if ((s.triggerType!='none')){
			s.triggerType = (s.triggerType=='click')?'click':'mouseover';
			n_l.bind(s.triggerType, function(event){myShow(n_l.index(this)+1);});
		}loadBtn();
		if (s.autoplay){	//滑入停止动画，滑出开始动画
			if (!mytime)play();
			o.hover(function(){
				if(mytime) clearInterval(mytime);
			},function(){play();});
		}
		function myShow(i,f){
			n_l.eq(i-1).addClass(s.activeTriggerCls).siblings().removeClass(s.activeTriggerCls);
			var c_l_w = (s.circular && s.activeIndex == page && i == 1) ? allwidth:u_w*(i-1),
				c_l_h = 0,
				isscrolly = false;
			switch(s.effect) {
				case "scrolly":
					c_l_h = (s.circular&&s.activeIndex == page && i == 1) ? allheight:u_h*(i-1);
					c_l_w=0;
					if (s.circular&&s.activeIndex==1&&i==page){
						isscrolly = true;
						c.css({top: (-allheight) + "px"});
					}
				case "scrollx":
					unloadBtn();
					if (s.circular&&s.activeIndex==1&&i==page&&isscrolly==false){
						c.css({left: (-allwidth)+ "px"});
					}
					c.stop(true,false).animate({
						top: (-c_l_h)+"px",left:(-c_l_w)+"px"
					}, s.duration,function(){
						if(i==1){c.css({left:"0",top:"0"});}loadBtn();
					});break;
			   case "fade":
				    if (s.activeIndex != i||f==true) c_l.stop(true,true).hide().eq(i-1).fadeIn(s.duration/2);break;
			   case "none":
					if (s.activeIndex != i||f==true) c_l.stop(true,true).eq(i-1).show().siblings().hide();break ;
			   default:
			   }
			s.activeIndex = i;
			setdisplay();countdown(s.activeIndex-1);
			if (typeof(s.callback) === "function") s.callback(i-1,n_l,c_l);
		};
		function countdown(i){//倒计时效果
			if (!s.countdown) return;
			var n_l_li = n_l.eq(i);
			var countdownCls = n_l_li.find(s.countdownCls);//取trigger-mask对象
			if (!countdownCls[0]) countdownCls = $("<div class='ks-switchable-trigger-mask'></div>").prependTo(n_l_li);
			s.countdownFromStyle = (s.countdownFromStyle)?s.countdownFromStyle:n_l_li.width();//计算初始样式
			countdownCls.css({'width':s.countdownFromStyle+'px'}).animate({width:'0px'},s.delay*1000);//启动效果
		}
		function setdisplay(){//设置
			if (!s.circular&&s.disableBtnCls!=""){
				$(s.nextBtnCls+","+s.prevBtnCls).removeClass(s.disableBtnCls);
				if (s.activeIndex == page) $(s.nextBtnCls).addClass(s.disableBtnCls);
				if (s.activeIndex == 1) $(s.prevBtnCls).addClass(s.disableBtnCls);
			}
		}
		function checksw(i){ //取下一帧序号
			return (i<1)?page:((i>page)?1:i);
		}
		function play(){
			mytime = setInterval(function(){
			 myShow(checksw(s.activeIndex+1));
			}, s.delay*1000);
		}
		function loadBtn(){
			if (s.prevBtnCls!=""){
				$(s.prevBtnCls).unbind().bind("click",function(){myShow(checksw(s.activeIndex-1));});
			}
			if (s.nextBtnCls!=""){
				$(s.nextBtnCls).unbind().bind("click",function(){myShow(checksw(s.activeIndex+1));});
			}
		}
		function unloadBtn(){
			$(s.prevBtnCls).unbind();
			$(s.nextBtnCls).unbind();
		}
	},
	J_Accordion:function(o,$config){
		var s = $.extend({
				effect: 'none',
				activeTriggerCls:'ks-active',
				triggerType: 'click',
				triggerCls: '.ks-switchable-trigger',
				panelCls: '.ks-switchable-panel',
				callback: '',
				multiple: false
				},$config || {}),
			active = s.activeTriggerCls;
		triggerType = ((s.triggerType=='click')?'click':'mouseover');
		o.children(s.triggerCls).bind(triggerType, function(event){
			var n = $(this),p = $(this).next(s.panelCls);
			if (triggerType=='mouseover'){ /*防止在active状态时，鼠标经过时会隐藏或晃动*/
				n.addClass(active);
				p.show();
			}else{
				n.toggleClass(active);
				effectType(s.effect,p);
			}
			if (s.multiple==false){
				n.siblings(s.triggerCls).removeClass(active);
				p.siblings(s.panelCls).hide();
			}
			if (typeof(s.callback) === "function") s.callback(n,p);
		});
		function effectType(e,t){
			if(e=='slide'){
				t.slideToggle();
			}else{
				t.toggle();
			}
		}
	},
	J_ajax:function(o,$config){
		var s = $.extend({
				url:'/index/ajax/index',
				data:{}},$config || {});
		o.on('click',function(){
			$.ajax({
				type: 'POST',
				url: s.url,
				cache: false,
				data: s.data,
				dataType: "json",
				success: function(info){
					ajaxFormComplete(info);
				}
			});
		});
	},
	J_timeFormat:function(o,$config){
		var s = $.extend({
				timestamp:0,
				ago:true,
				both:false
				},$config || {});
		var d = new Date();
		//获得GMT对于系统时间时间戳额外数值
		var gmtHours = d.getTimezoneOffset();
		var itime = parseInt(s.timestamp)+gmtHours, 
			showtime;
		//格式化为本地时间 +472 / +480 为消除8分钟误差
		showtime = s.ago ? getAgoTime(itime+480) : getLocalTime(itime+480);
		if(s.both){
			var ntime = parseInt(Date.parse(new Date())/1000);
			if(s.both=='end' && ntime >= itime){
				showtime = '已结束';
				o.addClass('label label-danger');
			}
			if(s.both=='start' && ntime <= itime){
				showtime = '未开始';
				o.addClass('label label-success');
			}
			if(s.both=='all'){
				showtime = ntime <= itime ? '未开始' : '已结束';
			}
		}
		//显示
		o.html(showtime);
		function getLocalTime(t){
			var dl = new Date(parseInt(t) * 1000);
			return dateFormat(dl,'yyyy-MM-dd hh:mm:ss');
		}
		//显示分秒天之前
		function getAgoTime(t){
			var val,p,dl,
				thisTimeStamp = parseInt(Date.parse(new Date())/1000),
				agoTime = thisTimeStamp - t;
			switch(true){
				case agoTime<60:
					val = '<font color="#FF9999">'+agoTime+'秒前</font>';
					break;
				case agoTime<3600:
					p = Math.ceil(agoTime/60);
					val = '<font color="#FF9999">'+p+'分钟前</font>';
					break;
				case agoTime<86400:
					p = Math.ceil(agoTime/3600);
					val = '<font color="#888">'+p+'小时前</font>';
					break;
				case agoTime<86400*3:
					p = Math.ceil(agoTime/86400);
					val = p+'天前';
					break;
				case agoTime<86400*365:
					dl = new Date(parseInt(t) * 1000);
					p = dateFormat(dl,'MM-dd');
					val = p;
					break;
				default:
					dl = new Date(parseInt(t) * 1000);
					p = dateFormat(dl,'yyyy-MM-dd');
					val = p;
			}
			return val;
		}
		//格式化时间
		function dateFormat(date, format) {
			if(format === undefined){
				format = date;
				date = new Date();
			}
			var map = {
				"M": date.getMonth() + 1, //月份
				"d": date.getDate(), //日
				"h": date.getHours(), //小时
				"m": date.getMinutes(), //分
				"s": date.getSeconds(), //秒
				"q": Math.floor((date.getMonth() + 3) / 3), //季度
				"S": date.getMilliseconds() //毫秒
			};
			format = format.replace(/([yMdhmsqS])+/g, function(all, t){
				var v = map[t];
				if(v !== undefined){
					if(all.length > 1){
						v = '0' + v;
						v = v.substr(v.length-2);
					}
					return v;
				}else if(t === 'y'){
					return (date.getFullYear() + '').substr(4 - all.length);
				}
				return all;
			});
			return format;
		}
	},
	J_dataFormat:function(o,$config){
		var s = $.extend({
				timestamp:0,
				type:'all',
				both:false
				},$config || {});
		var d = new Date();
		//获得GMT对于系统时间时间戳额外数值
		var gmtHours = d.getTimezoneOffset();
		var itime = parseInt(s.timestamp)+gmtHours, 
			showtime;
		//格式化为本地时间 +472 / +480 为消除8分钟误差
		showtime = getLocalTime(itime+480,s.type);
		if(s.both){
			var ntime = parseInt(Date.parse(new Date())/1000);
			if(s.both=='end' && ntime >= itime){
				showtime = '过期';
			}
		}
		//显示
		o.html(showtime);
		function getLocalTime(t,n){
			var dl = new Date(parseInt(t) * 1000),
				str;
			switch(n){
				case 'day':
					str = dateFormat(dl,'dd');
					break;
				case 'month':
					str = dateFormat(dl,'MM');
					break;
				case 'year':
					str = dateFormat(dl,'yyyy');
					break;
				case 'hours':
					str = dateFormat(dl,'hh');
					break;
				case 'minutes':
					str = dateFormat(dl,'mm');
					break;
				case 'seconds':
					str = dateFormat(dl,'ss');
					break;
				default:
					str = dateFormat(dl,'yyyy-MM-dd hh:mm:ss');
			}
			return str;
		}
		//格式化时间
		function dateFormat(date, format) {
			if(format === undefined){
				format = date;
				date = new Date();
			}
			var map = {
				"M": date.getMonth() + 1, //月份
				"d": date.getDate(), //日
				"h": date.getHours(), //小时
				"m": date.getMinutes(), //分
				"s": date.getSeconds(), //秒
				"q": Math.floor((date.getMonth() + 3) / 3), //季度
				"S": date.getMilliseconds() //毫秒
			};
			format = format.replace(/([yMdhmsqS])+/g, function(all, t){
				var v = map[t];
				if(v !== undefined){
					if(all.length > 1){
						v = '0' + v;
						v = v.substr(v.length-2);
					}
					return v;
				}else if(t === 'y'){
					return (date.getFullYear() + '').substr(4 - all.length);
				}
				return all;
			});
			return format;
		}
	},
	J_fixedBox:function(o,$config){
		var s = $.extend({
				type: 'fixed',
				position: 'right',
				where: 'bottom',
				value: 40,
				box: 980,
				show: 0,
				gotop:'.go-top'
				},$config || {});
		s.value = parseInt(s.value);
		s.box = parseInt(s.box);
		s.show = parseInt(s.show);
		o.find(s.gotop).on('click',function(){
			$('html,body').animate({scrollTop:'0px'},300);
		});
		//获取页面可见区域宽度
		var bw = $(window).width();
		//初始显示
		if(bw<=s.box){
			o.hide();
		}else{
			boxPosition(bw,s,o);
		}
		//改变窗口大小时
		$(window).resize(function(){
			bw = $(window).width();
			boxPosition(bw,s,o);
		});
		function boxPosition(bw,s,o){
			if(bw<=s.box){
				o.css({'display':'none'});
			}else{
				//计算定位偏移值
				var bp = bw/2 + s.box/2;
				o.css('position',s.type);
				if(s.position == 'right'){
					o.css('left',bp+'px');
				}else{
					o.css('right',bp+'px');
				}
				if(s.where == 'top'){
					o.css('top',s.value+'px');
				}else{
					o.css('bottom',s.value+'px');
				}
				if(s.show>0){
					$(window).scroll(function() {
						if($(window).scrollTop() >= s.show){
							o.fadeIn(300); 
						}else{    
							o.fadeOut(300);    
						}  
					});
				}
			}
		}
	},
	J_scrollWhere:function(o,$config){
		var s = $.extend({
				where: ''
				},$config || {});
		o.on('click',function(){
			if(s.where==''){
				$('html,body').animate({scrollTop:'0px'},500);
			}else{
				$('html,body').animate({scrollTop: $(s.where).offset().top}, 500);
			}
		});
	},
	J_ajaxPop:function(o,$config){
		cls = this;
		var s = $.extend({
				active:'active',
				triggerType: 'mouse',
				url: '/index',
				left:0,
				top:0,
				height:200,
				key:0,
				data:{}},$config || {}),
			triggerType = (s.triggerType=='click')?'click':'mouseenter',
			omenu = false,
			odrop = false,
			box = "ajax-pop-user-info-box-"+s.key,
			boxClass = '.'+box;
		if($(boxClass).length<1){
			$('body').append('<div class="'+box+'" data-ajax="0" style="display:none;"><img src="/static/images/loading.gif"></div>');
		}
		//绑定触发点
		o.bind(triggerType, function(event){
			var ajaxid = Math.round(Math.random()*9999999999);
			omenu = true,
			o.addClass(s.active);
			//计算坐标
			var t = o.offset(),
				top = t.top - parseInt(s.height)+parseInt(s.top),
				left = t.left+parseInt(s.left);
			//显示弹窗
			$(boxClass).css({'position':'absolute','z-index':'99','top':top+'px','left':left+'px','padding-bottom':'8px'})
				.stop().fadeIn().bind('mouseenter',function(){
					odrop = true;
				})
				.bind('mouseleave',function(){
					odrop = false;
					if (!omenu){
						setTimeout(function(){clo_drop($(boxClass))},10);
					}
				});
			//获取内容
			if($(boxClass).attr('data-ajax')=='0'){
				$.ajax({
					type: 'POST',
					url: s.url+'/'+ajaxid,
					cache: false,
					data: s.data,
					dataType: "html",
					success: function(info){
						$(boxClass).attr('data-ajax','1').html(info);
					}
				});
			}
		}).bind('mouseleave',function(){
			omenu = false;
			if (!odrop){
				setTimeout(function(){clo_drop($(boxClass))},10);
			}
		});
		//隐藏窗口
		function clo_drop(b){
			if(odrop==false&&omenu==false){
				b.hide();
				o.removeClass(s.active);
			}
		}
	},
	J_popUp:function(o,$config,z){
		cls = this;
		var s = $.extend({
				activeTriggerCls:'ks-active',
				triggerType: 'mouse',
				trigger:'.trigger',
				callback: '',
				callbackout: '',
				align:{
				  offset:[0,0],
				  points:['bl','tl']
				}},$config || {}),
			triggerType = (s.triggerType=='click')?'click':'mouseenter',
			triggerCls=((z)?z:$(s.trigger)),
			active = s.activeTriggerCls,
			omenu = false,
			odrop = false,
			u = s.align.offset,
			p = s.align.points;
		triggerCls.on(triggerType, function(event){
			omenu = true;
			if (o.css('display')=='none'){
				$(this).addClass(active);
				var t = triggerCls.offset();
				var top = get_align(p[0],t.top,triggerCls.height(),0,1,true);
					top = get_align(p[1],top,o.height(),0,1,false);
				var left = get_align(p[0],t.left,triggerCls.width(),1,2,true);
					left = get_align(p[1],left,o.width(),1,2,false);
				o.css(
					{'position':'absolute','z-index':'99','top':(top+u[1])+'px','left':(left+u[0])+'px'}
				).stop().show().on('mouseenter',function(){
					odrop = true;
				}).on('mouseleave',function(){
					odrop = false;
					if (!omenu){setTimeout(function(){clo_drop()},20);}
				});
				if (typeof(s.callback) === "function") s.callback(triggerCls,o);
			}
			return false;
		}).on('mouseleave',function(){
			omenu = false;
			if (!odrop){
				setTimeout(function(){clo_drop()},20);
			}
		});
		function clo_drop(){
			if (odrop==false&&omenu==false){
				if (typeof(s.callbackout) === "function") {
					s.callbackout(triggerCls,o);
				}else {o.hide();}
				triggerCls.removeClass(active);
			}
		}
		function get_align(str,pi,pi2,a,b,iso){
			pi2 = (iso)?pi2:0-pi2;
			switch(str.substring(a,b)){
				case 't':{pi = pi;break;}
				case 'c':{pi = pi+(pi2/2);break;}
				case 'b':{pi = pi+pi2;break;}
				case 'l':{pi = pi;break;}
				case 'r':{pi = pi+pi2;break;}
			}
			return pi;
		}
	},
	J_ajaxDialog:function(o,$config){
		var s = $.extend({
				type: 'modal',
				url: '/index/index',
				data:{}},$config || {});
		o.on('click',function(){
			var url = s.url;
			if(url.substr(-1)!='/') url = url+'/';
			$.ajax({
				type: 'GET',
				url: url,
				cache: false,
				data: s.data,
				dataType: 'HTML',
				success: function(info){
					modalComplete(info);
				}
			});
		});
		function modalComplete(e){
			//定义modal模板
			var messageBox = '<div class="modal fade" id="ajaxDialogModal" tabindex="-1" role="dialog" aria-labelledby="errMessageModal" aria-hidden="true">'
+'<div class="modal-dialog">'
+	'<div class="modal-content">'
+		'<div class="modal-header">'
+			'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>'
+			'<h4 class="modal-title" id="errMessageModalLabel">提示信息</h4>'
+		'</div>'
+		'<div class="modal-body">[[data]]</div>'
+		'<div class="modal-footer">'
+			'<button type="button" class="btn btn-primary ajax-updata-data">提交</button>'
+			'<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>'
+		'</div>'
+	'</div>'
+'</div></div>';
			var result ={};
				result.data = e;
			var renderDialog = template.compile(messageBox);
			$('body').append(renderDialog(result));
			var $ajaxDialogModal = $('#ajaxDialogModal')
			$ajaxDialogModal.modal('show');
			//关闭弹窗触发效果
			$('.ajax-updata-data').on('click',function(){
				var formurl = $ajaxDialogModal.find('from').attr('action'),
					data = $ajaxDialogModal.serialize();
				if($ajaxDialogModal.find('from').length>0){
					ajaxInfo(formurl,data,$ajaxDialogModal);
				}else{
					$ajaxDialogModal.modal('hide');
				}
			});
			$ajaxDialogModal.on('hidden.bs.modal',function(e){
				$ajaxDialogModal.remove();
			});
		}
		function ajaxInfo(url,data,box){
			$.ajax({
				type: 'POST',
				url: url,
				cache: false,
				data: data,
				dataType: "json",
				success: function(info){
					box.modal('hide');
					window.location.reload();
				}
			});
		}
	},
	J_imgUpload:function(o,$config){
		var s = $.extend({
				url: '/upload/userico',
				target: 'input[name="ico"]',
				name: 'myfile',
				callback: ''},$config || {});
		o.uploadFile({
				url:s.url,
				multiple:true,
				showStatusAfterSuccess:false,
				showAbort:false,
				showDone:false,
				allowedTypes:"png,gif,jpg,jpeg",
				maxFileSize:1024*1000*5,
				fileName:s.name,
				dragDropStr: "",
				extErrorStr:"上传失败!!!目前支持的图片格式为:",
				sizeErrorStr:"上传失败!!!超出最大图片限制:",
				uploadErrorStr:"上传错误,请刷新再试",
				onSubmit:function(){
					if($('#ajax-loading-box').length > 0){
						$('#ajax-loading-box').remove();
					}
					$('body').append('<div id="ajax-loading-box">加载中</div>');
				},
				onSuccess:function(files,info,xhr){
					var data = JSON.parse(info);
					if($(s.target).length>0) $(s.target).val(data);
					if (typeof(s.callback) === "function") s.callback(data,o);
				}
		});
	},
	J_autoInput:function(o,$config){
		var s = $.extend({
				url: '/index/index',
				limit: 9,
				min: 2,
				key: ''},$config || {});
		o.on('keyup focus',function(){
			s.key = o.val();
			var oldKey = o.attr('data-key');
			if(s.key.length<s.min){
				$('#input-'+o.attr('name')+'-sub').parent().removeClass('open');
				$('#input-'+o.attr('name')+'-sub').remove();
				return {};
			}
			if(s.key == oldKey) return {};
			$.ajax({
				url: s.url,
				type: 'post',
				async: true,
				data: {key:s.key},
				dataType: 'json',
				success: function(item){
					o.attr('data-key',s.key);
					return typeof(item) == 'undefined' ? false : process(item);
				}
			});
		});
		function process(item){
			var list = item.data,
				name = o.attr('name'),
				open = true;
			if(list=='' || list==null || list.length<1) return false;
			if($('#input-'+name+'-sub').length<1){
				o.addClass('dropdown dropdown-toggle').attr('id','input-'+name+'-box').after('<ul aria-labelledby="input-'+name+'-box" role="menu" class="dropdown-menu" id="input-'+name+'-sub"></ul>');
			}
			var listBox = '[[each data as val i]]<li role="presentation"><a class="input-dropdown" href="javascript:;" data-key="[[val.title]]" role="menuitem">[[val.title]]</a></li>[[/each]]';
			var renderBox = template.compile(listBox);
			$('#input-'+name+'-sub').html(renderBox(item)).parent().css({'position':'relative'});
			$('#input-'+name+'-box').dropdown('toggle');
			$('.input-dropdown').on('click',function(){
				o.val($(this).attr('data-key'));
				$('#input-'+name+'-sub').parent().removeClass('open');
			});
		}
	},
	J_scrollAjax:function(o,$config){
		var s = $.extend({
				url: '/question/index',
				item:'.auto_item',
				size: 20,
				page: 0,
				type: 1,
				box: '.scroll-box',
				callback:''
				},$config || {});

		//赋值节点
		o.attr({'size':s.size,'page':s.page,'top':0});
		o.scroll(function(){
			//当前可视范围高度
			var windowsH = $(s.box).height();
			var itemH = o.find(s.item+':last').height();
			//滚动高度
			var scrollH = o.scrollTop();
			//最后个节点距离顶部高度 - 可视范围高度 -10 = 加载高度
			var isAjax = windowsH - scrollH - itemH;
			if(o.attr('ajax')) return '';
			//单个节点高度
			if(isAjax <= 80){
				//底部监控节点数值
				var scrollPage = parseInt(o.attr('page'));
				var scrollNum = parseInt(o.attr('top'));
				if(scrollPage == scrollNum){
					var limit = s.size * (scrollPage+1);
					o.attr('top',scrollNum+1);
					$.ajax({
						url: s.url,
						type: 'post',
						data: {'page':scrollPage,'type':s.type},
						dataType: 'json',
						success: function(result){
							setTimeout(function(){
								o.attr('page',scrollPage+1);
							},1000);
							if(result.status==0) o.attr('ajax',1);
							if (typeof(s.callback) === "function") s.callback(result,o);
						}
					});
				}
			} 
		});
	},
	J_listTree:function(o,$config){
		var s = $.extend({
				treeclass:'pc-l',
				ico:true,
				topico:'<i class="fa fa-plus-square"></i>',
				subico:'<i class="fa fa-angle-double-right"></i>',
				choose:true,
				choosename:'cateid',
				checked:false,
				fold:true,
				level:5,
				callback:'',
				data:{}},$config || {});
		var json = o.attr('data-json'),list = (new Function("","return "+json))();
		var info = '',subinfo=[],sublv=[],topobj;
		var topico = s.ico ? s.topico : '',
			subico = s.ico ? s.subico : '';
		$.each(list,function(i,n){
			if(i==0 || i=='0'){
				topobj = n;
			}else{
				subinfo[i] = '';
				$.each(n,function(si,sn){
					subinfo[i] += createLi(sn.id,sn.fid,sn.catename,true);
				});
			}
		});
		var htmlcontent = '';
		$.each(topobj,function(i,n){
			htmlcontent += createLi(n.id,n.fid,n.catename,false);
			//插入子分类
			if(typeof(subinfo[i])!='undefined') htmlcontent += createTree(n.id);
		});
		o.html(htmlcontent);
		if(s.fold){
			foldCate();
		}
		if (typeof(s.callback) === "function") s.callback(o);
		//创建html条目
		function createLi(id,fid,catename,sub){
			var info = '',
				ico = (typeof(sub)!='undefined' && sub==true) ? subico : topico;
			if(s.choose){
				var chk = s.checked == id ? 'checked' : '';
				if(s.choose == 'checkbox'){
					info += '<li data-id="'+id+'" data-fid="'+fid+'"><input type="checkbox" name="'+s.choosename+'" value="'+id+'" '+chk+'><label data-fid="'+fid+'">'+ico+catename+'</label></li>';
				}else{
					info += '<li data-id="'+id+'" data-fid="'+fid+'"><input type="radio" name="'+s.choosename+'" value="'+id+'" '+chk+'><label data-fid="'+fid+'">'+ico+catename+'</label></li>';
				}
			}else{
				info += '<li class="'+s.treeclass+'" data-id="'+id+'" data-fid="'+fid+'">'+ico+catename+'</li>';
			}
			return info;
		}
		//创建节点
		function createTree(subid){
			var val = '<ul>';
			$.each(list,function(i,n){
				if(i==subid){
					$.each(n,function(si,sn){
						val += createLi(sn.id,sn.fid,sn.catename,true);
						if(typeof(subinfo[si])!='undefined') val += createTree(si);
					});
				}
			});
			val += '</ul>';
			return val;
		}
		//折叠回调
		function foldCate(){
			$('.cate-menu-tree').find('li').on('click',function(){
				$(this).next('ul').toggle();
			});
			$(o).find('ul').hide();
		}
	},
	J_linkageMenu:function(o,$config){
		var s = $.extend({
				url:'',
				target:'fid',
				data:{}},$config || {});
		//先增加input
		if(o.find('input[name="'+s.target+'"]').length<1) o.append('<input name="'+s.target+'" type="hidden" value="0">');
		o.find('select:eq(0)').attr('data-rank',0).attr('name','rank0');
		var list = o.find('select');
		list.off().on('change',function(){
			var rank = parseInt($(this).attr('data-rank')),
				fid = $(this).val();
			o.find('select:gt('+rank+')').remove();
			$('input[name="'+s.target+'"]').val(fid);
			ajaxInfo(fid,rank);
		});
		//ajax获取子分类
		function ajaxInfo(fid,p){
			p++;
			$.ajax({
				type: 'POST',
				url: s.url,
				cache: true,
				data: {'id':fid},
				dataType: "json",
				success: function(info){
					var content = '';
					if(info.status==1){
						content += '<select data-rank="'+p+'" name="rank'+p+'"><option value="'+fid+'">------</option>';
						$.each(info.data,function(i,n){
							content += '<option value="'+n.id+'">'+n.catename+'</option>';
						});
						content += '</select>'
						o.append(content);
						//重载自己
						o.J_widget();
					}
				}
			});
		}
	},
	J_formCheck:function(o,$config){
		var s = $.extend({
				submit:true, //是否提交按钮能点击
				now:true, //是否即时提示
				data:{}},$config || {});
		//阻止提交
		$(o).find(":submit").on('click',function(check){  
			//检查表单是否能提交(代写)
			form = false;
			//如果不能提交则阻止冒泡
			if(form==false){  
				check.preventDefault(); 
			}
		});
		/*检查类型:
		input[text]/textarea
			length:x 长度
			tel 电话号码(包含手机号)
			mobile 手机号码
			idcard 身份证
			number 数字(正整数)
			int 数字(整数)
			price 金额(正数)
		select
			empty 是否必选
		
		*/
		//先检查文字必填
		function checkText(){
			var err = '',
				check = false,
				list = $(o).find('input[type="text"],textarea');
			$.each(list,function(){
				var type = $(this).attr('data-check');
			});
		}
	},
	J_Countdown:function(o,$config){
		var d = new Date();
		var c = $.extend({
			 beginTime: gt(d),
			 endTime: gt(new Date(d.valueOf() + 24*60*60*1000)),
			 timebeginCls: '.ks-countdown-start',
			 timeRunCls: '.ks-countdown-run',
			 timeEndCls: '.ks-countdown-end',
			 timeUnitCls:{
				d: '.ks-d',
				h: '.ks-h',
				m: '.ks-m',
				s: '.ks-s'
				},
			 minDigit: 1//每个时间单位值显示的最小位数
			},$config || {}),
			T_D		= $(c.timeUnitCls.d,o),  //天数
			T_H		= $(c.timeUnitCls.h,o),  //小时
			T_M		= $(c.timeUnitCls.m,o),  //分钟
			T_S		= $(c.timeUnitCls.s,o),  //秒
			e		= ct(c.endTime),  //格式化倒计时终止时间
			b		= ct(c.beginTime),  //格式化倒计时开始时间
			obj		=[$(c.timebeginCls,o),$(c.timeRunCls,o),$(c.timeEndCls,o)], //开始前内容
			obt		=[T_D.length>0,T_H.length>0,T_M.length>0], //天分时秒表单存在否
			ft		= parseInt((new Date(e).getTime() - new Date(b).getTime())/1000),    //计算时间差,以秒为单位
			isstart = new Date(b).getTime() - d.getTime(),     //开始时间与当前时间的差值
			isend	= new Date(e).getTime() - d.getTime(),     //终止时间与当前时间的差值
			css		= new Array('none','inline');
		$(T_D).add(T_H).add(T_M).add(T_S).html(0);
		SetRemainTime();
		var InterValObj = window.setInterval(SetRemainTime, 1000); //间隔函数，1秒执行
		function SetRemainTime() {
			//if(isstart > 0){ //如果开始时间晚于当前时间，则只显示“倒计时还未开始”层
			//	set([1,0,0]);
			//}else 
			if(isend < 0){ //如果终止时间早于当前时间，则只显示“倒计时结束了”层
				set([0,0,1]);
				T_D.html('');T_H.html('');T_M.html('');T_S.html('');
			}else if(ft > 0){
				set([0,1,0]);
				ft--;
				var d = Math.floor((ft / 3600) / 24),        //计算天
					h = f(Math.floor((ft / 3600) % 24)),      //计算小时
					m = f(Math.floor((ft / 60) % 60)),      //计算分
					s = f(Math.floor(ft % 60));             // 计算秒
				T_D.html(f(d));
				h=(obt[0])?h:d*24+h;T_H.html(f(h));
				m=(obt[1])?m:h*60+m;T_M.html(f(m));
				s=(obt[2])?s:m*60+s;T_S.html(f(s));
				//显示区间
				/*if(d<1) T_D.css('display','none');
				if(d<1 && h<1) T_H.css('display','none');
				if(d>1){
					//T_M.css('display','none');
					//T_S.css('display','none');
				}*/
				if(d<1 & h>1){
					T_S.css('display','none');
				}
			}else{//剩余时间小于或等于0的时候，就停止间隔函数
				set([0,0,1]);
				window.clearInterval(InterValObj);//这里可以添加倒计时时间为0后需要执行的事件
			}
		}
		function f(str){//补位
			var seats = c.minDigit*1-String(str).length;
			for (var i=0;i<seats;i++ )str = "0" + String(str);
			return str;
		}
		function set(s){for (i in obj) obj[i].css('display',css[s[i]]);}//各状态显示
		function ct(str){return str.replace(/-/g,"/").replace(/ /g,",");}//格式化时间
		function gt(t){return t.getFullYear() + "-" + (t.getMonth() + 1) + "-" + t.getDate() + " " + t.toLocaleTimeString();}//取时间
	},
	init:function(o){
		try {this["J_"+o.attr("data-widget-type")](o,(new Function("return " + o.attr("data-widget-config")))()||{});}
		catch (e){$("body").append('class=['+o.attr("class")+']  id=['+o.attr("id")+']  :  ' + e.description);};
	}
};
(function($){
	$.fn.J_widget = function() {
		this.each(function(){
			J_widget.init($(this));
		});
		return this;
	};
})(jQuery);
$(document).ready(function() {
	$(".J_widget").J_widget();
});