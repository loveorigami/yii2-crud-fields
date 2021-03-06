<?php
/**
 */

namespace execut\crudFields\fields;


use execut\crudFields\Relation;
use unclead\multipleinput\MultipleInputColumn;
use yii\base\BaseObject;
use yii\base\Exception;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;

class Field extends BaseObject
{
    const SCENARIO_GRID = 'grid';
    const SCENARIO_FORM = 'form';
    public $module = null;
    /**
     * @var ActiveRecord
     */
    public $model = null;
    public $required = false;
    public $defaultValue = null;
    public $attribute = null;
    public $rules = [];
    public $multipleInputType = MultipleInputColumn::TYPE_TEXT_INPUT;
    protected $_column = [];
    protected $_field = [];
    protected $_label = null;
    public $displayOnly = false;
    public $isRenderRelationFields = false;
    public $isRenderInRelationForm = true;

    public $nameAttribute = 'name';
    public $with = null;
    public $relation = null;
    public $data = [];
    public $valueAttribute = null;
    public $multipleInputField = [];
    public $url = null;
    public $isNoRenderRelationLink = false;

    /**
     * @var \Closure|null
     */
    public $scope = null;

    protected $_relationObject = null;
    public $order = 0;

    public function attach() {
        if ($this->defaultValue !== null) {
            $attribute = $this->attribute;
            $this->model->$attribute = $this->defaultValue;
        }
    }

    public function setRelationObject($object) {
        $this->_relationObject = $object;

        return $this;
    }

    public function getRelationObject() {
        if ($this->_relationObject === null && $this->relation !== null) {
            $this->_relationObject = new Relation([
                'field' => $this,
                'name' => $this->relation,
                'nameAttribute' => $this->nameAttribute,
                'with' => $this->with,
                'valueAttribute' => $this->valueAttribute,
            ]);
        }

        return $this->_relationObject;
    }

    public function getValue() {
        $attribute = $this->attribute;

        return $this->model->$attribute;
    }

    public function getData() {
        if (empty($this->data)) {
            $relationObject = $this->getRelationObject();
            if (!$relationObject) {
                throw new Exception('Data is required or set relation name');
            }

            return $relationObject->getData();
        }

        return $this->data;
    }

    public function getColumn() {
        $column = $this->_column;
        if ($column === false) {
            return false;
        }

        if (is_callable($column)) {
            $column = $column();
        }

        if ($this->attribute !== null) {
            $column['attribute'] = $this->attribute;
        }

        return $column;
    }

    public function setColumn($column) {
        $this->_column = $column;

        return $this;
    }

    public function getField() {
        $field = $this->_field;
        if (is_callable($field)) {
            $field = $field($this->model, $this);
        }

        if ($field === false) {
            return false;
        }

        if ($this->model !== null) {
            $field['viewModel'] = $this->model;
            $field['editModel'] = $this->model;
        }

        if ($this->attribute !== null) {
            $field['attribute'] = $this->attribute;
        }

        $displayOnly = $this->getDisplayOnly();
        if ($displayOnly) {
            $field['displayOnly'] = true;
        }

        return $field;
    }

    public function getDisplayOnly() {
        if ($this->displayOnly) {
            if (is_callable($this->displayOnly)) {
                return call_user_func($this->displayOnly);
            } else {
                return true;
            }
        }
    }

    public function getFields($isWithRelationsFields = true) {
        $fields = [];
        if ($this->getIsRenderRelationFields() && $isWithRelationsFields) {
            $relationObject = $this->getRelationObject();
            $relationFields = $relationObject->getRelationFields();
            foreach ($relationFields as $field) {
                $formFields = $field->getFields(false);
                foreach ($formFields as &$formField) {
                    if (empty($formField['valueColOptions'])) {
                        $formField['valueColOptions'] = [];
                    }

                    Html::addCssClass($formField['valueColOptions'], 'related-' . $relationObject->getName());
//                    if (!empty($formField['attribute'])) {
//                        ArrayHelper::setValue($formField, 'options.name', $this->model->formName() . '[' . $relationObject->getName() . '][' . $formField['attribute'] . ']');
//                    }
                }

                $fields = ArrayHelper::merge($formFields, $fields);
            }
        } else {
            $fields = [$this->attribute => $this->getField()];
        }

        return $fields;
    }

    public function getColumns() {
        $column = $this->getColumn();
        if ($column === false) {
            return [];
        }

        return [$this->attribute => $column];
    }

    public function getMultipleInputField() {
        if ($this->multipleInputField === false) {
            return false;
        }

        return ArrayHelper::merge([
            'name' => $this->attribute,
            'type' => $this->multipleInputType,
            'enableError' => true,
            'options' => [
                'placeholder' => $this->getLabel(),
            ],
        ], $this->multipleInputField);
    }

    public function setField($field) {
        $this->_field = $field;

        return $this;
    }

    public function applyScopes(ActiveQuery $query) {
        $attribute = $this->attribute;
        $scopeResult = true;
        if ($this->scope !== null) {
            $scope = $this->scope;
            $scopeResult = $scope($query, $this->model);
        }

        if ($scopeResult && $this->attribute) {
            $value = $this->getValue();
            if (!empty($value) || $value === '0') {
                $query->andFilterWhere([
                    $attribute => $value,
                ]);
            }
        }

        $this->applyRelationScopes($query);

        return $query;
    }

    public function getIsRenderRelationFields() {
        if (is_callable($this->isRenderRelationFields)) {
            $isRenderRelationFields = $this->isRenderRelationFields;

            return $isRenderRelationFields($this);
        }

        return $this->isRenderRelationFields;
    }

    public function rules() {
        $rules = [];
        if ($this->attribute !== null) {
            $rules[] = [
                [$this->attribute],
                'safe',
                'on' => self::SCENARIO_GRID,
            ];

            if (!$this->getIsRenderRelationFields() && !$this->getDisplayOnly()) {
                if ($this->required) {
                    $rule = 'required';
                } else {
                    $rule = 'safe';
                }

                $rules[] = [
                    [$this->attribute],
                    $rule,
                    'on' => [self::SCENARIO_FORM, 'default'],
                ];
            }
        }

        return ArrayHelper::merge($rules, $this->rules);
    }

    public function setLabel($label) {
        $this->_label = $label;

        return $this;
    }

    public function getLabel() {
        if ($this->_label !== null) {
            return $this->_label;
        }

        $attribute = Inflector::humanize($this->attribute, '_');
        if ($this->module === null) {
            return $attribute;
        }

        return \Yii::t('execut/' . $this->module, $attribute);
    }

    /**
     * @param ActiveQuery $query
     */
    protected function applyRelationScopes(ActiveQuery $query)
    {
        if ($this->relation) {
            return $this->getRelationObject()->applyScopes($query);
        }
    }

    public function getFormBuilderFields() {
        return [];
    }
}