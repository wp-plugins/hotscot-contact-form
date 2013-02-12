var textborder,selectborder,checkboxborder,currOptionName;


function getBorderDetails(){
	textborder = jQuery('.hcf_req_text').css('border-color') || '#ccc';
	selectborder = jQuery('.hcf_req_select').css('border-color') || '#ccc';
	checkboxborder = jQuery('.hcf_req_check').css('border') || '';
}


jQuery(document).ready(function(){
	getBorderDetails();
    jQuery('.hcf-form').submit(function(){
    	var isValid = true;
    	jQuery('.hcf_req_text').each(function(){

    		if(jQuery(this).val() == ''){
    			isValid = false;
    			jQuery(this).css('border-color', 'red');
    		}else{
    			if(jQuery(this).css('border-color') == 'rgb(255, 0, 0)'){
    				jQuery(this).css('border-color', textborder);
    			}
    		}
    	});

    	jQuery('.hcf_req_select').each(function(){
    		if(jQuery(this).val() == '-1'){
    			isValid = false;
    			jQuery(this).css('border-color', 'red');
    		}else{
    			if(jQuery(this).css('border-color') == 'rgb(255, 0, 0)'){
    				jQuery(this).css('border-color', selectborder);
    			}
    		}
    	});

    	jQuery('.hcf_req_check').each(function(){
    		if(currOptionName == '' || currOptionName != jQuery(this).attr('name')){
    			currOptionName = jQuery(this).attr('name');
    		}

    		if(jQuery('input[name="' + currOptionName + '"]:checked').length == 0){
    			isValid = false;
    			jQuery(this).parent('label').css('border', '1px solid red');
    		}else{
    			console.log(jQuery(this).parent('label').css('border'));
				if(jQuery(this).parent('label').css('border') == '1px solid rgb(255, 0, 0)'){
    				jQuery(this).parent('label').css('border', checkboxborder);
    			}
    		}
    	});

    	return isValid;
    });
});