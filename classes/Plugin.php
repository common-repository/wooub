<?php
namespace UBER\Classes;

/**
 * Description of Plugin
 *
 * @author Sergeo
 */
class Plugin {
    /**
     * Prefix for options and metas.
     *
     * @staticvar string
     */
    protected static $prefix;
    
    /**
     * Plugin slug.
     *
     * @staticvar string
     */
    protected static $slug;

    /**
     * Plugin title.
     *
     * @staticvar string
     */
    protected static $title;

    /**
     * Plugin version.
     *
     * @staticvar string
     */
    protected static $version;

    /**
     * Path to plugin directory.
     *
     * @staticvar string
     */
    protected static $directory;

    /**
     * Path to plugin main file.
     *
     * @staticvar string
     */
    protected static $main_file;
    /**
     * Plugin text domain.
     *
     * @staticvar string
     */
    protected static $text_domain;
    
    
    public static function getDirectory()
    {
        if ( static::$directory === null ) {
            $reflector = new \ReflectionClass( get_called_class() );
            static::$directory = dirname( dirname( $reflector->getFileName() ) );
        }

        return static::$directory;
    }
    
    /**
     * Get path to plugin main file.
     *
     * @return string
     */
    public static function getMainFile()
    {
        if ( static::$main_file === null ) {
            static::$main_file = static::getDirectory() . '/main.php';
        }

        return static::$main_file;
    }
    
    /**
     * Get plugin version.
     *
     * @return string
     */
    public static function getVersion()
    {
        if ( static::$version === null ) {
            if ( ! function_exists( 'get_plugin_data' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            $plugin_data = get_plugin_data( static::getMainFile() );
            static::$version     = $plugin_data['Version'];
            static::$title       = $plugin_data['Name'];
            static::$text_domain = $plugin_data['TextDomain'];
        }

        return static::$version;
    }

    /**
     * Get plugin slug.
     *
     * @return string
     */
    public static function getSlug()
    {
        if ( static::$slug === null ) {
            static::$slug = basename( static::getDirectory() );
        }

        return static::$slug;
    }
    
    /**
     * Get plugin text domain.
     *
     * @return string
     */
    public static function getTextDomain()
    {
        if ( static::$text_domain === null ) {
            if ( ! function_exists( 'get_plugin_data' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            $plugin_data = get_plugin_data( static::getMainFile() );
            static::$version     = $plugin_data['Version'];
            static::$title       = $plugin_data['Name'];
            static::$text_domain = $plugin_data['TextDomain'];
        }

        return static::$text_domain;
    }
    
    public static function activate()
    {
        
    }

    public static function deactivate()
    {
        
    }

    public static function uninstall()
    {
        
    }
    
    public static function initEnv()
    {
        
    }


    public static function registerHooks()
    {
        /** @var Plugin $plugin_class */
        $plugin_class = get_called_class();
                
        register_activation_hook( static::getMainFile(),   array( $plugin_class, 'activate' ) );
        register_deactivation_hook( static::getMainFile(), array( $plugin_class, 'deactivate' ) );
        register_uninstall_hook( static::getMainFile(),    array( $plugin_class, 'uninstall' ) );

        add_action( 'plugins_loaded', function () use ( $plugin_class ) {
            // l10n.
            load_plugin_textdomain( $plugin_class::getTextDomain(), false, $plugin_class::getSlug() . '/languages' );
        } );
    }
    
    public static function run()
    {
        self::registerHooks();
        self::initEnv();

        // Initialize authentication panel
        if ( is_admin() ) {
            new AuthPanel;
        }
    }
}
