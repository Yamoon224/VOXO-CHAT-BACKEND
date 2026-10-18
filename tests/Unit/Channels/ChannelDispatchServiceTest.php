<?php

namespace Tests\Unit\Channels;

use App\Domains\Channels\Providers\ArrayEmailChannelProvider;
use App\Domains\Channels\Providers\ArrayInstagramChannelProvider;
use App\Domains\Channels\Providers\ArrayLineChannelProvider;
use App\Domains\Channels\Providers\ArrayMessengerChannelProvider;
use App\Domains\Channels\Providers\ArraySmsChannelProvider;
use App\Domains\Channels\Providers\ArrayTelegramChannelProvider;
use App\Domains\Channels\Providers\ArrayTelephonyChannelProvider;
use App\Domains\Channels\Providers\ArrayViberChannelProvider;
use App\Domains\Channels\Providers\ArrayWhatsAppChannelProvider;
use App\Domains\Channels\Providers\ArrayXChannelProvider;
use App\Domains\Channels\Services\ChannelDispatchService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryChannelConnectionRepository;
use Tests\Support\Fakes\InMemoryChannelEmailSettingsRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\TestCase;

class ChannelDispatchServiceTest extends TestCase
{
    private function makeService(
        ?InMemoryChannelEmailSettingsRepository $emailSettings = null,
        ?InMemoryChannelConnectionRepository $connections = null,
        ?ArrayEmailChannelProvider $emailProvider = null,
        ?ArrayWhatsAppChannelProvider $whatsApp = null,
    ): ChannelDispatchService {
        return new ChannelDispatchService(
            $emailSettings ?? new InMemoryChannelEmailSettingsRepository,
            $emailProvider ?? new ArrayEmailChannelProvider,
            $connections ?? new InMemoryChannelConnectionRepository,
            $whatsApp ?? new ArrayWhatsAppChannelProvider,
            new ArrayMessengerChannelProvider,
            new ArrayInstagramChannelProvider,
            new ArrayTelegramChannelProvider,
            new ArraySmsChannelProvider,
            new ArrayXChannelProvider,
            new ArrayViberChannelProvider,
            new ArrayLineChannelProvider,
            new ArrayTelephonyChannelProvider,
        );
    }

    #[Test]
    public function un_canal_widget_n_est_jamais_relaye(): void
    {
        $provider = new ArrayEmailChannelProvider;
        $service = $this->makeService(emailProvider: $provider);

        $service->dispatchReply('workspace-1', 'widget', 'client@exemple.fr', 'Réponse.');

        $this->assertSame([], $provider->sent());
    }

    #[Test]
    public function sans_adresse_connue_rien_n_est_relaye(): void
    {
        $provider = new ArrayEmailChannelProvider;
        $service = $this->makeService(emailProvider: $provider);

        $service->dispatchReply('workspace-1', 'email', null, 'Réponse.');

        $this->assertSame([], $provider->sent());
    }

    #[Test]
    public function un_canal_e_mail_desactive_n_est_pas_relaye(): void
    {
        $settings = ModelFactory::channelEmailSettings(['workspace_id' => 'workspace-1', 'enabled' => false, 'reply_from_email' => 'support@exemple.fr']);
        $provider = new ArrayEmailChannelProvider;
        $service = $this->makeService(new InMemoryChannelEmailSettingsRepository($settings), emailProvider: $provider);

        $service->dispatchReply('workspace-1', 'email', 'client@exemple.fr', 'Réponse.');

        $this->assertSame([], $provider->sent());
    }

    #[Test]
    public function un_canal_e_mail_actif_relaye_la_reponse_a_l_adresse_d_envoi(): void
    {
        $settings = ModelFactory::channelEmailSettings([
            'workspace_id' => 'workspace-1',
            'enabled' => true,
            'reply_from_email' => 'support@exemple.fr',
            'reply_from_name' => 'Support Exemple',
        ]);
        $provider = new ArrayEmailChannelProvider;
        $service = $this->makeService(new InMemoryChannelEmailSettingsRepository($settings), emailProvider: $provider);

        $service->dispatchReply('workspace-1', 'email', 'client@exemple.fr', 'Réponse.');

        $sent = $provider->sent();
        $this->assertCount(1, $sent);
        $this->assertSame('client@exemple.fr', $sent[0]['to']);
        $this->assertSame('support@exemple.fr', $sent[0]['from_email']);
        $this->assertSame('Support Exemple', $sent[0]['from_name']);
        $this->assertSame('Réponse.', $sent[0]['body']);
    }

    #[Test]
    public function un_canal_whatsapp_non_configure_n_est_pas_relaye(): void
    {
        $provider = new ArrayWhatsAppChannelProvider;
        $service = $this->makeService(whatsApp: $provider);

        $service->dispatchReply('workspace-1', 'whatsapp', '33612345678', 'Réponse.');

        $this->assertSame([], $provider->sent());
    }

    #[Test]
    public function un_canal_whatsapp_desactive_n_est_pas_relaye(): void
    {
        $connection = ModelFactory::channelConnection([
            'workspace_id' => 'workspace-1',
            'channel' => 'whatsapp',
            'enabled' => false,
            'credentials' => ['access_token' => 'token', 'phone_number_id' => '123'],
        ]);
        $provider = new ArrayWhatsAppChannelProvider;
        $service = $this->makeService(connections: new InMemoryChannelConnectionRepository($connection), whatsApp: $provider);

        $service->dispatchReply('workspace-1', 'whatsapp', '33612345678', 'Réponse.');

        $this->assertSame([], $provider->sent());
    }

    #[Test]
    public function un_canal_whatsapp_actif_relaye_la_reponse_avec_ses_identifiants(): void
    {
        $connection = ModelFactory::channelConnection([
            'workspace_id' => 'workspace-1',
            'channel' => 'whatsapp',
            'enabled' => true,
            'credentials' => ['access_token' => 'token-abc', 'phone_number_id' => '1234567890'],
        ]);
        $provider = new ArrayWhatsAppChannelProvider;
        $service = $this->makeService(connections: new InMemoryChannelConnectionRepository($connection), whatsApp: $provider);

        $service->dispatchReply('workspace-1', 'whatsapp', '33612345678', 'Réponse.');

        $sent = $provider->sent();
        $this->assertCount(1, $sent);
        $this->assertSame('33612345678', $sent[0]['to']);
        $this->assertSame('Réponse.', $sent[0]['body']);
        $this->assertSame('token-abc', $sent[0]['credentials']['access_token']);
    }
}
