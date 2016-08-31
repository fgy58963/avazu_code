(function($){
function fileQueueError(file, errorCode, message) {
	try {
		var errorName = "";
		if (errorCode === SWFUpload.errorCode_QUEUE_LIMIT_EXCEEDED) {
			errorName = "你选择的文件数超过了最大限制";
		}

		if (errorName !== "") {
			alert(errorName);
			return;
		}

		switch (errorCode) {
		case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
			errorName = "你选择的文件大小超过了最大限制 " + this.getSetting('file_limit_size');
			break;
		case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
		case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
		default:
			alert(message);
			break;
		}

        if (errorName) {
            alert(errorName);
        }
	} catch (ex) {
		this.debug(ex);
	}

}

function fileDialogComplete(numFilesSelected, numFilesQueued) {
	try {
		if (numFilesQueued > 0) {
			this.startUpload();
		}
	} catch (ex) {
		this.debug(ex);
	}
}

function uploadProgress(file, bytesLoaded) {

	try {
		var percent = Math.ceil((bytesLoaded / file.size) * 100);
        var $cnt = $('#' + this.customSettings.cnt_id);
        if ($cnt.find('.upload-process-outter').css('display') == 'none') {
            $cnt.find('.upload-process-outter').show();
        }
        $cnt.find('.upload-process-inner').css('width', percent + '%');
	} catch (ex) {
		this.debug(ex);
	}
}

function uploadSuccess(file, serverData) {
	try {
        var $cnt = $('#' + this.customSettings.cnt_id);

        $cnt.find('.upload-process-outter').hide();

        if (serverData.indexOf('Error') >= 0) {
            alert(serverData.split('|')[1] || serverData);
        } else {
            
        }
        window.__debug_swfupload && window.console && console.log(serverData);
        var d = eval('(' + serverData + ')');
        if (!d) {
        	alert(serverData);
        } else if (d.err_code) {
        	alert(d.err_msg);
        } else {
        	setfileinfo(this.customSettings.cnt_id, d, true, this.customSettings);
        }
	} catch (ex) {
		this.debug(ex);
	}
}

function setfileinfo(cnt_id, d, show_succ, customSettings)
{
    var $cnt = $('#' + cnt_id);
    $cnt.find(':input').val(d.path);
    var fn = d.url.split('/').pop(), ext = fn.split('.').pop();
    fn = fn.substring(0, 6) + '....' + ext;
    var img_url = d.url;
    $cnt.find('.uploaded-img-info').hide().html('<a href="' + d.url + '" title="点击在新窗口查看图片" target="uploaded_img_preview"><img src="' + img_url + '" height="16" width="16" style="margin:0;border:none"/>&nbsp;' + fn + '</a><div style="position:absolute;top:0;border:1px solid #999;border-bottom:2px solid #666;border-right:2px solid #666;display:none;z-index:1000"><img src="' + img_url + '" style="margin:0;border:none;"/></div>').fadeIn()
    .mouseenter(function(){
        var max_size = 300, img = $(this).find('div:last img')[0];
        if (! img) return;
        if (img.width && img.height && !img.fixed) {
            var pro = img.width > img.height ? 'width' : 'height';
            if (img[pro] > max_size) {
                img[pro] = max_size;
            }
            img.fixed = true;
        }
        $(this).find('div:last').css('left', $(this).attr('offsetWidth') + 10).show();
    })
    .mouseleave(function(){
        $(this).find('div:last').hide();
    }); 
    if (show_succ && customSettings && customSettings.onsuccess) {
        customSettings.onsuccess(customSettings.name, d);
    }
}

function uploadComplete(file) {
	try {
		/*  I want the next upload to continue automatically so I'll call startUpload here */
	} catch (ex) {
		this.debug(ex);
	}
}

function uploadError(file, errorCode, message) {
	try {
        var $cnt = $('#' + this.customSettings.cnt_id);
        $cnt.find('.upload-process-outter').hide();
		switch (errorCode) {
		case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
		case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
		case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
			break;
		default:
			alert(message);
			break;
		}

		//addImage("images/" + imageName);

	} catch (ex3) {
		this.debug(ex3);
	}

}

$.fn.img_uploader = function(ext_options)
{
	var options = {
		upload_url: "/upload",
		file_post_name: 'userfile',
		post_params: {
		},

		// File Upload Settings
		file_size_limit : "512 KB",
		file_types : "*.jpg;*.jpeg;*.gif;*.png;",
		file_types_description : "Web Image Files",
		file_upload_limit : "0",
		file_queue_limit : "1",
		// Event Handler Settings - these functions as defined in Handlers.js
		//  The handlers are not part of SWFUpload but are part of my website and control how
		//  my website reacts to the SWFUpload events.
		file_queue_error_handler : fileQueueError,
		file_dialog_complete_handler : fileDialogComplete,
		upload_progress_handler : uploadProgress,
		upload_error_handler : uploadError,
		upload_success_handler : uploadSuccess,
		upload_complete_handler : uploadComplete,

		// Button Settings
		button_image_url : "/scaffold/js/img_uploader/SmallSpyGlassWithTransperancy_17x18.png",
		button_placeholder_id : "spanButtonPlaceholder",
		button_width: 100,
		button_height: 18,
		button_text : '<span class="button">选择文件</span>',
		button_text_style : '.button { font-family: Helvetica, Arial, sans-serif; font-size: 12pt; } .buttonSmall { font-size: 10pt; }',
		button_text_top_padding: 0,
		button_text_left_padding: 18,
		button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
		button_cursor: SWFUpload.CURSOR.HAND,
		
		// Flash Settings
		flash_url : "/scaffold/js/swfupload.swf",

		custom_settings : {
		},
		
		// Debug Settings
		debug: false 
	};
	
    if (!$.fn.img_uploader.instanceCount) {
        $.fn.img_uploader.instanceCount = 1;
    } else {
        $.fn.img_uploader.instanceCount++;
    }
    
    var cnt = this[0];
    var button_replaceholder_id = 'img_uploader_btn_' + $.fn.img_uploader.instanceCount;
    var cnt_id;
    if (cnt.id) {
        cnt_id = cnt.id;
    } else {
        cnt_id = 'img_uploader_cnt_' + $.fn.img_uploader.instanceCount;
        cnt.id = cnt_id;
    }

    this.html([
    '<div style="display:inline;border:solid 1px #7faaff;background-color:#c5d9ff;padding:2px;">',
        '<span id="', button_replaceholder_id, '"></span>',
        '<input type="hidden" name="', ext_options.name, '"/>',
    '</div>',
    '<div style="display:inline;margin-left:1em;position:relative" class="uploaded-img-info">',
    '</div>',
    '<div class="upload-process-outter" style="display:none;margin-left:0;margin-top:3px;width:110px;height:3px;overflow:hidden;border:1px solid green;background:#fff;">',
        '<div class="upload-process-inner" style="width:0px;height:3px;overflow:hidden;background:green"></div>',
    '</div>'
    ].join(''));

    options.button_placeholder_id = button_replaceholder_id;
    options.custom_settings.cnt_id = cnt_id;
    options.custom_settings.name = ext_options.name;

    if (ext_options.debug !== undefined) {
        options.debug = ext_options.debug;
    }
    
    if (ext_options.onsuccess) {
        options.custom_settings.onsuccess = ext_options.onsuccess;
    }

    if (ext_options.file_size_limit) {
        options.file_size_limit = ext_options.file_size_limit;
    }
    
    if (ext_options.post_params) {
        options.post_params = ext_options.post_params;
    }
    
    if (ext_options.file) {
        setfileinfo(cnt_id, ext_options.file);
    }

	var swfu = new SWFUpload(options);
};
})(jQuery);
