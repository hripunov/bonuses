{include file=$field->getOriginalTemplate() field=$elem.__bonuses_units} {$elem.__bonuses_units_type->formView()}
<div id="hiddenBonusRow" style="display: none;"></div>
<script type="text/javascript">
   $(function(){
       $("input[name='doedit[]'][value='bonuses_units']").on('click', function(){ //Если изменилось состояние открытия редактирования бонусов
           if ($(this).prop('checked')){ //Если выбрано редактирование
               $("#hiddenBonusRow").append('<input type="checkbox" value="bonuses_units_type" name="doedit[]" checked="checked" class="doedit" alt="">');
           }else{
               $("#hiddenBonusRow").empty();
           }     
       }); 
            
   });
</script>