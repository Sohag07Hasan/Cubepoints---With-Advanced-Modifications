jQuery(document).ready(function($){	
	
	$('.ui-autocomplete').css({'max-height' : '200px', 'overflow-y' : 'auto', 'overflow-x' : ' hidden', 'padding-right' : '20px'});
	
	$('.cp_donate_button').click(function(){		
			
		$('#cp_recipient').autocomplete({source:CPAUTO.authors.split('-')});
		
	});
	
});
