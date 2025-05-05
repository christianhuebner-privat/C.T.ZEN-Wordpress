jQuery(function($){
    // News hinzufügen/entfernen
    var container = $('#aktuelles-container');
    $('#ctz-add-news').click(function(e){
      e.preventDefault();
      var idx = container.children('.ctz-news-row').length;
      container.append('\
        <div class="ctz-news-row">\
          <input type="date" name="news['+idx+'][date]" />\
          <textarea name="news['+idx+'][content]" rows="2"></textarea>\
          <a href="#" class="ctz-remove-news">Entfernen</a>\
        </div>');
    });
    container.on('click','.ctz-remove-news',function(e){
      e.preventDefault();
      $(this).closest('.ctz-news-row').remove();
    });
  
    // Version-Auswahl: lädt per JS den jeweiligen Inhalt
    var descMap = {}, opMap = {}, aktMap = {};
    <?php if(!empty($desc_versions)): foreach($desc_versions as $v): 
      $row = $wpdb->get_row("SELECT description FROM {$wpdb->prefix}ctzen_desc_versions WHERE vid={$v->vid}");
    ?>
    descMap[<?php echo $v->vid;?>] = <?php echo json_encode($row->description);?>;
    <?php endforeach; endif; ?>
  
    $('#desc-versions').change(function(){
      $('#description').val( descMap[this.value] );
    });
  
    <?php if(!empty($op_versions)): foreach($op_versions as $v):
      $row = $wpdb->get_row("SELECT opinion FROM {$wpdb->prefix}ctzen_opinion_versions WHERE vid={$v->vid}");
    ?>
    opMap[<?php echo $v->vid;?>] = <?php echo json_encode($row->opinion);?>;
    <?php endforeach; endif; ?>
  
    $('#op-versions').change(function(){
      $('#opinion').val( opMap[this.value] );
    });
  
    <?php if(!empty($akt_versions)): foreach($akt_versions as $v):
      $row = $wpdb->get_row("SELECT data FROM {$wpdb->prefix}ctzen_aktuelles_versions WHERE vid={$v->vid}");
    ?>
    aktMap[<?php echo $v->vid;?>] = <?php echo json_encode(json_decode($row->data,true));?>;
    <?php endforeach; endif; ?>
  
    $('#akt-versions').change(function(){
      var list = aktMap[this.value]||[],
          html = '';
      list.forEach(function(n,i){
        html += '<div class="ctz-news-row">'
             +  '<input type="date" name="news['+i+'][date]" value="'+n.date+'">'
             +  '<textarea name="news['+i+'][content]" rows="2">'+n.content+'</textarea>'
             +  '<a href="#" class="ctz-remove-news">Entfernen</a>'
             +  '</div>';
      });
      $('#aktuelles-container').html(html);
    });
  });
  