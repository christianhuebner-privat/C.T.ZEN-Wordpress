<?php
class CTZEN_DB {
    public static function install(){
        global $wpdb;
        $c = $wpdb->get_charset_collate();
        $t = $wpdb->prefix;

        $sql = "
        CREATE TABLE {$t}ctzen_themes (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title       VARCHAR(255)          NOT NULL,
            parent_id   BIGINT(20) UNSIGNED   DEFAULT NULL,
            menu_order  INT                   NOT NULL DEFAULT 0,
            start_date  DATE                  DEFAULT NULL,
            end_date    DATE                  DEFAULT NULL,
            author_id   BIGINT(20) UNSIGNED   NOT NULL,
            created_at  DATETIME              NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME              NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY parent_idx(parent_id)
        ) $c;

        CREATE TABLE {$t}ctzen_desc_versions (
            vid         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            theme_id    BIGINT(20) UNSIGNED NOT NULL,
            description LONGTEXT             NOT NULL,
            author_id   BIGINT(20) UNSIGNED NOT NULL,
            created_at  DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(vid),
            KEY theme_idx(theme_id)
        ) $c;

        CREATE TABLE {$t}ctzen_opinion_versions (
            vid         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            theme_id    BIGINT(20) UNSIGNED NOT NULL,
            opinion     LONGTEXT             NOT NULL,
            author_id   BIGINT(20) UNSIGNED NOT NULL,
            created_at  DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(vid),
            KEY theme_idx(theme_id)
        ) $c;

        CREATE TABLE {$t}ctzen_aktuelles_versions (
            vid         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            theme_id    BIGINT(20) UNSIGNED NOT NULL,
            data        LONGTEXT             NOT NULL,  /* JSON-encoded array of {date,content} */
            author_id   BIGINT(20) UNSIGNED NOT NULL,
            created_at  DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(vid),
            KEY theme_idx(theme_id)
        ) $c;
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function uninstall(){
        global $wpdb;
        $t = $wpdb->prefix;
        $wpdb->query("DROP TABLE IF EXISTS {$t}ctzen_aktuelles_versions");
        $wpdb->query("DROP TABLE IF EXISTS {$t}ctzen_opinion_versions");
        $wpdb->query("DROP TABLE IF EXISTS {$t}ctzen_desc_versions");
        $wpdb->query("DROP TABLE IF EXISTS {$t}ctzen_themes");
    }
}
