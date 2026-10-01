jQuery(function($){
  $(document).on('input', '.tp-icon-input', function(){
    var cls = String($(this).val() || '').trim().replace(/[^a-zA-Z0-9_\-\s]/g,'');
    $(this).closest('.tp-icon-field').find('.tp-icon-preview').attr('class','tp-icon-preview '+cls);
  });
  $(document).on('click','.tp-remove-custom',function(){ $(this).closest('.tp-custom-row').remove(); });
  $('#tp-add-custom-tab').on('click',function(){
    var template=document.getElementById('tp-custom-template');
    if(!template) return;
    var key='custom_'+Date.now();
    var html=template.innerHTML.replaceAll('__KEY__', key);
    $('#tp-custom-tabs').append(html);
  });
});
