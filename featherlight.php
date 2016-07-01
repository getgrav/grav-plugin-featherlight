<?php
namespace Grav\Plugin;

use \Grav\Common\Plugin;
use \Grav\Common\Grav;
use \Grav\Common\Page\Page;

class FeatherlightPlugin extends Plugin
{
    protected $active = false;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Initialize configuration
     */
    public function onPluginsInitialized()
    {
        if ($this->isAdmin()) {
            $this->active = false;
            return;
        }

        $this->enable([
            'onPageInitialized' => ['onPageInitialized', 0]
        ]);
    }

    /**
     * Initialize configuration
     */
    public function onPageInitialized()
    {
        $defaults = (array) $this->config->get('plugins.featherlight');

        /** @var Page $page */
        $page = $this->grav['page'];
        if (isset($page->header()->featherlight)) {
            $this->config->set('plugins.featherlight', array_merge($defaults, $page->header()->featherlight));
        }

        // take the old legacy `lightbox: true` setting into account
        if (isset($page->header()->lightbox) && $page->header()->lightbox == true) {
            $legacy = true;
        } else {
            $legacy = false;
        }

        $this->active = $this->config->get('plugins.featherlight.active') || $legacy;

        if ($this->active) {
            $this->enable([
                'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
            ]);
        }
    }

    /**
     * if enabled on this page, load the JS + CSS theme.
     */
    public function onTwigSiteVariables()
    {
        $config = $this->config->get('plugins.featherlight');

        if ($config['requirejs']) {
            $init = "define(\"featherlight\",['jquery', 'plugin/featherlight/js/featherlight.min' ], function($){
                var Lightbox = {
                  Init : function() {
                    $('a[rel=\"lightbox\"]').featherlight({
                      openSpeed: {$config['openSpeed']},
                      closeSpeed: {$config['closeSpeed']},
                      closeOnClick: '{$config['closeOnClick']}',
                      closeOnEsc:   '{$config['closeOnEsc']}',
                      root: '{$config['root']}'
                    });
                  }
                };
                return Lightbox;
            });";
            $this->grav['assets']
                ->addCss('plugin://featherlight/css/featherlight.min.css')
                ->addInlineJs($init);
        } elseif ($config['gallery']) {
            $init = $this->getInitJs($config);
            $this->grav['assets']
                 ->addCss('plugin://featherlight/css/featherlight.min.css')
                 ->addCss('plugin://featherlight/css/featherlight.gallery.min.css')
                 ->add('jquery', 101)
                 ->addJs('plugin://featherlight/js/featherlight.min.js')
                 ->addJs('plugin://featherlight/js/featherlight.gallery.min.js')
                 ->addInlineJs($init);
        } else {
            $init = $this->getInitJs($config);
            $this->grav['assets']
                ->addCss('plugin://featherlight/css/featherlight.min.css')
                ->add('jquery', 101)
                ->addJs('plugin://featherlight/js/featherlight.min.js')
                ->addInlineJs($init);
        }
    }

    protected function getInitJs($config) {
        $asset = $this->grav['locator']->findResource($config['initTemplate'], false);

        $init = file_get_contents(ROOT_DIR . $asset);

        $init = str_replace(
            array('{pluginName}', '{openSpeed}', '{closeSpeed}', '{closeOnClick}', '{closeOnEsc}', '{root}'),
            array(
                $config['gallery'] ? 'featherlightGallery' : 'featherlight',
                $config['openSpeed'],
                $config['closeSpeed'],
                $config['closeOnClick'],
                $config['closeOnEsc'],
                $config['root']
            ),
            $init
        );

        return $init;
    }
}
