<?php

namespace App\Services\Quality;

use App\Repositories\Quality\QualityInspectionRepository;
use App\Repositories\Quality\QualityParameterRepository;
use App\Validators\QualityInspectionValidator;
use App\Validators\QualityParameterValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Quality inspection business logic.
 *
 * @agent-service: Quality inspections
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class QualityInspectionService
{
    protected QualityInspectionRepository $inspections;
    protected QualityParameterRepository $parameters;
    protected QualityInspectionValidator $inspectionValidator;
    protected QualityParameterValidator $parameterValidator;

    public function __construct(
        ?QualityInspectionRepository $inspections = null,
        ?QualityParameterRepository $parameters = null,
        ?QualityInspectionValidator $inspectionValidator = null,
        ?QualityParameterValidator $parameterValidator = null
    ) {
        $this->inspections = $inspections ?? new QualityInspectionRepository();
        $db = $this->inspections->db();
        $this->parameters = $parameters ?? new QualityParameterRepository(null, $db);
        $this->inspectionValidator = $inspectionValidator ?? new QualityInspectionValidator();
        $this->parameterValidator = $parameterValidator ?? new QualityParameterValidator();
    }

    /** List parameters. @agent-use: GET /api/quality-parameters */
    public function listParameters(array $filters): array
    {
        $validated = $this->parameterValidator->validateFilters($filters);
        return ['success' => true, 'data' => $this->parameters->list($validated)];
    }

    /** Create parameter. */
    public function createParameter(array $data): array
    {
        $validated = $this->parameterValidator->validateCreate($data);
        if ($this->parameters->existsByName($validated['name'])) {
            throw new InvalidArgumentException('Quality parameter name already exists');
        }
        $param = $this->parameters->create($validated);
        return ['success' => true, 'data' => $param];
    }

    /** Update parameter. */
    public function updateParameter(int $id, array $data): array
    {
        $this->requireParameter($id);
        $validated = $this->parameterValidator->validateUpdate($data);
        if (isset($validated['name']) && $this->parameters->existsByName($validated['name'], $id)) {
            throw new InvalidArgumentException('Quality parameter name already exists');
        }
        $param = $this->parameters->update($id, $validated);
        return ['success' => true, 'data' => $param];
    }

    /** Delete parameter when unused. */
    public function deleteParameter(int $id): array
    {
        $this->requireParameter($id);
        if ($this->parameters->isUsed($id)) {
            throw new InvalidArgumentException('Parameter is used by inspections');
        }
        $this->parameters->delete($id);
        return ['success' => true];
    }

    /** List inspections. */
    public function listInspections(array $filters): array
    {
        $validated = $this->inspectionValidator->validateList($filters);
        return ['success' => true, 'data' => $this->inspections->list($validated)];
    }

    /** Show inspection with items. */
    public function show(int $id): array
    {
        return ['success' => true, 'data' => $this->requireInspection($id)];
    }

    /**
     * Create a draft inspection with items.
     * @agent-use: Draft creation for inbound/outbound
     * @agent-pattern: Validation + repository orchestration
     */
    public function createInspection(array $data): array
    {
        $validated = $this->inspectionValidator->validateCreate($data);
        $paramIds = array_unique(array_column($validated['items'], 'parameter_id'));
        $parameters = $this->parameters->findByIds($paramIds);
        if (count(array_diff($paramIds, array_keys($parameters))) > 0) {
            throw new RuntimeException('Quality parameter not found');
        }

        $items = [];
        foreach ($validated['items'] as $item) {
            $param = $parameters[$item['parameter_id']] ?? null;
            if (! $param) {
                throw new RuntimeException('Quality parameter not found');
            }
            $items[] = [
                'parameter_id' => $item['parameter_id'],
                'parameter_name' => $param['name'],
                'uom' => $param['uom'] ?? null,
                'value_numeric' => $item['value_numeric'],
                'value_text' => $item['value_text'] ?? ($item['value_numeric'] !== null ? (string) $item['value_numeric'] : null),
                'notes' => $item['notes'] ?? null,
            ];
        }

        $inspection = [
            'reference_type' => trim((string) $validated['reference_type']),
            'reference_id' => (int) $validated['reference_id'],
            'status' => 'draft',
            'result' => 'pending',
            'inspected_by' => $validated['inspected_by'] ?? null,
            'inspected_at' => null,
            'submitted_at' => null,
            'approved_by' => null,
            'approved_at' => null,
            'notes' => $validated['notes'] ?? null,
        ];

        $created = $this->inspections->create($inspection, $items);
        return ['success' => true, 'data' => $created];
    }

    /**
     * Submit and evaluate inspection.
     * @agent-use: Submit inspection
     * @agent-pattern: Evaluation + status transition
     */
    public function submitInspection(int $id, array $payload = []): array
    {
        $inspection = $this->requireInspection($id);
        if ($inspection['status'] !== 'draft') {
            throw new InvalidArgumentException('Only draft inspections can be submitted');
        }

        $paramIds = array_unique($this->inspections->parameterIds($id));
        $parameters = $this->parameters->findByIds($paramIds);
        if (count(array_diff($paramIds, array_keys($parameters))) > 0) {
            throw new RuntimeException('Quality parameter not found');
        }

        $updates = $this->inspectionValidator->validateSubmit($payload);
        if (isset($updates['inspected_by'])) {
            $inspection['inspected_by'] = $updates['inspected_by'];
        }

        [$evaluatedItems, $result] = $this->evaluateItems($inspection['items'], $parameters);
        $meta = [
            'status' => 'submitted',
            'result' => $result,
            'submitted_at' => $this->now(),
            'inspected_by' => $inspection['inspected_by'] ?? null,
            'inspected_at' => $inspection['inspected_at'] ?? $this->now(),
        ];

        $updated = $this->inspections->updateEvaluation($id, $evaluatedItems, $meta);
        return ['success' => true, 'data' => $updated];
    }

    /**
     * Approve submitted inspection.
     * @agent-use: Approve inspection
     * @agent-pattern: Guarded transition
     */
    public function approveInspection(int $id, array $payload): array
    {
        $inspection = $this->requireInspection($id);
        if ($inspection['status'] !== 'submitted') {
            throw new InvalidArgumentException('Inspection must be submitted before approval');
        }
        if (($inspection['result'] ?? 'pending') !== 'pass') {
            throw new InvalidArgumentException('Cannot approve a failed inspection');
        }

        $data = $this->inspectionValidator->validateApprove($payload);
        $meta = [
            'status' => 'approved',
            'approved_by' => $data['approved_by'],
            'approved_at' => $this->now(),
        ];

        $updated = $this->inspections->updateEvaluation($id, [], $meta);
        return ['success' => true, 'data' => $updated];
    }

    /**
     * Reject draft/submitted inspection.
     * @agent-use: Reject inspection
     * @agent-pattern: Guarded transition
     */
    public function rejectInspection(int $id, array $payload): array
    {
        $inspection = $this->requireInspection($id);
        if (! in_array($inspection['status'], ['draft', 'submitted'], true)) {
            throw new InvalidArgumentException('Only draft or submitted inspections can be rejected');
        }

        $data = $this->inspectionValidator->validateReject($payload);
        $meta = [
            'status' => 'rejected',
            'result' => 'fail',
            'rejected_by' => $data['rejected_by'],
            'rejected_at' => $this->now(),
        ];
        if ($data['reason'] ?? null) {
            $meta['notes'] = trim((string) (($inspection['notes'] ?? '') . ' ' . $data['reason']));
        }

        $updated = $this->inspections->updateEvaluation($id, [], $meta);
        return ['success' => true, 'data' => $updated];
    }

    private function requireInspection(int $id): array
    {
        $inspection = $this->inspections->findWithItems($id);
        if (! $inspection) {
            throw new RuntimeException('Inspection not found');
        }
        return $inspection;
    }

    private function requireParameter(int $id): array
    {
        $parameter = $this->parameters->find($id);
        if (! $parameter) {
            throw new RuntimeException('Quality parameter not found');
        }
        return $parameter;
    }

    /**
     * Evaluate inspection items based on parameter thresholds/specs.
     *
     * @return array{0: array<int,array>, 1: string}
     */
    private function evaluateItems(array $items, array $parameters): array
    {
        $evaluated = [];
        $allPass = true;
        foreach ($items as $item) {
            $paramId = (int) ($item['parameter_id'] ?? 0);
            $parameter = $parameters[$paramId] ?? null;
            if (! $parameter) {
                throw new RuntimeException('Quality parameter not found');
            }
            $valueNumeric = array_key_exists('value_numeric', $item) && $item['value_numeric'] !== null ? (float) $item['value_numeric'] : null;
            $valueText = $item['value_text'] ?? ($valueNumeric !== null ? (string) $valueNumeric : null);

            $pass = true;
            $min = isset($parameter['min_value']) ? (float) $parameter['min_value'] : null;
            $max = isset($parameter['max_value']) ? (float) $parameter['max_value'] : null;
            $spec = isset($parameter['specification']) ? trim((string) $parameter['specification']) : null;

            if (($min !== null || $max !== null) && $valueNumeric === null) {
                throw new InvalidArgumentException('Numeric value required for parameter ' . $parameter['name']);
            }
            if ($min !== null && $valueNumeric !== null && $valueNumeric < $min) {
                $pass = false;
            }
            if ($max !== null && $valueNumeric !== null && $valueNumeric > $max) {
                $pass = false;
            }
            if ($spec !== null && $spec !== '') {
                $actual = $valueText ?? ($valueNumeric !== null ? (string) $valueNumeric : '');
                if ($actual === '' || strcasecmp($spec, trim((string) $actual)) !== 0) {
                    $pass = false;
                }
            }

            $evaluated[] = [
                'id' => isset($item['id']) ? (int) $item['id'] : 0,
                'parameter_id' => $paramId,
                'value_numeric' => $valueNumeric,
                'value_text' => $valueText,
                'pass_flag' => $pass ? 1 : 0,
            ];
            $allPass = $allPass && $pass;
        }

        return [$evaluated, $allPass ? 'pass' : 'fail'];
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
