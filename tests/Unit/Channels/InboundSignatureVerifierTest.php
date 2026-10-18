<?php

namespace Tests\Unit\Channels;

use App\Domains\Channels\Support\InboundSignatureVerifier;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InboundSignatureVerifierTest extends TestCase
{
    #[Test]
    public function une_signature_meta_valide_est_acceptee(): void
    {
        $body = '{"entry":[]}';
        $secret = 'app-secret';
        $signature = 'sha256='.hash_hmac('sha256', $body, $secret);
        $request = Request::create('/inbound', 'POST', [], [], [], [], $body);
        $request->headers->set('X-Hub-Signature-256', $signature);

        $this->assertTrue(InboundSignatureVerifier::metaSignatureValid($request, ['app_secret' => $secret]));
    }

    #[Test]
    public function une_signature_meta_falsifiee_est_refusee(): void
    {
        $body = '{"entry":[]}';
        $request = Request::create('/inbound', 'POST', [], [], [], [], $body);
        $request->headers->set('X-Hub-Signature-256', 'sha256=0000');

        $this->assertFalse(InboundSignatureVerifier::metaSignatureValid($request, ['app_secret' => 'app-secret']));
    }

    #[Test]
    public function une_signature_meta_sans_prefixe_sha256_est_refusee(): void
    {
        $body = '{"entry":[]}';
        $secret = 'app-secret';
        $request = Request::create('/inbound', 'POST', [], [], [], [], $body);
        $request->headers->set('X-Hub-Signature-256', hash_hmac('sha256', $body, $secret));

        $this->assertFalse(InboundSignatureVerifier::metaSignatureValid($request, ['app_secret' => $secret]));
    }

    #[Test]
    public function sans_app_secret_configure_rien_n_est_accepte(): void
    {
        $request = Request::create('/inbound', 'POST', [], [], [], [], '{}');
        $request->headers->set('X-Hub-Signature-256', 'sha256=abc');

        $this->assertFalse(InboundSignatureVerifier::metaSignatureValid($request, []));
    }

    #[Test]
    public function un_jeton_secret_telegram_valide_est_accepte(): void
    {
        $request = Request::create('/inbound', 'POST');
        $request->headers->set('X-Telegram-Bot-Api-Secret-Token', 'le-secret');

        $this->assertTrue(InboundSignatureVerifier::telegramSecretValid($request, ['secret_token' => 'le-secret']));
    }

    #[Test]
    public function un_jeton_secret_telegram_errone_est_refuse(): void
    {
        $request = Request::create('/inbound', 'POST');
        $request->headers->set('X-Telegram-Bot-Api-Secret-Token', 'mauvais');

        $this->assertFalse(InboundSignatureVerifier::telegramSecretValid($request, ['secret_token' => 'le-secret']));
    }

    #[Test]
    public function une_signature_twilio_valide_est_acceptee(): void
    {
        $authToken = 'auth-token';
        $params = ['From' => '+33612345678', 'Body' => 'Bonjour'];
        $request = Request::create('http://localhost/inbound', 'POST', $params);

        $data = $request->fullUrl();
        $sorted = $params;
        ksort($sorted);
        foreach ($sorted as $key => $value) {
            $data .= $key.$value;
        }
        $signature = base64_encode(hash_hmac('sha1', $data, $authToken, true));
        $request->headers->set('X-Twilio-Signature', $signature);

        $this->assertTrue(InboundSignatureVerifier::twilioSignatureValid($request, ['auth_token' => $authToken]));
    }

    #[Test]
    public function une_signature_twilio_falsifiee_est_refusee(): void
    {
        $params = ['From' => '+33612345678', 'Body' => 'Bonjour'];
        $request = Request::create('http://localhost/inbound', 'POST', $params);
        $request->headers->set('X-Twilio-Signature', 'signature-invalide');

        $this->assertFalse(InboundSignatureVerifier::twilioSignatureValid($request, ['auth_token' => 'auth-token']));
    }

    #[Test]
    public function une_signature_x_valide_est_acceptee(): void
    {
        $body = '{"direct_message_events":[]}';
        $secret = 'consumer-secret';
        $signature = 'sha256='.base64_encode(hash_hmac('sha256', $body, $secret, true));
        $request = Request::create('/inbound', 'POST', [], [], [], [], $body);
        $request->headers->set('X-Twitter-Webhooks-Signature', $signature);

        $this->assertTrue(InboundSignatureVerifier::xSignatureValid($request, ['consumer_secret' => $secret]));
    }

    #[Test]
    public function une_signature_x_falsifiee_est_refusee(): void
    {
        $body = '{"direct_message_events":[]}';
        $request = Request::create('/inbound', 'POST', [], [], [], [], $body);
        $request->headers->set('X-Twitter-Webhooks-Signature', 'sha256=0000');

        $this->assertFalse(InboundSignatureVerifier::xSignatureValid($request, ['consumer_secret' => 'consumer-secret']));
    }

    #[Test]
    public function sans_consumer_secret_configure_rien_n_est_accepte_pour_x(): void
    {
        $request = Request::create('/inbound', 'POST', [], [], [], [], '{}');
        $request->headers->set('X-Twitter-Webhooks-Signature', 'sha256=abc');

        $this->assertFalse(InboundSignatureVerifier::xSignatureValid($request, []));
    }

    #[Test]
    public function une_signature_viber_valide_est_acceptee(): void
    {
        $body = '{"event":"message"}';
        $secret = 'auth-token-viber';
        $signature = hash_hmac('sha256', $body, $secret);
        $request = Request::create('/inbound', 'POST', [], [], [], [], $body);
        $request->headers->set('X-Viber-Content-Signature', $signature);

        $this->assertTrue(InboundSignatureVerifier::viberSignatureValid($request, ['auth_token' => $secret]));
    }

    #[Test]
    public function une_signature_viber_falsifiee_est_refusee(): void
    {
        $body = '{"event":"message"}';
        $request = Request::create('/inbound', 'POST', [], [], [], [], $body);
        $request->headers->set('X-Viber-Content-Signature', 'signature-invalide');

        $this->assertFalse(InboundSignatureVerifier::viberSignatureValid($request, ['auth_token' => 'auth-token-viber']));
    }

    #[Test]
    public function une_signature_line_valide_est_acceptee(): void
    {
        $body = '{"events":[]}';
        $secret = 'channel-secret-line';
        $signature = base64_encode(hash_hmac('sha256', $body, $secret, true));
        $request = Request::create('/inbound', 'POST', [], [], [], [], $body);
        $request->headers->set('X-Line-Signature', $signature);

        $this->assertTrue(InboundSignatureVerifier::lineSignatureValid($request, ['channel_secret' => $secret]));
    }

    #[Test]
    public function une_signature_line_falsifiee_est_refusee(): void
    {
        $body = '{"events":[]}';
        $request = Request::create('/inbound', 'POST', [], [], [], [], $body);
        $request->headers->set('X-Line-Signature', 'signature-invalide');

        $this->assertFalse(InboundSignatureVerifier::lineSignatureValid($request, ['channel_secret' => 'channel-secret-line']));
    }
}
