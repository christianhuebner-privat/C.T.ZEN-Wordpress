<?php
class CTZEN_Admin {
    public static function init(){
        add_action('admin_menu',           [__CLASS__,'add_menu']);
        add_action('admin_enqueue_scripts',[__CLASS__,'enqueue_assets']);
    }

    public static function add_menu(){
        add_menu_page(
            'C.T.ZEN Themen',
            'C.T.ZEN',
            'manage_options',
            'ctzen_themes',
            [__CLASS__,'page'],
            'dashicons-welcome-learn-more',
            20
        );
    }

    public static function enqueue_assets($hook){
        if(strpos($hook,'ctzen_themes')===false) return;
        wp_enqueue_script('ctzen-admin', CTZEN_URL.'assets/js/admin.js',['jquery'],'1.0',true);
        wp_enqueue_style('ctzen-admin', CTZEN_URL.'assets/css/admin.css');
    }

    public static function page(){
        $action = $_REQUEST['action'] ?? 'list';
        switch($action){
            case 'add':
            case 'edit': self::form(intval($_GET['id'] ?? 0)); break;
            case 'save': self::save(); self::list(); break;
            case 'delete': self::delete(intval($_GET['id'])); self::list(); break;
            default: self::list();
        }
    }

    protected static function list(){
        global $wpdb;
        $themes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ctzen_themes ORDER BY parent_id, menu_order");
        include CTZEN_DIR.'includes/admin/partials/theme-list.php';
    }

    protected static function form($id=0){
        global $wpdb;
        $theme = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ctzen_themes WHERE id=%d",$id)) : null;
        $desc_versions = $wpdb->get_results($wpdb->prepare("SELECT vid,author_id,created_at FROM {$wpdb->prefix}ctzen_desc_versions WHERE theme_id=%d ORDER BY vid",$id));
        $op_versions  = $wpdb->get_results($wpdb->prepare("SELECT vid,author_id,created_at FROM {$wpdb->prefix}ctzen_opinion_versions WHERE theme_id=%d ORDER BY vid",$id));
        $akt_versions = $wpdb->get_results($wpdb->prepare("SELECT vid,author_id,created_at FROM {$wpdb->prefix}ctzen_aktuelles_versions WHERE theme_id=%d ORDER BY vid",$id));
        $current = ['description'=>'','opinion'=>'','aktuelles'=>[]];
        if($id){
            if($r=$wpdb->get_row("SELECT description FROM {$wpdb->prefix}ctzen_desc_versions WHERE theme_id={$id} ORDER BY vid DESC LIMIT 1")) $current['description']=$r->description;
            if($r=$wpdb->get_row("SELECT opinion     FROM {$wpdb->prefix}ctzen_opinion_versions WHERE theme_id={$id} ORDER BY vid DESC LIMIT 1")) $current['opinion']=$r->opinion;
            if($r=$wpdb->get_row("SELECT data        FROM {$wpdb->prefix}ctzen_aktuelles_versions WHERE theme_id={$id} ORDER BY vid DESC LIMIT 1")) $current['aktuelles']=json_decode($r->data,true);
        }
        $all = $wpdb->get_results("SELECT id,title FROM {$wpdb->prefix}ctzen_themes WHERE id!={$id}");
        include CTZEN_DIR.'includes/admin/partials/theme-form.php';
    }

    protected static function save(){
        global $wpdb;
        check_admin_referer('ctzen_save_theme');
        $uid = get_current_user_id();
        $p   = $wpdb->prefix;

        // Eingabewerte
        $title      = sanitize_text_field($_POST['title']);
        $parent_id  = isset($_POST['parent_id']) && $_POST['parent_id'] ? intval($_POST['parent_id']) : null;
        $start_date = $_POST['start_date'] ?: null;
        $end_date   = $_POST['end_date']   ?: null;
        $new_pos    = intval($_POST['menu_order']);

        // Theme anlegen oder updaten (ohne menu_order)
        if(!empty($_POST['id'])){
            $tid = intval($_POST['id']);
            $wpdb->update("{$p}ctzen_themes",[
                'title'=>$title,
                'parent_id'=>$parent_id,
                'start_date'=>$start_date,
                'end_date'=>$end_date,
                'author_id'=>$uid
            ],['id'=>$tid]);
        } else {
            $wpdb->insert("{$p}ctzen_themes",[
                'title'=>$title,
                'parent_id'=>$parent_id,
                'start_date'=>$start_date,
                'end_date'=>$end_date,
                'author_id'=>$uid
            ]);
            $tid = $wpdb->insert_id;
        }

        // Reihenfolge aller Geschwister neu berechnen
        $cond = $parent_id===null
            ? "parent_id IS NULL"
            : $wpdb->prepare("parent_id=%d", $parent_id);
        $siblings = $wpdb->get_results(
            "SELECT id FROM {$p}ctzen_themes WHERE {$cond} ORDER BY menu_order ASC, id ASC"
        );
        $ordered = [];
        foreach($siblings as $s){
            if($s->id != $tid) $ordered[] = $s->id;
        }
        // Position begrenzen
        if($new_pos < 0) $new_pos = 0;
        if($new_pos > count($ordered)) $new_pos = count($ordered);
        // Thema in Liste einfügen
        array_splice($ordered, $new_pos, 0, [$tid]);
        // menu_order updaten
        foreach($ordered as $idx => $id){
            $wpdb->update("{$p}ctzen_themes", ['menu_order'=>$idx], ['id'=>$id]);
        }

        // Versionierung (Beschr., Meinung, Aktuelles) wie bisher
        // ... (unverändert)
    }

    protected static function delete($id){
        check_admin_referer('ctzen_delete_theme');
        global $wpdb;
        $p = $wpdb->prefix;
        $wpdb->delete("{$p}ctzen_aktuelles_versions", ['theme_id'=>$id]);
        $wpdb->delete("{$p}ctzen_opinion_versions", ['theme_id'=>$id]);
        $wpdb->delete("{$p}ctzen_desc_versions",    ['theme_id'=>$id]);
        $wpdb->delete("{$p}ctzen_themes",           ['id'=>$id]);
    }
}
