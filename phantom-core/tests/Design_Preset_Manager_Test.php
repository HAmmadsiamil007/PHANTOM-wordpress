<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\PresetManager;
use PhantomCore\Design\PresetRegistry;
use PhantomCore\Design\Preset;
use PhantomCore\Design\Providers\CoreProvider;
use PhantomCore\Design\Providers\UserProvider;

class Design_Preset_Manager_Test extends TestCase {
    private PresetRegistry $registry;

    protected function setUp(): void {
        $this->registry = PresetRegistry::get_instance();
        $this->registry->invalidateCache();
    }

    public function test_save_and_delete_user_preset(): void {
        $this->registry->register_provider(new CoreProvider());
        $this->registry->register_provider(new UserProvider());
        $manager = PresetManager::get_instance();

        $preset = Preset::from_array([
            'id' => 'user:manager-test',
            'name' => 'Manager Test',
            'tokens' => ['color.primary' => '#FF00FF'],
        ]);
        $saved = $manager->save($preset);
        $this->assertTrue($saved);

        $userProvider = new UserProvider();
        $retrieved = $userProvider->get_preset('user:manager-test');
        $this->assertNotNull($retrieved);

        $deleted = $manager->delete('user:manager-test');
        $this->assertTrue($deleted);
    }

    public function test_duplicate_creates_copy(): void {
        $this->registry->register_provider(new CoreProvider());
        $this->registry->register_provider(new UserProvider());
        $manager = PresetManager::get_instance();

        $copy = $manager->duplicate('core:light', 'user:light-copy', 'Light Copy');
        $this->assertNotNull($copy);
        $this->assertSame('user:light-copy', $copy->id);
        $this->assertSame('Light Copy', $copy->name);

        $manager->delete('user:light-copy');
    }

    public function test_duplicate_nonexistent_returns_null(): void {
        $this->registry->register_provider(new CoreProvider());
        $this->registry->register_provider(new UserProvider());
        $manager = PresetManager::get_instance();

        $this->assertNull($manager->duplicate('nonexistent', 'new:id', 'New Name'));
    }

    public function test_current_returns_null_by_default(): void {
        $this->registry->register_provider(new CoreProvider());
        $this->registry->register_provider(new UserProvider());
        $manager = PresetManager::get_instance();

        $this->assertNull($manager->current());
    }
}
