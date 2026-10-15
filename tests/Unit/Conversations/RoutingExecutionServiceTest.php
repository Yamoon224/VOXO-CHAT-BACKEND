<?php

namespace Tests\Unit\Conversations;

use App\Domains\Conversations\Enums\ConversationChannel;
use App\Domains\Conversations\Enums\RoutingActionType;
use App\Domains\Conversations\Services\RoutingExecutionService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryConversationRepository;
use Tests\Support\Fakes\InMemoryRoutingRuleRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\TestCase;

class RoutingExecutionServiceTest extends TestCase
{
    #[Test]
    public function une_regle_sur_le_canal_affecte_la_conversation_correspondante(): void
    {
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1', 'channel' => ConversationChannel::Email]);
        $conversations = new InMemoryConversationRepository($conversation);
        $rule = ModelFactory::routingRule([
            'workspace_id' => 'workspace-1',
            'conditions' => [['field' => 'channel', 'operator' => 'equals', 'value' => 'email']],
            'action_type' => RoutingActionType::AssignUser,
            'assignee_user_ids' => ['user-1'],
        ]);
        $service = new RoutingExecutionService(new InMemoryRoutingRuleRepository($rule), $conversations);

        $service->applyTo($conversation, 'Bonjour.');

        $this->assertSame('user-1', $conversation->assigned_user_id);
    }

    #[Test]
    public function une_regle_sur_le_message_filtre_par_mot_cle(): void
    {
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1']);
        $conversations = new InMemoryConversationRepository($conversation);
        $rule = ModelFactory::routingRule([
            'workspace_id' => 'workspace-1',
            'conditions' => [['field' => 'message', 'operator' => 'contains', 'value' => 'facture']],
            'action_type' => RoutingActionType::AssignUser,
            'assignee_user_ids' => ['user-billing'],
        ]);
        $service = new RoutingExecutionService(new InMemoryRoutingRuleRepository($rule), $conversations);

        $service->applyTo($conversation, "J'ai un souci avec ma FACTURE de ce mois.");

        $this->assertSame('user-billing', $conversation->assigned_user_id);
    }

    #[Test]
    public function sans_regle_correspondante_la_conversation_reste_non_affectee(): void
    {
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1']);
        $conversations = new InMemoryConversationRepository($conversation);
        $rule = ModelFactory::routingRule([
            'workspace_id' => 'workspace-1',
            'conditions' => [['field' => 'message', 'operator' => 'contains', 'value' => 'facture']],
            'action_type' => RoutingActionType::AssignUser,
            'assignee_user_ids' => ['user-billing'],
        ]);
        $service = new RoutingExecutionService(new InMemoryRoutingRuleRepository($rule), $conversations);

        $service->applyTo($conversation, 'Bonjour, une question sur le produit.');

        $this->assertNull($conversation->assigned_user_id);
    }

    #[Test]
    public function une_regle_inactive_est_ignoree(): void
    {
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1']);
        $conversations = new InMemoryConversationRepository($conversation);
        $rule = ModelFactory::routingRule([
            'workspace_id' => 'workspace-1',
            'is_active' => false,
            'conditions' => [],
            'action_type' => RoutingActionType::AssignUser,
            'assignee_user_ids' => ['user-1'],
        ]);
        $service = new RoutingExecutionService(new InMemoryRoutingRuleRepository($rule), $conversations);

        $service->applyTo($conversation, 'Bonjour.');

        $this->assertNull($conversation->assigned_user_id);
    }

    #[Test]
    public function une_regle_sans_condition_est_un_filet_par_defaut(): void
    {
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1']);
        $conversations = new InMemoryConversationRepository($conversation);
        $rule = ModelFactory::routingRule([
            'workspace_id' => 'workspace-1',
            'conditions' => [],
            'action_type' => RoutingActionType::AssignUser,
            'assignee_user_ids' => ['user-fallback'],
        ]);
        $service = new RoutingExecutionService(new InMemoryRoutingRuleRepository($rule), $conversations);

        $service->applyTo($conversation, "N'importe quel message.");

        $this->assertSame('user-fallback', $conversation->assigned_user_id);
    }

    #[Test]
    public function le_tourniquet_repartit_a_tour_de_role(): void
    {
        $rule = ModelFactory::routingRule([
            'workspace_id' => 'workspace-1',
            'conditions' => [],
            'action_type' => RoutingActionType::RoundRobin,
            'assignee_user_ids' => ['user-a', 'user-b'],
            'next_round_robin_index' => 0,
        ]);
        $rules = new InMemoryRoutingRuleRepository($rule);

        $first = ModelFactory::conversation(['workspace_id' => 'workspace-1']);
        (new RoutingExecutionService($rules, new InMemoryConversationRepository($first)))->applyTo($first, 'Bonjour.');
        $this->assertSame('user-a', $first->assigned_user_id);

        $second = ModelFactory::conversation(['workspace_id' => 'workspace-1']);
        (new RoutingExecutionService($rules, new InMemoryConversationRepository($second)))->applyTo($second, 'Bonjour.');
        $this->assertSame('user-b', $second->assigned_user_id);

        $third = ModelFactory::conversation(['workspace_id' => 'workspace-1']);
        (new RoutingExecutionService($rules, new InMemoryConversationRepository($third)))->applyTo($third, 'Bonjour.');
        $this->assertSame('user-a', $third->assigned_user_id);
    }

    #[Test]
    public function seule_la_premiere_regle_correspondante_agit(): void
    {
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1']);
        $conversations = new InMemoryConversationRepository($conversation);
        $first = ModelFactory::routingRule([
            'workspace_id' => 'workspace-1',
            'position' => 0,
            'conditions' => [],
            'action_type' => RoutingActionType::AssignUser,
            'assignee_user_ids' => ['user-first'],
        ]);
        $second = ModelFactory::routingRule([
            'workspace_id' => 'workspace-1',
            'position' => 1,
            'conditions' => [],
            'action_type' => RoutingActionType::AssignUser,
            'assignee_user_ids' => ['user-second'],
        ]);
        $service = new RoutingExecutionService(new InMemoryRoutingRuleRepository($first, $second), $conversations);

        $service->applyTo($conversation, 'Bonjour.');

        $this->assertSame('user-first', $conversation->assigned_user_id);
    }
}
