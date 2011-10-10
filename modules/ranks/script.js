jQuery(document).ready(function($){
	$("#cp_module_media_button").click(function(){
		$('html').addClass('Image');
		formfield = $('#cp_module_ranks_logo').attr('name');
		
		//using wp ui library function
		tb_show('','media-upload.php?type=image&TB_iframe=true');
		return false;
	});
	
	//saving default object
	window.original_send_to_editor = window.send_to_editor;
	window.send_to_editor = function(html){
		var fileurl;
		if(formfield != null){
			fileurl = $('img',html).attr('src');
			$('#cp_module_ranks_logo').val(fileurl);
			tb_remove();
			$('html').removeClass('Image');
			formfield = null;
		}
		else{
			window.original_send_to_editor(html);
		}
	};
	
});
