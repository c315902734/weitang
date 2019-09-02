function tip(msg){
	$(".js-global-tip-box").remove();
	var html = '<div class="js-global-tip-box"><div class="global-tip"><span><table><tr><td>'+msg+'</td></tr></table></span></div></div>';
	$('body').append(html);
	setTimeout('$(".js-global-tip-box").fadeOut("normal",function(){$(".js-global-tip-box").remove();});',1600);
}

function confirmTip(msg,func){
	var html = '<div class="js-global-tip-box confirm">'+
					'<div class="global-confirm">'+
						'<span>'+
							'<table>'+
								'<tr>'+
									'<td colspan="2" style="border-bottom:1px solid #eee;">'+msg+'</td>'+
								'</tr>'+
								'<tr>'+
									'<td><a href="javascript:$(\'.js-global-tip-box\').remove();">取消</a></td>'+
									'<td><a href="javascript:;" class="receiptBtn">确认</a></td>'+
								'</tr>'+
							'</table>'+
						'</span>'+
					'</div>'+
				'</div>';
	$('body').append(html);
	$('.js-global-tip-box').find('.receiptBtn').click(function(){
		$('.js-global-tip-box').remove();
		func();
	});
	return false;
}
