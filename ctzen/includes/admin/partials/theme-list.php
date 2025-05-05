<div class="wrap">
  <h1>C.T.ZEN Themen <a href="<?php echo admin_url('?page=ctzen_themes&action=add'); ?>" class="page-title-action">Neu hinzufügen</a></h1>
  <table class="wp-list-table widefat fixed striped">
    <thead><tr>
      <th>Titel</th>
      <th>Parent</th>
      <th>Reihenfolge</th>
      <th>Aktionen</th>
    </tr></thead>
    <tbody>
    <?php foreach($themes as $t): ?>
      <tr>
        <td><?php echo esc_html($t->title); ?></td>
        <td><?php echo $t->parent_id?:'–'; ?></td>
        <td><?php echo esc_html($t->menu_order); ?></td>
        <td>
          <a href="<?php echo wp_nonce_url(admin_url("?page=ctzen_themes&action=edit&id={$t->id}"),'ctzen_save_theme'); ?>">Bearbeiten</a> |
          <a href="<?php echo wp_nonce_url(admin_url("?page=ctzen_themes&action=delete&id={$t->id}"),'ctzen_delete_theme'); ?>" onclick="return confirm('Wirklich löschen?');">Löschen</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
