function getEditorSkinColorList(skin_name,selected_colorset,type,testid){
    if(skin_name.length>0){
        type = type || 'document';
        var response_tags = new Array('error','message','colorset');
        exec_xml('editor','dispEditorSkinColorset',{skin:skin_name},resultGetEditorSkinColorList,response_tags,{'selected_colorset':selected_colorset,'type':type,'testid':testid});
    }
}

function resultGetEditorSkinColorList(ret_obj,response_tags, params) {
    var selectbox = null;
	jQuery(function($){
		if(params.testid){
			selectbox = $("#"+params.testid).parent('label').next('select');
		}else{
			selectbox = (params.type == 'document') ? $('select[name=sel_editor_colorset]') : $('select[name=sel_comment_editor_colorset]');
		}
		selectbox.html('');

		if(params.type == 'document'){
			$("select[name=sel_editor_colorset]").hide()
				.removeAttr('name');
			selectbox.attr('name','sel_editor_colorset');			
		}else{
			$("select[name=sel_comment_editor_colorset]").hide()
				.removeAttr('name');			
			selectbox.attr('name','sel_comment_editor_colorset');			
		}	

		if(ret_obj['error'] == 0 && ret_obj.colorset){	
			var it = new Array();
			var items = ret_obj['colorset']['item'];	
			if(typeof(items[0]) == 'undefined'){
				it[0] = items;
			}else{
				it = items;
			}			
			var selectAttr = "";
			for(var i=0;i<it.length;i++){
				selectbox.append($('<option value="'+it[i].name+'" >'+it[i].title+'</option>'));				
			}
			selectbox.show();
		}else{
			selectbox.hide();
			selectbox.html('');
		}
	});
}


jQuery(function($){
    var fontPreview = $('.fontPreview');
    var fontRadio = $('.fontSelector>:radio');
    var checkedFont = fontRadio.filter(':checked').next('label').css('fontFamily');
    fontPreview.css('fontFamily',checkedFont);
    fontRadio.change(function(){
        var myFont = $(this).next('label').css('fontFamily');
        fontPreview.css('fontFamily',myFont);
    });
    var changedSize = $('#fontSize').val();
    fontPreview.css('fontSize',changedSize+'px');
    $('#fontSize').keyup(function(){
        var mySize = $(this).val();
        fontPreview.css('fontSize',mySize+'px');
        if(mySize.length==0){ fontPreview.css('fontSize','') };
        if(mySize>28){
            alert('The font is too big.');
            $(this).val('28');
            fontPreview.css('fontSize','28px');
        };
    });
});