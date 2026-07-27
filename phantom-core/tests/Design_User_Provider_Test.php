<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\Preset;
use PhantomCore\Design\Providers\UserProvider;

class Design_User_Provider_Test extends TestCase {
    private UserProvider $provider;

    protected function setUp(): void {
        $this->provider = new UserProvider();
    }

    public function test_source_returns_user(): void {
        $this->assertSame('user', $this->provider->source());
    }

    public function test_save_and_get_preset(): void {
        $preset = Preset::from_array([
            'id' => 'user:test',
            'name' => 'Test User Preset',
            'source' => 'user',
            'tokens' => ['color.primary' => '#00FF00'],
        ]);
        $saved = $this->provider->save($preset);
        $this->assertTrue($saved);

        $retrieved = $this->provider->get_preset('user:test');
        $this->assertNotNull($retrieved);
        $this->assertSame('Test User Preset', $retrieved->name);
        $this->assertSame('#00FF00', $retrieved->tokens['color.primary']);
    }

    public function test_delete_removes_preset(): void {
        $preset = Preset::from_array([
            'id' => 'user:delete-test',
            'name' => 'Delete Me',
        ]);
        $this->provider->save($preset);
        $this->assertTrue($this->provider->exists('user:delete-test'));

        $deleted = $this->provider->delete('user:delete-test');
        $this->assertTrue($deleted);
        $this->assertFalse($this->provider->exists('user:delete-test'));
    }

    public function test_delete_nonexistent_returns_false(): void {
        $this->assertFalse($this->provider->delete('user:nonexistent'));
    }

    public function test_exists_returns_false_by_default(): void {
        $this->assertFalse($this->provider->exists('user:nonexistent'));
    }
}
