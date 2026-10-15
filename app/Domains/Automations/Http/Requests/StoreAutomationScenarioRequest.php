<?php

namespace App\Domains\Automations\Http\Requests;

use App\Domains\Automations\Enums\AutomationActionType;
use App\Domains\Automations\Enums\AutomationConditionField;
use App\Domains\Automations\Enums\AutomationConditionOperator;
use App\Domains\Automations\Enums\AutomationStatus;
use App\Domains\Automations\Enums\AutomationTriggerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAutomationScenarioRequest extends FormRequest
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
            'status' => ['sometimes', Rule::enum(AutomationStatus::class)],
            'trigger_type' => ['required', Rule::enum(AutomationTriggerType::class)],
            'conditions' => ['sometimes', 'array'],
            'conditions.*.field' => ['required_with:conditions', Rule::enum(AutomationConditionField::class)],
            'conditions.*.operator' => ['required_with:conditions', Rule::enum(AutomationConditionOperator::class)],
            'conditions.*.value' => ['required_with:conditions', 'string', 'max:200'],
            'steps' => ['sometimes', 'array'],
            'steps.*.type' => ['required_with:steps', Rule::enum(AutomationActionType::class)],
            'steps.*.config' => ['required_with:steps', 'array'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nom', 'trigger_type' => 'déclencheur', 'conditions' => 'critères', 'steps' => 'étapes'];
    }

    /** @return array{name: string, position: int, status: string, trigger_type: string, conditions: list<array{field: string, operator: string, value: string}>, steps: list<array{type: string, config: array<string, mixed>}>} */
    public function toAttributes(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'position' => $this->integer('position', 0),
            'status' => $this->string('status', AutomationStatus::Draft->value)->toString(),
            'trigger_type' => $this->string('trigger_type')->toString(),
            'conditions' => $this->input('conditions', []),
            'steps' => $this->input('steps', []),
        ];
    }
}
