<?php
namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/** Media library validation. @agent-validator: Media library @agent-pattern: Validation first @agent-reusable: MEDIUM */
class ProductMediaValidator
{
    protected Validation $v;
    public function __construct(?Validation $validation=null){$this->v=$validation??Services::validation(null,false);}

    /** Validate library filters. @agent-use: GET /api/products/media/library @agent-pattern: Standard list validation */
    public function validateLibraryFilters(array $input): array
    {
        $v=$this->run(array_merge(['limit'=>20,'offset'=>0,'entity_id'=>null],$input),[
            'limit'=>'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[200]',
            'offset'=>'permit_empty|integer|greater_than_equal_to[0]',
            'entity_id'=>'permit_empty|integer|greater_than[0]',
        ]);
        return ['limit'=>(int)($v['limit']??20),'offset'=>(int)($v['offset']??0),'entity_id'=>isset($v['entity_id'])?(int)$v['entity_id']:null];
    }

    protected function run(array $data,array $rules): array
    {if(!$this->v->setRules($rules)->run($data)){throw new InvalidArgumentException(implode('; ',array_filter($this->v->getErrors()))?:'Invalid data');}return $this->v->getValidated();}
}
