<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\TokenValidator;
use PhantomCore\Design\TokenRegistry;

class Design_Token_Validator_Test extends TestCase {
    private TokenValidator $validator;

    protected function setUp(): void {
        TokenRegistry::get_instance()->load();
        $this->validator = new TokenValidator();
    }

    public function test_validate_known_token_returns_ok(): void {
        $result = $this->validator->validate('color.primary');
        $this->assertIsArray($result);
        $this->assertSame('ok', $result[0]['status']);
    }

    public function test_validate_unknown_token_returns_error(): void {
        $result = $this->validator->validate('nonexistent');
        $this->assertSame('error', $result[0]['status']);
    }

    public function test_validateAll_returns_results_for_all(): void {
        $results = $this->validator->validateAll();
        $this->assertGreaterThan(100, count($results));
        $allOk = true;
        foreach ($results as $r) {
            if ('error' === $r['status']) {
                $allOk = false;
                break;
            }
        }
        $this->assertTrue($allOk, 'All tokens should validate without errors');
    }

    public function test_isHealthy_returns_true(): void {
        $this->assertTrue($this->validator->isHealthy());
    }
}
