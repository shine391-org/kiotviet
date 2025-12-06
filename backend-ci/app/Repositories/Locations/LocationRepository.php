<?php

namespace App\Repositories\Locations;

use App\Models\ProvinceModel;
use App\Models\DistrictModel;
use App\Models\WardModel;

/**
 * Location persistence layer.
 *
 * @agent-repository: Locations
 * @agent-pattern: Repository pattern
 */
class LocationRepository
{
    protected ProvinceModel $provinces;
    protected DistrictModel $districts;
    protected WardModel $wards;

    public function __construct(
        ?ProvinceModel $provinces = null,
        ?DistrictModel $districts = null,
        ?WardModel $wards = null
    ) {
        $this->provinces = $provinces ?? new ProvinceModel();
        $this->districts = $districts ?? new DistrictModel();
        $this->wards = $wards ?? new WardModel();
    }

    /** Get all provinces with optional search. */
    public function findProvinces(array $filters = []): array
    {
        $builder = $this->provinces->builder();
        $builder->where('is_active', 1);

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $builder->groupStart()
                ->like('name', $s)
                ->orLike('full_name', $s)
                ->orLike('code', $s)
                ->groupEnd();
        }

        $builder->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC');

        return $builder->get()->getResultArray();
    }

    /** Get districts by province. */
    public function findDistricts(int $provinceId, array $filters = []): array
    {
        $builder = $this->districts->builder();
        $builder->where('province_id', $provinceId)
            ->where('is_active', 1);

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $builder->groupStart()
                ->like('name', $s)
                ->orLike('full_name', $s)
                ->orLike('code', $s)
                ->groupEnd();
        }

        $builder->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC');

        return $builder->get()->getResultArray();
    }

    /** Get wards by district. */
    public function findWards(int $districtId, array $filters = []): array
    {
        $builder = $this->wards->builder();
        $builder->where('district_id', $districtId)
            ->where('is_active', 1);

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $builder->groupStart()
                ->like('name', $s)
                ->orLike('full_name', $s)
                ->orLike('code', $s)
                ->groupEnd();
        }

        $builder->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC');

        return $builder->get()->getResultArray();
    }

    /** Get province by ID. */
    public function findProvinceById(int $id): ?array
    {
        return $this->provinces->find($id);
    }

    /** Get district by ID. */
    public function findDistrictById(int $id): ?array
    {
        return $this->districts->find($id);
    }

    /** Get ward by ID. */
    public function findWardById(int $id): ?array
    {
        return $this->wards->find($id);
    }

    /** Bulk insert provinces. */
    public function insertProvinces(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }
        $this->provinces->insertBatch($rows);
        return count($rows);
    }

    /** Bulk insert districts. */
    public function insertDistricts(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }
        $this->districts->insertBatch($rows);
        return count($rows);
    }

    /** Bulk insert wards. */
    public function insertWards(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }
        $this->wards->insertBatch($rows);
        return count($rows);
    }

    /** Count provinces. */
    public function countProvinces(): int
    {
        return $this->provinces->where('is_active', 1)->countAllResults();
    }

    /** Count districts. */
    public function countDistricts(): int
    {
        return $this->districts->where('is_active', 1)->countAllResults();
    }

    /** Count wards. */
    public function countWards(): int
    {
        return $this->wards->where('is_active', 1)->countAllResults();
    }
}
