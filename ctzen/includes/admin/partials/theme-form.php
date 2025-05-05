<?php global $wpdb; ?>
<div class="wrap">
  <h1><?php echo $theme ? 'Thema bearbeiten' : 'Neues Thema'; ?></h1>
  <form method="post" id="ctzen-theme-form">
    <?php wp_nonce_field('ctzen_save_theme'); ?>
    <?php if ($theme): ?>
      <input type="hidden" name="id" value="<?php echo intval($theme->id); ?>">
    <?php endif; ?>
    <table class="form-table">
      <!-- Titel, Parent, Reihenfolge, Datum -->
      <tr>
        <th><label for="title">Titel</label></th>
        <td><input type="text" name="title" id="title" class="regular-text" required value="<?php echo esc_attr($theme->title ?? ''); ?>"></td>
      </tr>
      <tr>
        <th><label for="parent_id">Parent-Thema</label></th>
        <td>
          <select name="parent_id" id="parent_id">
            <option value="">– Kein Parent –</option>
            <?php foreach ($all as $p): ?>
              <option value="<?php echo esc_attr($p->id); ?>" <?php selected($theme->parent_id ?? '', $p->id); ?>><?php echo esc_html($p->title); ?></option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
      <tr>
        <th><label for="menu_order">Reihenfolge</label></th>
        <td><input type="number" name="menu_order" id="menu_order" value="<?php echo intval($theme->menu_order ?? 0); ?>"></td>
      </tr>
      <tr>
        <th><label for="start_date">Startdatum</label></th>
        <td><input type="date" name="start_date" id="start_date" value="<?php echo esc_attr($theme->start_date ?? ''); ?>"></td>
      </tr>
      <tr>
        <th><label for="end_date">Enddatum</label></th>
        <td><input type="date" name="end_date" id="end_date" value="<?php echo esc_attr($theme->end_date ?? ''); ?>"></td>
      </tr>
      <!-- Beschreibung -->
      <tr>
        <th><label for="description">Beschreibung (HTML)</label></th>
        <td>
          <textarea name="description" id="description" class="large-text" rows="5"><?php echo esc_textarea($current['description']); ?></textarea>
          <button type="button" class="button ctz-clear-field" data-target="#description">Entfernen</button>
          <?php if ($theme): ?>
          <div class="ctz-version-dropdown">
            <label for="desc-versions">Version wählen:</label>
            <select id="desc-versions">
              <?php foreach ($desc_versions as $v):
                $user = get_userdata($v->author_id);
                $name = $user ? $user->display_name : 'User#'.$v->author_id;
                $content = $wpdb->get_var($wpdb->prepare("SELECT description FROM {$wpdb->prefix}ctzen_desc_versions WHERE vid=%d", $v->vid));
              ?>
              <option value="<?php echo esc_attr($v->vid); ?>" data-description="<?php echo esc_attr($content); ?>">
                <?php echo esc_html(date('Y-m-d H:i', strtotime($v->created_at))).' – '.esc_html($name); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
        </td>
      </tr>
      <!-- Meinung -->
      <tr>
        <th><label for="opinion">Meinung (HTML)</label></th>
        <td>
          <textarea name="opinion" id="opinion" class="large-text" rows="5"><?php echo esc_textarea($current['opinion']); ?></textarea>
          <button type="button" class="button ctz-clear-field" data-target="#opinion">Entfernen</button>
          <?php if ($theme): ?>
          <div class="ctz-version-dropdown">
            <label for="op-versions">Version wählen:</label>
            <select id="op-versions">
              <?php foreach ($op_versions as $v):
                $user = get_userdata($v->author_id);
                $name = $user ? $user->display_name : 'User#'.$v->author_id;
                $content = $wpdb->get_var($wpdb->prepare("SELECT opinion FROM {$wpdb->prefix}ctzen_opinion_versions WHERE vid=%d", $v->vid));
              ?>
              <option value="<?php echo esc_attr($v->vid); ?>" data-opinion="<?php echo esc_attr($content); ?>">
                <?php echo esc_html(date('Y-m-d H:i', strtotime($v->created_at))).' – '.esc_html($name); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
        </td>
      </tr>
      <!-- Aktuelles -->
      <tr>
        <th>Aktuelles</th>
        <td>
          <div id="aktuelles-container">
            <?php foreach ($current['aktuelles'] as $i => $n): ?>
            <div class="ctz-news-row">
              <input type="date" name="news[<?php echo $i; ?>][date]" class="ctz-news-date-field" value="<?php echo esc_attr($n['date']); ?>">
              <textarea name="news[<?php echo $i; ?>][content]" class="large-text" rows="3"><?php echo esc_textarea($n['content']); ?></textarea>
              <button type="button" class="button ctz-remove-news">Entfernen</button>
            </div>
            <?php endforeach; ?>
          </div>
          <p><button type="button" id="ctz-add-news" class="button">News hinzufügen</button></p>
        </td>
      </tr>
    </table>
    <p><button type="submit" name="action" value="save" class="button button-primary">Speichern</button></p>
  </form>
</div>

<script>
jQuery(function($) {
  // Clear field buttons for description and opinion
  $('.ctz-clear-field').on('click', function() {
    var target = $(this).data('target');
    $(target).val('');
  });

  // News add/remove
  var container = $('#aktuelles-container');
  $('#ctz-add-news').on('click', function(e) {
    e.preventDefault();
    var idx = container.children('.ctz-news-row').length;
    var row = '<div class="ctz-news-row">'
            + '<input type="date" name="news['+idx+'][date]" class="ctz-news-date-field" />'
            + '<textarea name="news['+idx+'][content]" class="large-text" rows="3"></textarea>'
            + '<button type="button" class="button ctz-remove-news">Entfernen</button>'
            + '</div>';
    container.append(row);
  });
  container.on('click', '.ctz-remove-news', function(e) {
    e.preventDefault();
    $(this).closest('.ctz-news-row').remove();
  });

  // Version dropdowns
  $('#desc-versions').on('change', function() {
    var val = $(this).find(':selected').data('description') || '';
    $('#description').val(val);
  }).trigger('change');
  $('#op-versions').on('change', function() {
    var val = $(this).find(':selected').data('opinion') || '';
    $('#opinion').val(val);
  }).trigger('change');
});
</script>
