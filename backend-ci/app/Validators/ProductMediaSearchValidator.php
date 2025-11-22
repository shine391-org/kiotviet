<?php
namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/** Media SKU search validation. @agent-validator: Media search filters @agent-pattern: Validation first @agent-reusable: MEDIUM */
class ProductMediaSearchValidator
{
    protected Validation $v;
    public function __construct(?Validation $validation=null){$this->v=$validation??Services::validation(null,false);}

    /** Validate SKU search filters. @agent-use: GET /api/products/media/search-sku @agent-pattern: Lightweight search validation */
    public function validate(array $input): array
    {
        $v=$this->run(array_merge(['sku'=>'','limit'=>20,'offset'=>0,'entity_id'=>null],$input),[
            'sku'=>'permit_empty|string|max_length[255]',
            'limit'=>'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[200]',
            'offset'=>'permit_empty|integer|greater_than_equal_to[0]',
            'entity_id'=>'permit_empty|integer|greater_than[0]',
        ]);
        return [
            'sku'=>trim((string)($v['sku']??'')),
            'limit'=>(int)($v['limit']??20),
            'offset'=>(int)($v['offset']??0),
            'entity_id'=>isset($v['entity_id'])?(int)$v['entity_id']:null
        ];
    }

    private function run(array $data,array $rules): array
    {if(!$this->v->setRules($rules)->run($data)){throw new InvalidArgumentException(implode('; ',array_filter($this->v->getErrors()))?:'Invalid data');}return $this->v->getValidated();}
}
