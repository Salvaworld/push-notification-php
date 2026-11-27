<?php

use PHPUnit\Framework\TestCase;
use SalvaWorld\PushNotification\Apn;
use SalvaWorld\PushNotification\ApnJwtGenerator;

class ApnGatewayFallbackTest extends TestCase {

    private string $keyFile;

    protected function setUp(): void {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        openssl_pkey_export($key, $pem);
        $this->keyFile = tempnam(sys_get_temp_dir(), 'apn');
        file_put_contents($this->keyFile, $pem);
    }

    protected function tearDown(): void {
        @unlink($this->keyFile);
    }

    /**
     * An Apn without its constructor, which reads the Laravel config, set up through reflection.
     */
    private function apn(array $config = []): Apn {
        $apn = (new ReflectionClass(Apn::class))->newInstanceWithoutConstructor();
        $config = array_merge([
            'auth_key' => $this->keyFile,
            'key_id' => 'KEY1234567',
            'team_id' => 'TEAM123456',
            'bundle_id' => 'com.example.app',
            'dry_run' => true,
        ], $config);

        $this->set($apn, Apn::class, 'config', $config);
        $this->set($apn, Apn::class, 'jwtGenerator', new ApnJwtGenerator('KEY1234567', 'TEAM123456', $this->keyFile));

        return $apn;
    }

    private function set(object $object, string $class, string $property, mixed $value): void {
        $reflection = new ReflectionProperty($class, $property);
        $reflection->setValue($object, $value);
    }

    private function get(object $object, string $property): mixed {
        return (new ReflectionProperty(Apn::class, $property))->getValue($object);
    }

    private function call(object $object, string $method, mixed ...$args): mixed {
        return (new ReflectionMethod(Apn::class, $method))->invoke($object, ...$args);
    }

    public function test_a_bad_device_token_is_retried_once_on_the_other_gateway() {
        $apn = $this->apn();

        $this->assertTrue($this->call($apn, 'shouldFallBack', 400, 'BadDeviceToken', 'tok'));

        $this->set($apn, Apn::class, 'retried', ['tok' => true]);
        $this->assertFalse($this->call($apn, 'shouldFallBack', 400, 'BadDeviceToken', 'tok'));
    }

    public function test_only_a_bad_device_token_triggers_the_fallback() {
        $apn = $this->apn();

        $this->assertFalse($this->call($apn, 'shouldFallBack', 200, null, 'tok'));
        $this->assertFalse($this->call($apn, 'shouldFallBack', 403, 'InvalidProviderToken', 'tok'));
        $this->assertFalse($this->call($apn, 'shouldFallBack', 410, 'Unregistered', 'tok'));
        $this->assertFalse($this->call($apn, 'shouldFallBack', 400, 'BadTopic', 'tok'));
        $this->assertFalse($this->call($apn, 'shouldFallBack', 400, null, 'tok'));
    }

    public function test_the_fallback_can_be_turned_off() {
        $apn = $this->apn(['gateway_fallback' => false]);

        $this->assertFalse($this->call($apn, 'shouldFallBack', 400, 'BadDeviceToken', 'tok'));
    }

    public function test_only_an_explicit_dry_run_of_false_means_production() {
        $this->assertTrue($this->call($this->apn(['dry_run' => true]), 'usesSandbox'));
        $this->assertFalse($this->call($this->apn(['dry_run' => false]), 'usesSandbox'));
    }

    public function test_the_gateway_url_follows_the_choice() {
        $apn = $this->apn();

        $this->assertSame('https://api.development.push.apple.com/3/device/abc', $this->call($apn, 'gatewayUrl', 'abc', true));
        $this->assertSame('https://api.push.apple.com/3/device/abc', $this->call($apn, 'gatewayUrl', 'abc', false));
    }

    public function test_a_handle_remembers_its_gateway_and_can_be_prepared_for_the_other_one() {
        $apn = $this->apn(['dry_run' => true]);
        $message = ['aps' => ['alert' => 'hi'], 'headers' => []];

        $first = $apn->prepareHandle('tok', $message);
        $this->assertTrue($this->get($apn, 'gateways')['tok']);

        $retry = $apn->prepareHandle('tok', $message, false);
        $this->assertFalse($this->get($apn, 'gateways')['tok']);

        $this->assertSame('tok', curl_getinfo($first, CURLINFO_PRIVATE));
        $this->assertSame('tok', curl_getinfo($retry, CURLINFO_PRIVATE));
    }
}
