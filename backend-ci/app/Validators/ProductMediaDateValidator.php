<?php
namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/** Media date filter validation. @agent-validator: Media library date filters @agent-pattern: Validation first @agent-reusable: MEDIUM */
class ProductMediaDateValidator
{
    protected Validation $v;
    public function __construct(?Validation $validation=null){$this->v=$validation??Services::validation(null,false);}

    /** Validate by-date filters. @agent-use: GET /api/products/media/by-date @agent-pattern: Filter validation */
    public function validateFilters(array $input): array
    {
        $v=$this->run(array_merge(['year'=>null,'month'=>null,'entity_id'=>null],$input),[
            'year'=>'permit_empty|integer|greater_than_equal_to[1970]|less_than_equal_to[2100]',
            'month'=>'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[12]',
            'entity_id'=>'permit_empty|integer|greater_than[0]',
        ]);
        return ['year'=>isset($v['year'])?(int)$v['year']:null,'month'=>isset($v['month'])?(int)$v['month']:null,'entity_id'=>isset($v['entity_id'])?(int)$v['entity_id']:null];
    }

    private function run(array $data,array $rules): array
    {if(!$this->v->setRules($rules)->run($data)){throw new InvalidArgumentException(implode('; ',array_filter($this->v->getErrors()))?:'Invalid data');}return $this->v->getValidated();}
}
