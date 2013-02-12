/**  Chose which form to embed into the editor **/
jQuery(document).ready(function(){
	jQuery('#hcf-form-selector').click(function(){
		tinyMCE.activeEditor.execCommand("mceInsertContent",false," [hcf-form id=\"" + jQuery('input[name=formselect]:checked').val() + "\"] ")
		tb_remove();
	});
});