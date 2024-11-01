function deleteSingle(id){
    jQuery.ajax({
        url : ajax_object.ajax_url+'?action=delete_single',
        type : 'POST',
        data : {
            id:id,
        },
        success:function( response ) {
            jQuery('#v4_row_'+id).hide();
        }
    });  
}

function deleteEverything(){
    jQuery.ajax({
        url : ajax_object.ajax_url+'?action=delete_everything',
        type : 'POST',
        data : {
            
        },
        success:function( response ) {

            jQuery('.v4_all_rows').hide();
        }
    });     
}

jQuery(document).ready( function(){  

});



