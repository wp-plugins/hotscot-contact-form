//The created form
var formObject = {
    formSettings: '',
    formElements: [],
    emailSettings: ''
};

/**
 * Takes the user created form and serializes it into a json object which can be stored
 */
function parseCreatedForm(){
    var requiredTickbox, isClientEmailTickbox;
    var elem = {};

    //Clear out the main formElements object
    formObject.formElements = [];


    //Loop through each of the placed form elements, extracting the relevent details
    jQuery('#form-elements div.hcf-edit-box').each(function(index,value){
        elem = {}; //Object to hold all element details

        //Get the element type (this is defined on the class of the TD in the row)
        var eType = jQuery(jQuery(value).parent('td').siblings('td')[1]).attr('class');

        //Depending on the type we need to record different information
        switch(eType){
        	case 'hcf-text-box':
        		elem.elementType = "text";
        		//Note to self: children() is only 1 level down, find() can traverse down multiple levels to select descendant elements (grandchildren, etc.)
        		requiredTickbox = jQuery(value).find('input[name="required"]');
        		if( (requiredTickbox.attr('checked') !== undefined) && (requiredTickbox.attr('checked') == "checked") ){
		            elem.isElementRequired = true;
		        }else{
		            elem.isElementRequired = false;
		        }
		        nolinksTickbox = jQuery(value).find('input[name="nolinks"]');
		        if( (nolinksTickbox.attr('checked') !== undefined) && (nolinksTickbox.attr('checked') == "checked") ){
		            elem.nolinks = true;
		        }else{
		            elem.nolinks = false;
		        }
        		break;
        	case 'hcf-email-box':
        		elem.elementType = "email";
        		//Note to self: children() is only 1 level down, find() can traverse down multiple levels to select descendant elements (grandchildren, etc.)
        		requiredTickbox = jQuery(value).find('input[name="required"]');
        		if( (requiredTickbox.attr('checked') !== undefined) && (requiredTickbox.attr('checked') == "checked") ){
		            elem.isElementRequired = true;
		        }else{
		            elem.isElementRequired = false;
		        }

		        //Send client email to this email address?
        		isClientEmailTickbox = jQuery(value).find('input[name="clientemail"]');
        		if( (isClientEmailTickbox.attr('checked') !== undefined) && (isClientEmailTickbox.attr('checked') == "checked") ){
		            elem.sendFormToThisAddress = true;
		        }else{
		            elem.sendFormToThisAddress = false;
		        }
        		break;
        	case 'hcf-checkbox':
        		elem.elementType = "checkbox";
        		elem.elementOptions = jQuery(value).find('input[name="options"]').val()  || '';
        		//Note to self: children() is only 1 level down, find() can traverse down multiple levels to select descendant elements (grandchildren, etc.)
        		requiredTickbox = jQuery(value).find('input[name="required"]');
        		if( (requiredTickbox.attr('checked') !== undefined) && (requiredTickbox.attr('checked') == "checked") ){
		            elem.isElementRequired = true;
		        }else{
		            elem.isElementRequired = false;
		        }
        		break;
        	case 'hcf-select':
        		elem.elementType = "select";
        		elem.elementOptions = jQuery(value).find('input[name="options"]').val()  || '';
        		//Note to self: children() is only 1 level down, find() can traverse down multiple levels to select descendant elements (grandchildren, etc.)
        		requiredTickbox = jQuery(value).find('input[name="required"]');
        		if( (requiredTickbox.attr('checked') !== undefined) && (requiredTickbox.attr('checked') == "checked") ){
		            elem.isElementRequired = true;
		        }else{
		            elem.isElementRequired = false;
		        }
        		break;
        	case 'hcf-textarea':
        		elem.elementType = "textarea";
        		elem.elementRows = jQuery(value).find('input[name="rows"]').val()  || '';
        		elem.elementCols = jQuery(value).find('input[name="cols"]').val()  || '';
        		//Note to self: children() is only 1 level down, find() can traverse down multiple levels to select descendant elements (grandchildren, etc.)

        		requiredTickbox = jQuery(value).find('input[name="required"]');
        		if( (requiredTickbox.attr('checked') !== undefined) && (requiredTickbox.attr('checked') == "checked") ){
		            elem.isElementRequired = true;
		        }else{
		            elem.isElementRequired = false;
		        }

		        nolinksTickbox = jQuery(value).find('input[name="nolinks"]');
		        if( (nolinksTickbox.attr('checked') !== undefined) && (nolinksTickbox.attr('checked') == "checked") ){
		            elem.nolinks = true;
		        }else{
		            elem.nolinks = false;
		        }
        		break;
        	case 'hcf-submit':
	        	elem.elementType = "submit";
	        	elem.elementValue = jQuery(value).find('input[name="value"]').val()  || '';

	        	useCaptcha = jQuery(value).find('input[name="captcha"]');
        		if( (useCaptcha.attr('checked') !== undefined) && (useCaptcha.attr('checked') == "checked") ){
		            elem.useCaptcha = true;
		        }else{
		            elem.useCaptcha = false;
		        }
	        	break;
        }

        //Generic information for all form elements
        //Note: we are triming the id and name to avoid spaces, you're on your own with classes and lebels
        elem.elementID = jQuery.trim(jQuery(value).find('input[name="id"]').val()) || '';
        elem.elementName = jQuery.trim(jQuery(value).find('input[name="name"]').val())  || '';
        elem.elementClass = jQuery(value).find('input[name="class"]').val() || '';
        elem.elementLabel = jQuery(value).find('input[name="label"]').val()  || '';

        //Append our new form element to our list
        formObject.formElements.push(elem);
    });
}

/**
 * Setup the daggable/droppable form elaments
 */
function setupFormElements(){
    jQuery( "#control-list>tbody>tr" ).draggable({
        appendTo: "body",
        helper: "clone"
    });

    jQuery( "#form-elements").droppable({
        activeClass: "ui-state-highlight",
        hoverClass: "ui-state-highlight",
        accept: ":not(.ui-sortable-helper)",
        drop: function( event, ui ) {
            jQuery( this ).find( ".placeholder" ).parent('tr').remove();

            jQuery(this).find('tbody:first').append('<tr>' + ui.draggable.html() + '</tr>');

        }
    });

	var fixHelper = function(e, ui) {
		ui.children().each(function() {
			jQuery(this).width(jQuery(this).width());
		});
		return ui;
	};

	jQuery("#form-elements>tbody").sortable({
        placeholder: "ui-state-highlight",
        helper: fixHelper,
        sort: function(){
        	jQuery('#form-elements').removeClass('ui-state-highlight');
        }
    }).disableSelection();


    //Handle email tickboxes
    jQuery('#form-elements').on('change', 'input[name="clientemail"]', function(e){
    	var reqbox = jQuery(this).parents('tbody:first').find('input[name="required"]');

    	if(jQuery(this).is(':checked')){
    		jQuery(reqbox).attr({
    			checked: 'checked',
    			disabled: true
    		})

    	}else{
			jQuery(reqbox).removeAttr('checked disabled');
    	}
    });

    //When the name changes, update the corrisponding td
    jQuery('#form-elements').on('keyup', 'input[name="label"]', function(e){
    	jQuery(this).parents('tr:last').children('td:first').text(jQuery(this).val());
    });
}

/**
 * Setup the misc click handlers
 */
function setupMiscClickHandlers(){
    // Setup click events for the form edit/remove links
    // Note: doing it like this (with the .on() fn) means that it will work even when links
    //       are dynamicaly created in the future.

    //Form element "Edit" link click handler
    jQuery(document).on('click', 'a.hcf-edit', function(event){
        event.preventDefault();

        var editDiv = jQuery(event.target).parents('span').siblings('.hcf-edit-box');
        editDiv.toggle('fast', function(){
        	if(jQuery(this).css('display') == 'block'){
        		jQuery(event.target).text('Hide');
        	}else{
        		jQuery(event.target).text('Edit');
        	}
        });
    });

    //Form element "Remove" link click handler
    jQuery(document).on('click', 'a.hcf-remove', function(event){
        event.preventDefault();
        if(confirm("Delete field?")){
	        //Remove the desired form element
	        jQuery(this).parents('tr').remove();

	        //If there are no more form elements, add in the placeholder
	        if(jQuery('#form-elements tr').size() == 0){
	            jQuery('#form-elements').html('<tr><td colspan="3">Drag form elements here</td></tr>');
	        }
	    }
    });

    //make sure that all form submit buttons don't actually submit
    jQuery(document).on('click', '.no-click-through', function(event){ event.preventDefault(); });
}

/* Hotscot Contact Form - Form Builder Script */
jQuery(document).ready(function(){

    setupFormElements();
    setupMiscClickHandlers();

    //When the user clicks add/update, we want to create a json object in which to save our created form
    jQuery('#edit_item').submit(function(){
    	var isValid = true;
        parseCreatedForm();

        //Form settings
        var formSettings = {};
        formSettings.formName = jQuery('#form-name').val();
        formSettings.formStyle = jQuery('#form-style option:selected').val();
        formSettings.thanksPage = jQuery('#thankspage option:selected').val();

        formObject.formSettings = formSettings;

        var emailSettings = {};
        //Client Email Settings
        emailSettings.clientTemplate = jQuery("#clientTemplate").val();
        emailSettings.clientHeaders = jQuery("#clientHeaders").val();
        emailSettings.clientSubject = jQuery("#clientSubject").val();

        //Check for html email.
        if((jQuery("#clientUseHTMLEmail").attr('checked') !== undefined) && (jQuery("#clientUseHTMLEmail").attr('checked') == "checked")){
        	emailSettings.clientUseHTMLEmail = true;
        }else{
			emailSettings.clientUseHTMLEmail = false;
        }


        //Owner email settings
        emailSettings.ownerTemplate = jQuery("#ownerTemplate").val();
        emailSettings.ownerEmail = jQuery("#ownerEmail").val();
        emailSettings.ownerHeaders = jQuery("#ownerHeaders").val();
        emailSettings.ownerSubject = jQuery("#ownerSubject").val();

		//Check for html email.
        if((jQuery("#ownerUseHTMLEmail").attr('checked') !== undefined) && (jQuery("#ownerUseHTMLEmail").attr('checked') == "checked")){
        	emailSettings.ownerUseHTMLEmail = true;
        }else{
			emailSettings.ownerUseHTMLEmail = false;
        }

        formObject.emailSettings = emailSettings;

        jQuery('#hcf-form-object').val(JSON.stringify(formObject));

        //Before submuitting validate the form to make sure the id's names have no spaces
        isValid = validateFormNamesAndIDs();

        return isValid;
    });
});


//Check ID's and Name's for spaces
function validateFormNamesAndIDs(){
	var isValid = true;
	var validationMsg = '';

	for(var i = 0; i < formObject.formElements.length; i++){

		//Check ID for space
		if(formObject.formElements[i].elementID != ''){
			if(/\s/.test(formObject.formElements[i].elementID)){
				isValid = false;
				validationMsg = validationMsg + "ID: \"" + formObject.formElements[i].elementID + "\" - Element ID's are not allowed to contain a space<br/>";
			}
		}

		//Check name for Space
		if(formObject.formElements[i].elementName != ''){
			if(/\s/.test(formObject.formElements[i].elementName)){
				isValid = false;
				validationMsg = validationMsg + "Name: \"" + formObject.formElements[i].elementName + "\" - Element name are not allowed to contain a space<br/>";
			}
		}
	}

	if(!isValid) displayErrorMsg(validationMsg);
	return isValid;
}

function displayErrorMsg(msg){
	if(jQuery('.updated').length == 1){
		jQuery('.updated').hide();
	}

	if(jQuery('#hsjserror').length == 1){
		//update message
		jQuery('#hsjserror .error p').text(msg);
	}else{ //add to page
		jQuery('.hcf-wrap h2:first').after('<div id="hsjserror" class="error"><p>' + msg + '</p></div>');
		jQuery('body').scrollTop(0);
	}

}