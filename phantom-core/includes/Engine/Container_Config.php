<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class Container_Config {
    public static function configure(Container $container): void
    {
        // 1. EventDispatcher — singleton
        $container->singleton(EventDispatcher::class, function ($c) {
            return new EventDispatcher();
        });

        // 2. Render_Engine — singleton with pack resolution
        $container->singleton(Render_Engine::class, function ($c) {
            $pack = 'kids';
            if (class_exists('\PhantomCore\Settings_Registry')) {
                $registry = \PhantomCore\Settings_Registry::get_instance();
                if ($registry->has('template_pack')) {
                    $pack = $registry->get('template_pack');
                }
            }
            $engine = new Render_Engine(
                $c->get(Template_Loader::class),
                $c->get(SEO_Engine::class),
                $c->get(Security_Headers::class),
                $c->get(Asset_Loader::class),
                $c->get(EventDispatcher::class)
            );
            $engine->get_template_loader()->set_pack($pack);
            return $engine;
        });

        // 3. WooCommerce_Injector — factory (not singleton — created per-request)
        $container->set(WooCommerce_Injector::class, function ($c) {
            return new WooCommerce_Injector(
                $c->get(Render_Engine::class),
                $c->get(EventDispatcher::class)
            );
        });
    }
}
