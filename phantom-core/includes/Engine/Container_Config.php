<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

use PhantomCore\Demo\Demo_Loader;
use PhantomCore\Demo\Demo_Registry;
use PhantomCore\Demo\Demo_Switcher;
use PhantomCore\Components\Component_Registry;
use PhantomCore\Components\Component_Manager;
use PhantomCore\Registry\Template_Registry;

defined('ABSPATH') || exit;

class Container_Config {
    public static function configure(Container $container): void
    {
        // 1. EventDispatcher — singleton
        $container->singleton(EventDispatcher::class, function () {
            return new EventDispatcher();
        });

        // 2. Template_Loader — singleton
        $container->singleton(Template_Loader::class, function () {
            return new Template_Loader();
        });

        // 3. SEO_Engine — singleton
        $container->singleton(SEO_Engine::class, function () {
            return new SEO_Engine();
        });

        // 4. Security_Headers — singleton
        $container->singleton(Security_Headers::class, function () {
            return new Security_Headers();
        });

        // 5. Data_Engine — singleton
        $container->singleton(Data_Engine::class, function ($c) {
            return new Data_Engine($c->get(Template_Loader::class));
        });

        // 6. View_Engine — singleton
        $container->singleton(View_Engine::class, function ($c) {
            return new View_Engine($c->get(SEO_Engine::class));
        });

        // 7. Asset_Engine — singleton
        $container->singleton(Asset_Engine::class, function ($c) {
            return new Asset_Engine(
                $c->get(Data_Engine::class),
                $c->get(Security_Headers::class)
            );
        });

		// 8. Render_Engine — singleton with pack resolution
		$container->singleton(Render_Engine::class, function ($c) {
			$pack = get_option('phantom_template_pack', 'default');
            if (class_exists('\PhantomCore\Settings_Registry')) {
                $registry = \PhantomCore\Settings_Registry::get_instance();
                if ($registry->has('template_pack')) {
                    $pack = $registry->get('template_pack');
                }
            }
            $engine = new Render_Engine(
                $c->get(Data_Engine::class),
                $c->get(View_Engine::class),
                $c->get(Asset_Engine::class),
                $c->get(EventDispatcher::class)
            );
            $engine->get_template_loader()->set_pack($pack);
            return $engine;
        });

        // 9. Demo_Registry — singleton (no deps, scans filesystem)
        $container->singleton(Demo_Registry::class, function () {
            return new Demo_Registry();
        });

        // 10. Demo_Loader — singleton wrapping Template_Loader
        $container->singleton(Demo_Loader::class, function ($c) {
            return new Demo_Loader(
                $c->get(Template_Loader::class),
                $c->get(Demo_Registry::class)
            );
        });

		// 11. Demo_Switcher — singleton for activate/deactivate
		$container->singleton(Demo_Switcher::class, function ($c) {
			return new Demo_Switcher(
				$c->get(Demo_Registry::class)
			);
		});

		// 12. DesignSystemManager — singleton (Phase 4)
		$container->singleton(\PhantomCore\Design\DesignSystemManager::class, function () {
			return \PhantomCore\Design\DesignSystemManager::get_instance();
		});

		// 13. WooCommerce_Injector — factory (not singleton)
		$container->set(WooCommerce_Injector::class, function ($c) {
			return new WooCommerce_Injector(
				$c->get(Render_Engine::class),
				$c->get(EventDispatcher::class)
			);
		});

		// 14. Feature_Registry — singleton (Phase 5B)
		$container->singleton(\PhantomCore\Feature\Feature_Registry::class, function () {
			return \PhantomCore\Feature\Feature_Registry::get_instance();
		});

		// 15. Feature_Manager — singleton (Phase 5B)
		$container->singleton(\PhantomCore\Feature\Feature_Manager::class, function () {
			return \PhantomCore\Feature\Feature_Manager::get_instance();
		});

		// 16. Component_Registry — singleton (Phase 5D)
		$container->singleton(Component_Registry::class, function () {
			return Component_Registry::get_instance();
		});

		// 17. Component_Manager — singleton (Phase 5D)
		$container->singleton(Component_Manager::class, function () {
			return Component_Manager::get_instance();
		});

		// 18. Template_Registry — singleton (Phase 5D)
		$container->singleton(Template_Registry::class, function () {
			return Template_Registry::get_instance();
		});

		// 19. Animation_Registry — singleton (Phase 5A)
		$container->singleton(\PhantomCore\Animation\Animation_Registry::class, function () {
			return \PhantomCore\Animation\Animation_Registry::get_instance();
		});

		// 20. GSAP_Bridge — singleton (Phase 5A)
		$container->singleton(\PhantomCore\Animation\GSAP_Bridge::class, function () {
			return \PhantomCore\Animation\GSAP_Bridge::get_instance();
		});

		// 21. Scroll_Reveal — singleton (Phase 5A)
		$container->singleton(\PhantomCore\Animation\Scroll_Reveal::class, function () {
			return \PhantomCore\Animation\Scroll_Reveal::get_instance();
		});

		// 22. Parallax — singleton (Phase 5A)
		$container->singleton(\PhantomCore\Animation\Parallax::class, function () {
			return \PhantomCore\Animation\Parallax::get_instance();
		});

		// 23. Data_Normalizer — singleton utility (Phase B)
		$container->singleton(\PhantomCore\Data\Data_Normalizer::class, function () {
			return new \PhantomCore\Data\Data_Normalizer();
		});

		// 24. Data_Provider — abstract, concrete instances per provider
		// Concrete providers should self-register: $container->set(Foo_Provider::class, ...)

		// 25. Layout_Registry — singleton (Phase C)
		$container->singleton(\PhantomCore\Layout\Layout_Registry::class, function () {
			return \PhantomCore\Layout\Layout_Registry::get_instance();
		});

		// 26. Layout_Manager — static init called during bootstrap
		$container->singleton(\PhantomCore\Layout\Layout_Manager::class, function () {
			\PhantomCore\Layout\Layout_Manager::init();
			return null; // init-only, no instance needed
		});

		// 27. Design_API — singleton facade (Phase C)
		$container->singleton(\PhantomCore\Public\Design_API::class, function () {
			return \PhantomCore\Public\Design_API::get_instance();
		});

		// 28. Hook_Registry — singleton (Phase C)
		$container->singleton(\PhantomCore\Hook\Hook_Registry::class, function () {
			return \PhantomCore\Hook\Hook_Registry::get_instance();
		});

		// 29. Bridge_Manager — singleton (Phase D + Phase 4)
		$container->singleton(\PhantomCore\Bridges\Bridge_Manager::class, function () {
			return \PhantomCore\Bridges\Bridge_Manager::get_instance();
		});

		// 30. Asset_Registry — singleton (Phase E)
		$container->singleton(\PhantomCore\Registry\Asset_Registry::class, function () {
			return \PhantomCore\Registry\Asset_Registry::get_instance();
		});

		// 31. Capability_Manager — singleton (Phase E)
		$container->singleton(\PhantomCore\Capability_Manager::class, function () {
			return \PhantomCore\Capability_Manager::get_instance();
		});

		// 32-37. Public API facades (Phase E)
		$container->singleton(\PhantomCore\Public\Render_API::class, function () {
			return \PhantomCore\Public\Render_API::get_instance();
		});
		$container->singleton(\PhantomCore\Public\Component_API::class, function () {
			return \PhantomCore\Public\Component_API::get_instance();
		});
		$container->singleton(\PhantomCore\Public\Animation_API::class, function () {
			return \PhantomCore\Public\Animation_API::get_instance();
		});
		$container->singleton(\PhantomCore\Public\Settings_API::class, function () {
			return \PhantomCore\Public\Settings_API::get_instance();
		});
		$container->singleton(\PhantomCore\Public\Template_API::class, function () {
			return \PhantomCore\Public\Template_API::get_instance();
		});
		$container->singleton(\PhantomCore\Public\Developer_API::class, function () {
			return \PhantomCore\Public\Developer_API::get_instance();
		});

		// 38-48. Data Layer registrations (Phase 2a)
		// Adapters — actively wired into injectors
		$container->singleton(\PhantomCore\Adapters\Cart_Adapter::class, function () {
			return new \PhantomCore\Adapters\Cart_Adapter();
		});
		$container->singleton(\PhantomCore\Adapters\SearchResult_Adapter::class, function () {
			return new \PhantomCore\Adapters\SearchResult_Adapter();
		});
		$container->singleton(\PhantomCore\Adapters\User_Adapter::class, function () {
			return new \PhantomCore\Adapters\User_Adapter();
		});
		$container->singleton(\PhantomCore\Adapters\Footer_Adapter::class, function () {
			return new \PhantomCore\Adapters\Footer_Adapter();
		});
		$container->singleton(\PhantomCore\Adapters\Product_Adapter::class, function () {
			return new \PhantomCore\Adapters\Product_Adapter();
		});
		$container->singleton(\PhantomCore\Adapters\Post_Adapter::class, function () {
			return new \PhantomCore\Adapters\Post_Adapter();
		});

		// ViewModels
		$container->singleton(\PhantomCore\ViewModels\Product_ViewModel::class, function () {
			return new \PhantomCore\ViewModels\Product_ViewModel();
		});
		$container->singleton(\PhantomCore\ViewModels\SearchResult_ViewModel::class, function () {
			return new \PhantomCore\ViewModels\SearchResult_ViewModel();
		});
		$container->singleton(\PhantomCore\ViewModels\Post_ViewModel::class, function () {
			return new \PhantomCore\ViewModels\Post_ViewModel();
		});
		$container->singleton(\PhantomCore\ViewModels\Order_ViewModel::class, function () {
			return new \PhantomCore\ViewModels\Order_ViewModel();
		});
		$container->singleton(\PhantomCore\ViewModels\User_ViewModel::class, function () {
			return new \PhantomCore\ViewModels\User_ViewModel();
		});
		$container->singleton(\PhantomCore\ViewModels\Comment_ViewModel::class, function () {
			return new \PhantomCore\ViewModels\Comment_ViewModel();
		});
		$container->singleton(\PhantomCore\ViewModels\Category_ViewModel::class, function () {
			return new \PhantomCore\ViewModels\Category_ViewModel();
		});

		// Component_Metadata — template/asset compatibility
		$container->singleton(\PhantomCore\Component_Metadata::class, function () {
			return \PhantomCore\Component_Metadata::get_instance();
		});
    }
}
