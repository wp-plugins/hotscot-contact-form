var textborder,selectborder,checkboxborder,currOptionName;


function getBorderDetails(){
	textborder = jQuery('.hcf_req_text').css('border-color') || '#ccc';
	selectborder = jQuery('.hcf_req_select').css('border-color') || '#ccc';
	checkboxborder = jQuery('.hcf_req_check input[type="checkbox"]:eq(0)').css('border') || '';
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

        //For each group of tickboxes, if it's a req field, make sure at least one is ticked...
    	jQuery('.hcf_req_check').each(function(){
            var oneTickChecked = false;

    		var tickBoxes = jQuery(this).find('input[type="checkbox"]');


            for(var i = 0; i < tickBoxes.length; i++){
                if(jQuery(tickBoxes[i]).is(':checked')){
                    oneTickChecked = true;
                }
            }

            if(!oneTickChecked){
                console.log('false');
                isValid = false;
                jQuery(this).css('border', '1px solid red');
            }else{

                jQuery(this).css('border', '0px solid red');

            }
    	});

        //If all fields havent been filled in, show error msg
        if(!isValid){
            if(jQuery('.hcf-error').length == 0){
                jQuery(this).before('<div class="hcf-error"><p><strong>Error:</strong> Please fill in all required fields.</p></div>');
            }else{
                jQuery('.hcf-error p').html('<strong>Error:</strong> Please fill in all required fields.');
            }
        }

    	return isValid;
    });
});