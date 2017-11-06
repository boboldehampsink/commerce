<?php

namespace craft\commerce\services;

use Craft;
use craft\commerce\models\TaxCategory;
use craft\commerce\records\TaxCategory as TaxCategoryRecord;
use craft\db\Query;
use yii\base\Component;
use yii\base\Exception;

/**
 * Tax category service.
 *
 * @property TaxCategory[]|array $allTaxCategories
 * @property TaxCategory|null    $defaultTaxCategory
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  2.0
 */
class TaxCategories extends Component
{
    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    private $_fetchedAllTaxCategories = false;

    /**
     * @var TaxCategory[]
     */
    private $_taxCategoriesById = [];

    /**
     * @var TaxCategory[]
     */
    private $_taxCategoriesByHandle = [];

    /**
     * @var TaxCategory
     */
    private $_defaultTaxCategory;

    // Public Methods
    // =========================================================================

    /**
     * Returns all Tax Categories
     *
     * @return TaxCategory[]
     */
    public function getAllTaxCategories(): array
    {
        if (!$this->_fetchedAllTaxCategories) {
            $results = $this->_createTaxCategoryQuery()->all();

            foreach ($results as $result) {
                $this->_memoizeTaxCategory(new TaxCategory($result));
            }

            $this->_fetchedAllTaxCategories = true;
        }

        return $this->_taxCategoriesById;
    }

    /**
     * @param int $taxCategoryId
     *
     * @return TaxCategory|null
     */
    public function getTaxCategoryById($taxCategoryId)
    {
        if (isset($this->_taxCategoriesById[$taxCategoryId])) {
            return $this->_taxCategoriesById[$taxCategoryId];
        }

        if ($this->_fetchedAllTaxCategories) {
            return null;
        }

        $result = $this->_createTaxCategoryQuery()
            ->where(['id' => $taxCategoryId])
            ->one();

        if (!$result) {
            return null;
        }

        $this->_memoizeTaxCategory(new TaxCategory($result));

        return $this->_taxCategoriesById[$taxCategoryId];
    }

    /**
     * @param int $taxCategoryHandle
     *
     * @return TaxCategory|null
     */
    public function getTaxCategoryByHandle($taxCategoryHandle)
    {
        if (isset($this->_taxCategoriesByHandle[$taxCategoryHandle])) {
            return $this->_taxCategoriesByHandle[$taxCategoryHandle];
        }

        if ($this->_fetchedAllTaxCategories) {
            return null;
        }

        $result = $this->_createTaxCategoryQuery()
            ->where(['handle' => $taxCategoryHandle])
            ->one();

        if (!$result) {
            return null;
        }

        $taxCategory = new TaxCategory($result);
        $this->_memoizeTaxCategory($taxCategory);

        return $this->_taxCategoriesByHandle[$taxCategoryHandle];
    }

    /**
     * Get the default tax category
     *
     * @return TaxCategory|null
     */
    public function getDefaultTaxCategory()
    {
        if (null === $this->_defaultTaxCategory) {
            $row = $this->_createTaxCategoryQuery()
                ->where(['default' => 1])
                ->one();

            if (!$row) {
                return null;
            }

            $this->_defaultTaxCategory = new TaxCategory($row);
        }

        return $this->_defaultTaxCategory;
    }

    /**
     * @param TaxCategory $model
     *
     * @return bool
     * @throws Exception
     * @throws \Exception
     */
    public function saveTaxCategory(TaxCategory $model): bool
    {
        $oldHandle = null;

        if ($model->id) {
            $record = TaxCategoryRecord::findOne($model->id);

            if (!$record) {
                throw new Exception(Craft::t('commerce', 'No tax category exists with the ID “{id}”',
                    ['id' => $model->id]));
            }

            $oldHandle = $record->handle;
        } else {
            $record = new TaxCategoryRecord();
        }

        $record->name = $model->name;
        $record->handle = $model->handle;
        $record->description = $model->description;
        $record->default = $model->default;

        $record->validate();
        $model->addErrors($record->getErrors());

        if (!$model->hasErrors()) {
            // If this was the default make all others not the default.
            if ($model->default) {
                TaxCategoryRecord::updateAll(['default' => 0]);
            }

            // Save it!
            $record->save(false);

            // Now that we have a record ID, save it on the model
            $model->id = $record->id;

            // Update Service cache
            $this->_memoizeTaxCategory($model);

            if (null !== $oldHandle && $model->handle != $oldHandle) {
                unset($this->_taxCategoriesByHandle[$oldHandle]);
            }

            return true;
        }

        return false;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function deleteTaxCategoryById($id): bool
    {
        $all = $this->getAllTaxCategories();

        // Not the last one.
        if (count($all) === 1) {
            return false;
        }

        $record = TaxCategoryRecord::findOne($id);

        if ($record) {
            return (bool)$record->delete();
        }

        return false;
    }

    /**
     * @param $productTypeId
     *
     * @return array
     */
    public function getTaxCategoriesByProductTypeId($productTypeId): array
    {
        $rows = $this->_createTaxCategoryQuery()
            ->innerJoin('{{%commerce_producttypes_taxcategories}} productTypeTaxCategories', '[[taxCategories.id]] = [[productTypeTaxCategories.taxCategoryId]]')
            ->innerJoin('{{%commerce_producttypes}} productTypes', '[[productTypeTaxCategories.productTypeId]] = [[productTypes.id]]')
            ->where(['productTypes.id' => $productTypeId])
            ->all();

        if (empty($rows)) {
            $category = $this->getDefaultTaxCategory();

            if (!$category) {
                return [];
            }

            $taxCategory = $this->getDefaultTaxCategory();

            return [$taxCategory->id => $taxCategory];
        }

        $taxCategories = [];

        foreach ($rows as $row) {
            $key = $row['id'];
            $taxCategories[$key] = new TaxCategory($row);
        }

        return $taxCategories;
    }

    // Private methods
    // =========================================================================

    /**
     * Memoize a tax category model by its ID and handle.
     *
     * @param TaxCategory $taxCategory
     *
     * @return void
     */
    private function _memoizeTaxCategory(TaxCategory $taxCategory)
    {
        $this->_taxCategoriesById[$taxCategory->id] = $taxCategory;
        $this->_taxCategoriesByHandle[$taxCategory->handle] = $taxCategory;
    }

    /**
     * Returns a Query object prepped for retrieving tax categories.
     *
     * @return Query
     */
    private function _createTaxCategoryQuery(): Query
    {
        return (new Query())
            ->select([
                'taxCategories.id',
                'taxCategories.name',
                'taxCategories.handle',
                'taxCategories.description',
                'taxCategories.default'
            ])
            ->from(['{{%commerce_taxcategories}} taxCategories']);
    }
}
