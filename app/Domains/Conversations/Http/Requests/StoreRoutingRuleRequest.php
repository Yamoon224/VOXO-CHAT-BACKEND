<?php

namespace App\Domains\Conversations\Http\Requests;

use App\Domains\Conversations\Enums\RoutingActionType;
use App\Domains\Conversations\Enums\RoutingConditionField;
use App\Domains\Conversations\Enums\RoutingConditionOperator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoutingRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'position' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'conditions' => ['sometimes', 'array'],
            'conditions.*.field' => ['required_with:conditions', Rule::enum(RoutingConditionField::class)],
            'conditions.*.operator' => ['required_with:conditions', Rule::enum(RoutingConditionOperator::class)],
            'conditions.*.value' => ['required_with:conditions', 'string', 'max:200'],
            'action_type' => ['required', Rule::enum(RoutingActionType::class)],
            'assignee_user_ids' => ['required', 'array', 'min:1'],
            'assignee_user_ids.*' => ['string', 'uuid'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nom', 'conditions' => 'critères', 'action_type' => "type d'action", 'assignee_user_ids' => 'agents'];
    }

    /** @return array{name: string, position: int, is_active: bool, conditions: list<array{field: string, operator: string, value: string}>, action_type: RoutingActionType, assignee_user_ids: list<string>} */
    public function toAttributes(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'position' => $this->integer('position', 0),
            'is_active' => $this->boolean('is_active', true),
            'conditions' => $this->input('conditions', []),
            'action_type' => RoutingActionType::from($this->string('action_type')->toString()),
            'assignee_user_ids' => $this->input('assignee_user_ids', []),
        ];
    }
}
