/**
 * 全屏
 */
window.wangEditor.fullscreen = {
	// editor create之后调用
	init: function(editorSelector){
		$(editorSelector + " .w-e-toolbar").append('<div class="w-e-menu"><a class="_wangEditor_btn_fullscreen" href="###" onclick="window.wangEditor.fullscreen.toggleFullscreen(\'' + editorSelector + '\')">全屏</a></div>');
	},
	toggleFullscreen: function(editorSelector){
		$(editorSelector).toggleClass('fullscreen-editor');
		if($(editorSelector + ' ._wangEditor_btn_fullscreen').text() == '全屏'){
			$(editorSelector + ' ._wangEditor_btn_fullscreen').text('退出全屏');
		}else{
			$(editorSelector + ' ._wangEditor_btn_fullscreen').text('全屏');
		}
	}
};


/**
 * @todo 查看源码
 */
window.wangEditor.viewsource = {
	init: function(editorSelector) {
		$(editorSelector + " .w-e-toolbar").append('<div class="w-e-menu"><a class="_wangEditor_btn_viewsource" href="###" onclick="window.wangEditor.viewsource.toggleViewsource(\'' + editorSelector + '\')">源码</a></div>');
	},
	toggleViewsource: function(editorSelector) {
		editorHtml = editor.txt.html();
		if($(editorSelector + ' ._wangEditor_btn_viewsource').text() == '源码'){
			editorHtml = editorHtml.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/ /g, "&nbsp;");
			$(editorSelector + ' ._wangEditor_btn_viewsource').text('返回');
		}else{
			editorHtml = editor.txt.text().replace(/&lt;/ig, "<").replace(/&gt;/ig, ">").replace(/&nbsp;/ig, " ");
			$(editorSelector + ' ._wangEditor_btn_viewsource').text('源码');
		}
		editor.txt.html(editorHtml);
		editor.change && editor.change();	//更新编辑器的内容
	}
};

/**
 * 自动保存
 */
window.wangEditor.localcache = {
	init: function(editorSelector){
		$(editorSelector + " .w-e-toolbar").append('<div class="w-e-menu"><a class="_wangEditor_btn_localcache" href="###" onclick="window.wangEditor.localcache.toggleviLocalCache(\'' + editorSelector + '\')">自动恢复</a></div>');
	},
	toggleviLocalCache: function (editorSelector) {
		editorHtml = localStorage.getItem('local_cache');
		if($(editorSelector + ' ._wangEditor_btn_localcache').text() == '自动恢复'){
			console.log(editorHtml)
			editor.txt.html(editorHtml)
			editor.change && editor.change();	//更新编辑器的内容
		}

	}
};