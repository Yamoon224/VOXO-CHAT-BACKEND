<?php

namespace Tests\Unit\Channels;

use App\Domains\Channels\Providers\ArrayEmailChannelProvider;
use App\Domains\Channels\Services\ChannelDispatchService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryChannelEmailSettingsRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\TestCase;

class ChannelDispatchServiceTest extends TestCase
{
    #[Test]
    public function un_canal_autre_que_l_e_mail_n_est_jamais_relaye(): void
    {
        $provider = new ArrayEmailChannelProvider;
        $service = new ChannelDispatchService(new InMemoryChannelEmailSettingsRepository, $provider);

        $service->dispatchReply('workspace-1', 'widget', 'client@exemple.fr', 'Réponse.');

        $this->assertSame([], $provider->sent());
    }

    #[Test]
    public function sans_adresse_connue_rien_n_est_relaye(): void
    {
        $provider = new ArrayEmailChannelProvider;
        $service = new ChannelDispatchService(new InMemoryChannelEmailSettingsRepository, $provider);

        $service->dispatchReply('workspace-1', 'email', null, 'Réponse.');

        $this->assertSame([], $provider->sent());
    }

    #[Test]
    public function un_canal_e_mail_desactive_n_est_pas_relaye(): void
    {
        $settings = ModelFactory::channelEmailSettings(['workspace_id' => 'workspace-1', 'enabled' => false, 'reply_from_email' => 'support@exemple.fr']);
        $provider = new ArrayEmailChannelProvider;
        $service = new ChannelDispatchService(new InMemoryChannelEmailSettingsRepository($settings), $provider);

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
        $service = new ChannelDispatchService(new InMemoryChannelEmailSettingsRepository($settings), $provider);

        $service->dispatchReply('workspace-1', 'email', 'client@exemple.fr', 'Réponse.');

        $sent = $provider->sent();
        $this->assertCount(1, $sent);
        $this->assertSame('client@exemple.fr', $sent[0]['to']);
        $this->assertSame('support@exemple.fr', $sent[0]['from_email']);
        $this->assertSame('Support Exemple', $sent[0]['from_name']);
        $this->assertSame('Réponse.', $sent[0]['body']);
    }
}
