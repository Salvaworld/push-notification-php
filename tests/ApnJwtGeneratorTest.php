<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPUnit\Framework\TestCase;
use SalvaWorld\PushNotification\ApnJwtGenerator;

class ApnJwtGeneratorTest extends TestCase {

    private string $keyFile;
    private string $publicKey;

    protected function setUp(): void {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        openssl_pkey_export($key, $privatePem);
        $this->publicKey = openssl_pkey_get_details($key)['key'];
        $this->keyFile = tempnam(sys_get_temp_dir(), 'apn');
        file_put_contents($this->keyFile, $privatePem);
    }

    protected function tearDown(): void {
        @unlink($this->keyFile);
    }

    private function parts(string $jwt): array {
        $parts = explode('.', $jwt);
        $this->assertCount(3, $parts);

        return $parts;
    }

    private function decode(string $segment): string {
        return base64_decode(strtr($segment, '-_', '+/'));
    }

    public function test_the_signature_is_the_raw_64_bytes_apple_requires() {
        $generator = new ApnJwtGenerator('KEY1234567', 'TEAM123456', $this->keyFile);

        // openssl returns a DER signature of about 70 bytes, which Apple refuses
        for ($i = 0; $i < 25; $i++) {
            $jwt = (new ApnJwtGenerator('KEY1234567', 'TEAM123456', $this->keyFile))->generateToken();
            $this->assertSame(64, strlen($this->decode($this->parts($jwt)[2])));
        }
        $this->assertNotEmpty($generator->generateToken());
    }

    public function test_the_token_verifies_with_the_matching_public_key() {
        $jwt = (new ApnJwtGenerator('KEY1234567', 'TEAM123456', $this->keyFile))->generateToken();

        $claims = JWT::decode($jwt, new Key($this->publicKey, 'ES256'));

        $this->assertSame('TEAM123456', $claims->iss);
        $this->assertEqualsWithDelta(time(), $claims->iat, 5);
    }

    public function test_the_header_names_the_algorithm_and_the_key_id() {
        $jwt = (new ApnJwtGenerator('KEY1234567', 'TEAM123456', $this->keyFile))->generateToken();

        $header = json_decode($this->decode($this->parts($jwt)[0]), true);

        $this->assertSame('ES256', $header['alg']);
        $this->assertSame('KEY1234567', $header['kid']);
    }

    public function test_the_token_is_reused_until_it_is_about_to_expire() {
        $generator = new ApnJwtGenerator('KEY1234567', 'TEAM123456', $this->keyFile);

        $this->assertSame($generator->generateToken(), $generator->generateToken());
    }

    public function test_a_missing_key_file_is_reported() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Auth key file not found');

        new ApnJwtGenerator('KEY1234567', 'TEAM123456', '/no/such/key.p8');
    }

    public function test_an_invalid_key_file_is_reported() {
        file_put_contents($this->keyFile, 'not a key');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid auth key file');

        new ApnJwtGenerator('KEY1234567', 'TEAM123456', $this->keyFile);
    }
}
