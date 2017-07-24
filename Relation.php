<?php
/**
 * Created by PhpStorm.
 * User: execut
 * Date: 6/28/17
 * Time: 10:48 AM
 */

namespace execut\crudFields;


use yii\base\Object;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

class Relation extends Object
{
    public $field = null;
    public $nameAttribute = 'name';
    public $valueAttribute = null;
    public $with = null;
    protected $_name = null;
    public function setName($relation) {
        $this->_name = $relation;

        return $this;
    }

    public function getName() {
        if ($this->_name === null) {
            $this->_name = $this->getRelationNameFromAttribute();
        }

        return $this->_name;
    }

    public function getWith() {
        if ($this->with === null) {
            return $this->getName();
        }

        return $this->with;
    }

    public function applyScopes(ActiveQuery $query)
    {
        if ($this->isManyToMany()) {
            $value = $this->field->value;
            if (!empty($value)) {
                $viaRelationQuery = $this->getViaRelationQuery();
                $viaRelationQuery->select(key($viaRelationQuery->link))
                    ->andWhere([
                        $this->field->attribute => $value,
                    ]);
                $viaRelationQuery->link = null;
                $viaRelationQuery->primaryModel = null;

                $query->andWhere([
                    'id' => $viaRelationQuery,
                ]);
            }
        }

        if ($this->getWith()) {
            $query->with($this->getWith());
        }

        return $query; // TODO: Change the autogenerated stub
    }

    protected function getRelationNameFromAttribute() {
        $attribute = $this->field->attribute;
        $relationName = lcfirst(Inflector::id2camel(str_replace('_id', '', $attribute), '_'));

        return $relationName;
    }

    /**
     * @return string
     */
    public function getSourceText(): string
    {
        $attribute = $this->field->attribute;
        $relationName = $this->name;
        $nameAttribute = $this->nameAttribute;
        $model = $this->field->model;
        $sourceInitText = '';
        if (!empty($model->$attribute)) {
//            if ($relationName === 'goodsArticle') {
//                var_dump($model->$relationName);
//                exit;
//            }
            $sourceInitText = $model->$relationName->$nameAttribute;
        }

        return $sourceInitText;
    }

    public function getRelationModelClass() {
        $modelClass = $this->getRelationQuery()->modelClass;

        return $modelClass;
    }

    public function getRelationFormName() {
        $relationModelClass = $this->getRelationModelClass();
        $model = new $relationModelClass;

        return $model->formName();
    }

    /**
     * @return array
     */
    public function getSourcesText(): array
    {
        $relationName = $this->name;
        $model = $this->field->model;
        $modelClass = $this->getRelationModelClass();

        if ($this->isManyToMany()) {
            $relationQuery = $this->getRelationQuery();

            $via = $relationQuery->via;
            $viaRelationName = $via[0];
            $viaModels = $this->field->model->$viaRelationName;
            $viaAttribute = $this->field->attribute;
            if (!empty($this->field->model->$viaAttribute)) {
                $sourceIds = $this->field->model->$viaAttribute;
            } else {
                $sourceIds = [];
                foreach ($viaModels as $viaModel) {
                    $sourceIds[] = $viaModel->$viaAttribute;
                }
            }
        } else {
            $attribute = $this->field->attribute;
            if (!empty($model->$attribute)) {
                $sourceIds = [];
                if (is_array($model->$attribute)) {
                    $sourceIds = $model->$attribute;
                } else {
                    $sourceIds[] = $model->$attribute;
                }
            }
        }

        $nameAttribute = $this->nameAttribute;
        $sourceInitText = [];
        if (!empty($sourceIds)) {
            $models = $modelClass::find()->andWhere(['id' => $sourceIds])->all();

            $sourceInitText = ArrayHelper::map($models, 'id', $nameAttribute);
        }

        return $sourceInitText;
    }

    /**
     * @param $relationName
     * @param $model
     * @param $nameAttribute
     * @return array
     */
    public function getData(): array
    {
        $data = ['' => ''];

        $class = $this->getRelationModelClass();
        $relationQuery = $class::find();

        $data = ArrayHelper::merge($data, ArrayHelper::map($relationQuery->all(), 'id', $this->nameAttribute));
        return $data;
    }

    public function getColumnValue() {
        if ($this->isManyToMany()) {
            return function ($model) {
                $name = $this->getName();
                $nameAttribute = $this->nameAttribute;
                $result = [];
                foreach ($model->$name as $value) {
                    $result[] = $value->$nameAttribute;
                }

                return implode(', ', $result);
            };
        } else {
            if ($this->valueAttribute !== null) {
                return $this->valueAttribute;
            }

            return $this->name . '.' . $this->nameAttribute;
        }
    }

    protected function isManyToMany() {
        $relationQuery = $this->getRelationQuery();

        return $relationQuery->multiple;
    }

    /**
     * @return mixed
     */
    public function getRelationQuery()
    {
        $relationQuery = $this->field->model->getRelation($this->getName());

        return $relationQuery;
    }

    /**
     * @return mixed
     */
    public function getViaRelation()
    {
        $relationQuery = $this->getRelationQuery();

        $via = $relationQuery->via;
        $viaRelation = $via[0];
        return $viaRelation;
    }

    /**
     * @return mixed
     */
    public function getViaRelationQuery()
    {
        $viaRelation = $this->getViaRelation();
        $viaRelationQuery = $this->field->model->getRelation($viaRelation);
        return $viaRelationQuery;
    }

    public function getViaFromAttribute() {
        return key($this->getViaRelationQuery()->link);
    }

    public function getViaToAttribute() {
        return current($this->getRelationQuery()->link);
    }
}