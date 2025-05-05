<?php
class CTZEN_Admin {
    public static function init(){
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    public static function add_menu(){
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

    public static function enqueue_assets($hook){
        if (strpos($hook, 'ctzen_themes') === false) return;
        wp_enqueue_script('ctzen-admin', CTZEN_URL . 'assets/js/admin.js', ['jquery'], '1.0', true);
        wp_enqueue_style('ctzen-admin', CTZEN_URL . 'assets/css/admin.css');
    }

    public static function page(){
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

    protected static function list(){
        global $wpdb;
        $themes = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ctzen_themes ORDER BY parent_id, menu_order"
        );
        include CTZEN_DIR . 'includes/admin/partials/theme-list.php';
    }

    protected static function form($id = 0){
        global $wpdb;
        $theme = $id
            ? $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$wpdb->prefix}ctzen_themes WHERE id=%d", $id)
            )
            : null;

        $desc_versions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT vid, author_id, created_at FROM {$wpdb->prefix}ctzen_desc_versions WHERE theme_id=%d ORDER BY vid",
                $id
            )
        );
        $op_versions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT vid, author_id, created_at FROM {$wpdb->prefix}ctzen_opinion_versions WHERE theme_id=%d ORDER BY vid",
                $id
            )
        );
        $akt_versions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT vid, author_id, created_at FROM {$wpdb->prefix}ctzen_aktuelles_versions WHERE theme_id=%d ORDER BY vid",
                $id
            )
        );

        $current = ['description' => '', 'opinion' => '', 'aktuelles' => []];
        if ($id) {
            $row = $wpdb->get_row(
                "SELECT * FROM {$wpdb->prefix}ctzen_desc_versions WHERE theme_id={$id} ORDER BY vid DESC LIMIT 1"
            );
            if ($row) $current['description'] = $row->description;

            $row = $wpdb->get_row(
                "SELECT * FROM {$wpdb->prefix}ctzen_opinion_versions WHERE theme_id={$id} ORDER BY vid DESC LIMIT 1"
            );
            if ($row) $current['opinion'] = $row->opinion;

            $row = $wpdb->get_row(
                "SELECT * FROM {$wpdb->prefix}ctzen_aktuelles_versions WHERE theme_id={$id} ORDER BY vid DESC LIMIT 1"
            );
            if ($row) $current['aktuelles'] = json_decode($row->data, true);
        }

        $all = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}ctzen_themes WHERE id!={$id}");
        include CTZEN_DIR . 'includes/admin/partials/theme-form.php';
    }

    protected static function save(){
        global $wpdb;
        check_admin_referer('ctzen_save_theme');
        $uid = get_current_user_id();
        $p   = $wpdb->prefix;

        // Theme-Grunddaten
        $data = [
            'title'      => sanitize_text_field($_POST['title']),
            'parent_id'  => $_POST['parent_id'] ? intval($_POST['parent_id']) : null,
            'menu_order' => intval($_POST['menu_order']),
            'start_date' => $_POST['start_date'] ?: null,
            'end_date'   => $_POST['end_date']   ?: null,
            'author_id'  => $uid,
        ];
        if (!empty($_POST['id'])) {
            $wpdb->update("{$p}ctzen_themes", $data, ['id' => intval($_POST['id'])]);
            $tid = intval($_POST['id']);
        } else {
            $wpdb->insert("{$p}ctzen_themes", $data);
            $tid = $wpdb->insert_id;
        }

        // Versionierung: Beschreibung
        $new_desc = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
        if ($new_desc !== '') {
            $last_desc = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT description FROM {$p}ctzen_desc_versions WHERE theme_id=%d ORDER BY vid DESC LIMIT 1",
                    $tid
                )
            );
            if ($new_desc !== $last_desc) {
                $wpdb->insert("{$p}ctzen_desc_versions", [
                    'theme_id'    => $tid,
                    'description' => $new_desc,
                    'author_id'   => $uid,
                ]);
            }
        }

        // Versionierung: Meinung
        $new_op = isset($_POST['opinion']) ? wp_kses_post($_POST['opinion']) : '';
        if ($new_op !== '') {
            $last_op = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT opinion FROM {$p}ctzen_opinion_versions WHERE theme_id=%d ORDER BY vid DESC LIMIT 1",
                    $tid
                )
            );
            if ($new_op !== $last_op) {
                $wpdb->insert("{$p}ctzen_opinion_versions", [
                    'theme_id'  => $tid,
                    'opinion'   => $new_op,
                    'author_id' => $uid,
                ]);
            }
        }

        // Versionierung: Aktuelles (JSON)
        $news_items = [];
        if (!empty($_POST['news'])) {
            foreach ($_POST['news'] as $n) {
                $date    = sanitize_text_field($n['date']);
                $content = wp_kses_post($n['content']);
                if ($content === '') {
                    continue;
                }
                $news_items[] = ['date' => $date, 'content' => $content];
            }
        }
        if (!empty($news_items)) {
            $new_data = wp_json_encode($news_items);
            $last_data = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT data FROM {$p}ctzen_aktuelles_versions WHERE theme_id=%d ORDER BY vid DESC LIMIT 1",
                    $tid
                )
            );
            if ($new_data !== $last_data) {
                $wpdb->insert("{$p}ctzen_aktuelles_versions", [
                    'theme_id'  => $tid,
                    'data'      => $new_data,
                    'author_id' => $uid,
                ]);
            }
        }
    }

    protected static function delete($id){
        check_admin_referer('ctzen_delete_theme');
        global $wpdb;
        $p = $wpdb->prefix;
        $wpdb->delete("{$p}ctzen_aktuelles_versions", ['theme_id' => $id]);
        $wpdb->delete("{$p}ctzen_opinion_versions", ['theme_id' => $id]);
        $wpdb->delete("{$p}ctzen_desc_versions",    ['theme_id' => $id]);
        $wpdb->delete("{$p}ctzen_themes",           ['id'        => $id]);
    }
}
