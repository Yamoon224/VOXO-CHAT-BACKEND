<?php

namespace Tests\Unit\Auth;

use App\Domains\Auth\Support\EmailVerificationToken;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EmailVerificationTokenTest extends TestCase
{
    private const USER_ID = '0199a1b2-0000-7000-8000-000000000001';

    private DateTimeImmutable $now;

    private EmailVerificationToken $tokens;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-10-07 10:00:00');
        $this->tokens = new EmailVerificationToken('signing-key', 60);
    }

    #[Test]
    public function un_jeton_emis_designe_son_compte_et_son_adresse(): void
    {
        $token = $this->tokens->issue(self::USER_ID, 'Awa@Example.test', $this->now);

        $this->assertSame(self::USER_ID, $this->tokens->userId($token, $this->now));
        // La casse et les espaces de saisie ne changent pas l'adresse.
        $this->assertTrue($this->tokens->matchesEmail($token, ' awa@example.test ', $this->now));
    }

    /** Si l'adresse du compte change, l'ancien lien ne vérifie plus rien. */
    #[Test]
    public function il_ne_vaut_pas_pour_une_autre_adresse(): void
    {
        $token = $this->tokens->issue(self::USER_ID, 'awa@example.test', $this->now);

        $this->assertFalse($this->tokens->matchesEmail($token, 'autre@example.test', $this->now));
    }

    #[Test]
    public function il_expire(): void
    {
        $token = $this->tokens->issue(self::USER_ID, 'awa@example.test', $this->now);

        $this->assertNotNull($this->tokens->userId($token, $this->now->modify('+59 minutes')));
        $this->assertNull($this->tokens->userId($token, $this->now->modify('+61 minutes')));
        $this->assertFalse($this->tokens->matchesEmail($token, 'awa@example.test', $this->now->modify('+61 minutes')));
    }

    #[Test]
    public function un_contenu_modifie_invalide_la_signature(): void
    {
        $token = $this->tokens->issue(self::USER_ID, 'awa@example.test', $this->now);
        [$payload, $signature] = explode('.', $token);

        $forgedPayload = rtrim(strtr(base64_encode((string) json_encode([
            'u' => 'autre-compte', 'e' => 'x', 'x' => $this->now->getTimestamp() + 3600,
        ])), '+/', '-_'), '=');

        $this->assertNull($this->tokens->userId($forgedPayload.'.'.$signature, $this->now));
        $this->assertNull($this->tokens->userId($payload.'.'.strrev($signature), $this->now));
    }

    #[Test]
    public function un_jeton_signe_par_une_autre_cle_est_refuse(): void
    {
        $token = (new EmailVerificationToken('autre-cle', 60))->issue(self::USER_ID, 'awa@example.test', $this->now);

        $this->assertNull($this->tokens->userId($token, $this->now));
    }

    #[Test]
    public function une_chaine_quelconque_est_refusee(): void
    {
        foreach (['', 'abc', 'a.b.c', '.', 'pas-un-jeton.signature'] as $garbage) {
            $this->assertNull($this->tokens->userId($garbage, $this->now));
        }
    }
}
