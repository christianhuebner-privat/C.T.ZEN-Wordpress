<?php global $wpdb; ?>
<div class="wrap">
  <h1><?php echo $theme ? 'Thema bearbeiten' : 'Neues Thema'; ?></h1>
  <form method="post">
    <?php wp_nonce_field('ctzen_save_theme'); ?>
    <?php if ($theme): ?>
      <input type="hidden" name="id" value="<?php echo intval($theme->id); ?>">
    <?php endif; ?>
    <table class="form-table">
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
      <!-- Reihenfolge, Start/End-Datum etc. -->
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
      <!-- Beschreibung Versionen -->
      <tr>
        <th><label for="description">Beschreibung (HTML)</label></th>
        <td>
          <select id="desc-versions">
            <?php foreach ($desc_versions as $v):
              $user = get_userdata($v->author_id);
              $name = $user ? $user->display_name : 'User#' . $v->author_id;
              $content = $wpdb->get_var($wpdb->prepare("SELECT description FROM {$wpdb->prefix}ctzen_desc_versions WHERE vid=%d", $v->vid));
            ?>
            <option value="<?php echo esc_attr($v->vid); ?>" data-description="<?php echo esc_attr($content); ?>">
              <?php echo esc_html(date('Y-m-d H:i', strtotime($v->created_at))) . ' – ' . esc_html($name); ?>
            </option>
            <?php endforeach; ?>
          </select><br>
          <textarea name="description" id="description" class="large-text" rows="5"><?php echo esc_textarea($current['description']); ?></textarea>
        </td>
      </tr>
      <!-- Meinung Versionen -->
      <tr>
        <th><label for="opinion">Meinung (HTML)</label></th>
        <td>
          <select id="op-versions">
            <?php foreach ($op_versions as $v):
              $user = get_userdata($v->author_id);
              $name = $user ? $user->display_name : 'User#' . $v->author_id;
              $content = $wpdb->get_var($wpdb->prepare("SELECT opinion FROM {$wpdb->prefix}ctzen_opinion_versions WHERE vid=%d", $v->vid));
            ?>
            <option value="<?php echo esc_attr($v->vid); ?>" data-opinion="<?php echo esc_attr($content); ?>">
              <?php echo esc_html(date('Y-m-d H:i', strtotime($v->created_at))) . ' – ' . esc_html($name); ?>
            </option>
            <?php endforeach; ?>
          </select><br>
          <textarea name="opinion" id="opinion" class="large-text" rows="5"><?php echo esc_textarea($current['opinion']); ?></textarea>
        </td>
      </tr>
      <!-- Aktuelles wie gehabt -->
      <tr>
        <th>Aktuelles</th>
        <td>
          <select id="akt-versions">
            <?php foreach ($akt_versions as $v):
              $user = get_userdata($v->author_id);
              $name = $user ? $user->display_name : 'User#' . $v->author_id;
            ?>
            <option value="<?php echo esc_attr($v->vid); ?>"><?php echo esc_html(date('Y-m-d H:i', strtotime($v->created_at))) . ' – ' . esc_html($name); ?></option>
            <?php endforeach; ?>
          </select>
          <div id="aktuelles-container">
            <?php foreach ($current['aktuelles'] as $i => $n): ?>
            <div class="ctz-news-row">
              <input type="date" name="news[<?php echo $i; ?>][date]" value="<?php echo esc_attr($n['date']); ?>">
              <textarea name="news[<?php echo $i; ?>][content]" rows="2"><?php echo esc_textarea($n['content']); ?></textarea>
              <a href="#" class="ctz-remove-news">Entfernen</a>
            </div>
            <?php endforeach; ?>
          </div>
          <p><button id="ctz-add-news" class="button">News hinzufügen</button></p>
        </td>
      </tr>
    </table>
    <p><button type="submit" name="action" value="save" class="button button-primary">Speichern</button></p>
  </form>
</div>

<script>
jQuery(function($) {
  // Beschreibung-Version laden
  $('#desc-versions').on('change', function() {
    var val = $(this).find(':selected').data('description') || '';
    $('#description').val(val);
  }).trigger('change');
  // Meinung-Version laden
  $('#op-versions').on('change', function() {
    var val = $(this).find(':selected').data('opinion') || '';
    $('#opinion').val(val);
  }).trigger('change');
  // Aktuelles bleibt unverändert (kann bei Bedarf ähnlich umgesetzt werden)
});
</script>
