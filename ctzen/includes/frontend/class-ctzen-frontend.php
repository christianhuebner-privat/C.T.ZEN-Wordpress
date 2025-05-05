<?php
class CTZEN_Frontend {
    public static function init(){
        add_shortcode('ctzen', [__CLASS__,'shortcode']);
        add_action('wp_enqueue_scripts', function(){
            wp_enqueue_style('ctzen-frontend', CTZEN_URL.'assets/css/frontend.css');
            wp_enqueue_script('ctzen-frontend', CTZEN_URL.'assets/js/frontend.js', ['jquery'], '1.0', true);
        });
    }

    public static function shortcode($atts){
        global $wpdb;
        $tid   = isset($_GET['theme_id']) ? intval($_GET['theme_id']) : 0;
        $p     = $wpdb->prefix;
        $today = date_i18n('Y-m-d');

        // Bedingung: Thema muss gestartet sein und noch nicht abgelaufen
        $date_where = $wpdb->prepare(
            "(start_date IS NULL OR start_date <= %s)
             AND (end_date   IS NULL OR end_date   >= %s)",
            $today, $today
        );

        if ($tid) {
            // Prüfen, ob das gewählte Thema aktuell sichtbar ist
            $theme = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$p}ctzen_themes
                 WHERE id = %d
                   AND {$date_where}",
                $tid
            ) );
            if (! $theme) {
                return '<p>Dieses Thema ist derzeit nicht verfügbar.</p>';
            }

            // Sichtbare Kind-Themen
            $children = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$p}ctzen_themes
                 WHERE parent_id = %d
                   AND {$date_where}
                 ORDER BY menu_order",
                $tid
            ) );

            // Neueste Version der Beschreibung
            $desc = $wpdb->get_var( $wpdb->prepare(
                "SELECT description
                   FROM {$p}ctzen_desc_versions
                  WHERE theme_id = %d
               ORDER BY vid DESC
                  LIMIT 1",
                $tid
            ) );

            // Neueste Version der Meinung
            $op = $wpdb->get_var( $wpdb->prepare(
                "SELECT opinion
                   FROM {$p}ctzen_opinion_versions
                  WHERE theme_id = %d
               ORDER BY vid DESC
                  LIMIT 1",
                $tid
            ) );

            // Neueste Version der Aktuelles-Liste (JSON)
            $dat = $wpdb->get_var( $wpdb->prepare(
                "SELECT data
                   FROM {$p}ctzen_aktuelles_versions
                  WHERE theme_id = %d
               ORDER BY vid DESC
                  LIMIT 1",
                $tid
            ) );
            $news = $dat ? json_decode( $dat, true ) : [];

        } else {
            // Sichtbare Root-Themen
            $children = $wpdb->get_results(
                "SELECT * FROM {$p}ctzen_themes
                 WHERE parent_id IS NULL
                   AND {$date_where}
                 ORDER BY menu_order"
            );
            $theme    = null;
            $desc     = '';
            $op       = '';
            $news     = [];
        }

        // Ausgabe
        ob_start(); ?>
        <div class="ctz-themes">
          <ul class="ctz-theme-list">
            <?php foreach($children as $c): ?>
            <li>
              <a href="<?php echo esc_url(add_query_arg('theme_id', $c->id)); ?>">
                <?php echo esc_html($c->title); ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>

          <?php if ($tid && ! empty($theme)): ?>
          <div class="ctz-content">
            <h2><?php echo esc_html($theme->title); ?></h2>
            <div class="ctz-desc"><?php echo wp_kses_post($desc); ?></div>
            <div class="ctz-opin"><?php echo wp_kses_post($op); ?></div>

            <?php if (! empty($news)): ?>
            <div class="ctz-news">
              <?php foreach($news as $n): ?>
              <div class="ctz-news-item">
                <div class="ctz-news-date"><?php echo esc_html($n['date']); ?></div>
                <div class="ctz-news-content"><?php echo wp_kses_post($n['content']); ?></div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
