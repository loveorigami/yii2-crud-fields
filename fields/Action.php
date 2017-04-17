<?php
/**
 */

namespace execut\crudFields\fields;


use kartik\grid\ActionColumn;
use yii\db\ActiveQuery;

class Action extends Field
{
    public function getField()
    {
        return false;
    }

    public function getColumn()
    {
        return [
            'class' => ActionColumn::class,
        ];
    }

    public function rules()
    {
        return false;
    }

    public function applyScopes(ActiveQuery $query)
    {
        return $query; // TODO: Change the autogenerated stub
    }
}