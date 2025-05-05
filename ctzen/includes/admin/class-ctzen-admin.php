<?php
class CTZEN_Admin {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    public static function add_menu() {
        add_menu_page(
            'C.T.ZEN Themen',
            'C.T.ZEN',
            'manage_options',
            'ctzen_themes',
            [__CLASS__, 'page'],
            'dashicons-welcome-learn-more',
            20
        );
    }

    public static function enqueue_assets($hook) {
        if (strpos($hook, 'ctzen_themes') === false) return;
        wp_enqueue_script('ctzen-admin', CTZEN_URL . 'assets/js/admin.js', ['jquery'], '1.0', true);
        wp_enqueue_style('ctzen-admin', CTZEN_URL . 'assets/css/admin.css');
    }

    public static function page() {
        $action = $_REQUEST['action'] ?? 'list';
        switch ($action) {
            case 'add':
            case 'edit':
                self::form(intval($_GET['id'] ?? 0));
                break;
            case 'save':
                self::save();
                self::list();
                break;
            case 'delete':
                self::delete(intval($_GET['id']));
                self::list();
                break;
            default:
                self::list();
        }
    }

    protected static function list() {
        global $wpdb;
        $themes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ctzen_themes ORDER BY parent_id, menu_order");
        include CTZEN_DIR . 'includes/admin/partials/theme-list.php';
    }

    protected static function form($id = 0) {
        global $wpdb;
        $p = $wpdb->prefix;

        // Theme-Grunddaten
        $theme = $id
            ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$p}ctzen_themes WHERE id=%d", $id))
            : null;

        // Versionen Beschreibung
        $desc_versions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT vid, author_id, created_at
                 FROM {$p}ctzen_desc_versions
                 WHERE theme_id = %d
                 ORDER BY vid",
                $id
            )
        );
        // Versionen Meinung
        $op_versions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT vid, author_id, created_at
                 FROM {$p}ctzen_opinion_versions
                 WHERE theme_id = %d
                 ORDER BY vid",
                $id
            )
        );

        // Aktuelle Inhalte
        $current = ['description' => '', 'opinion' => ''];
        if ($theme) {
            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT description
                     FROM {$p}ctzen_desc_versions
                     WHERE theme_id = %d
                     ORDER BY vid DESC
                     LIMIT 1",
                    $id
                )
            );
            if ($row) $current['description'] = $row->description;

            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT opinion
                     FROM {$p}ctzen_opinion_versions
                     WHERE theme_id = %d
                     ORDER BY vid DESC
                     LIMIT 1",
                    $id
                )
            );
            if ($row) $current['opinion'] = $row->opinion;
        }

        // News-Einträge und Versionen
        $news_entries = [];
        if ($theme) {
            $news_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT news_id FROM {$p}ctzen_news WHERE theme_id = %d",
                    $id
                )
            );
            foreach ($news_rows as $nr) {
                // Versionen für jede News
                $versions = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT vid, news_date, author_id, created_at
                         FROM {$p}ctzen_news_versions
                         WHERE news_id = %d
                         ORDER BY vid",
                        $nr->news_id
                    )
                );
                // Aktuelle Version
                $last = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT news_date, content
                         FROM {$p}ctzen_news_versions
                         WHERE news_id = %d
                         ORDER BY vid DESC
                         LIMIT 1",
                        $nr->news_id
                    ),
                    ARRAY_A
                );
                $news_entries[] = [
                    'id'       => $nr->news_id,
                    'versions' => $versions,
                    'current'  => $last ?: ['news_date' => '', 'content' => ''],
                ];
            }
        }

        // Alle anderen Themes für Parent-Auswahl
        $all = $wpdb->get_results(
            "SELECT id, title
             FROM {$p}ctzen_themes
             WHERE id <> {$id}"
        );

        include CTZEN_DIR . 'includes/admin/partials/theme-form.php';
    }

    protected static function save() {
        global $wpdb;
        check_admin_referer('ctzen_save_theme');
        $uid = get_current_user_id();
        $p   = $wpdb->prefix;

        // 1) Theme-Grunddaten
        $data = [
            'title'      => sanitize_text_field($_POST['title']),
            'parent_id'  => !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null,
            'start_date' => $_POST['start_date'] ?: null,
            'end_date'   => $_POST['end_date']   ?: null,
            'author_id'  => $uid,
        ];
        if (!empty($_POST['id'])) {
            $tid = intval($_POST['id']);
            $wpdb->update("{$p}ctzen_themes", $data, ['id' => $tid]);
        } else {
            $wpdb->insert("{$p}ctzen_themes", $data);
            $tid = $wpdb->insert_id;
        }

        // 2) Reihenfolge-Logik
        $new_pos   = intval($_POST['menu_order']);
        $parent_id = $data['parent_id'];
        $cond      = is_null($parent_id) ? 'parent_id IS NULL' : $wpdb->prepare('parent_id=%d', $parent_id);
        $siblings  = $wpdb->get_results("SELECT id FROM {$p}ctzen_themes WHERE {$cond} ORDER BY menu_order ASC, id ASC");
        $ordered   = [];
        foreach ($siblings as $s) {
            if ($s->id !== $tid) $ordered[] = $s->id;
        }
        // Grenzen
        if ($new_pos < 0) $new_pos = 0;
        if ($new_pos > count($ordered)) $new_pos = count($ordered);
        array_splice($ordered, $new_pos, 0, [$tid]);
        foreach ($ordered as $idx => $id) {
            $wpdb->update("{$p}ctzen_themes", ['menu_order' => $idx], ['id' => $id]);
        }

        // 3) Versionierung Beschreibung
        if (isset($_POST['description'])) {
            $new_desc = wp_kses_post($_POST['description']);
            if ($new_desc !== '') {
                $last = $wpdb->get_var($wpdb->prepare(
                    "SELECT description FROM {$p}ctzen_desc_versions WHERE theme_id=%d ORDER BY vid DESC LIMIT 1",
                    $tid
                ));
                if ($new_desc !== $last) {
                    $wpdb->insert("{$p}ctzen_desc_versions", [
                        'theme_id'    => $tid,
                        'description' => $new_desc,
                        'author_id'   => $uid,
                    ]);
                }
            }
        }

        // 4) Versionierung Meinung
        if (isset($_POST['opinion'])) {
            $new_op = wp_kses_post($_POST['opinion']);
            if ($new_op !== '') {
                $last = $wpdb->get_var($wpdb->prepare(
                    "SELECT opinion FROM {$p}ctzen_opinion_versions WHERE theme_id=%d ORDER BY vid DESC LIMIT 1",
                    $tid
                ));
                if ($new_op !== $last) {
                    $wpdb->insert("{$p}ctzen_opinion_versions", [
                        'theme_id'  => $tid,
                        'opinion'   => $new_op,
                        'author_id' => $uid,
                    ]);
                }
            }
        }

        // 5) News und News-Versionierung
        // Bestehende News-IDs
        $existing = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT news_id FROM {$p}ctzen_news WHERE theme_id=%d",
                $tid
            )
        );
        $submitted = [];
        if (!empty($_POST['news']) && is_array($_POST['news'])) {
            foreach ($_POST['news'] as $row) {
                $date    = sanitize_text_field($row['date']);
                $content = wp_kses_post($row['content']);
                if ($content === '') continue;

                // a) News anlegen oder ID übernehmen
                if (empty($row['news_id'])) {
                    $wpdb->insert("{$p}ctzen_news", ['theme_id' => $tid]);
                    $nid = $wpdb->insert_id;
                } else {
                    $nid = intval($row['news_id']);
                    if (!in_array($nid, $existing, true)) continue;
                }
                $submitted[] = $nid;

                // b) Letzte Version prüfen
                $last = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT news_date, content FROM {$p}ctzen_news_versions WHERE news_id=%d ORDER BY vid DESC LIMIT 1",
                        $nid
                    ),
                    ARRAY_A
                );

                // c) Neue Version nur bei Änderung
                if (!$last || $last['news_date'] !== $date || $last['content'] !== $content) {
                    $wpdb->insert("{$p}ctzen_news_versions", [
                        'news_id'   => $nid,
                        'news_date' => $date,
                        'content'   => $content,
                        'author_id' => $uid,
                    ]);
                }
            }
        }
        // Entferne gelöschte News
        $to_delete = array_diff($existing, $submitted);
        if (!empty($to_delete)) {
            foreach ($to_delete as $del) {
                $wpdb->delete("{$p}ctzen_news_versions", ['news_id' => $del]);
                $wpdb->delete("{$p}ctzen_news",          ['news_id' => $del]);
            }
        }
    }

    protected static function delete($id) {
        check_admin_referer('ctzen_delete_theme');
        global $wpdb;
        $p = $wpdb->prefix;
        $wpdb->delete("{$p}ctzen_news_versions", ['news_id' => $id]);
        $wpdb->delete("{$p}ctzen_news",          ['news_id' => $id]);
        $wpdb->delete("{$p}ctzen_opinion_versions", ['theme_id' => $id]);
        $wpdb->delete("{$p}ctzen_desc_versions",    ['theme_id' => $id]);
        $wpdb->delete("{$p}ctzen_themes",           ['id'       => $id]);
    }
}
