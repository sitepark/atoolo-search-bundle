<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

/**
 * Schema 3.x is schema 2.x plus nested documents (one child document per
 * event date). All field mappings are the same, so this class extends
 * Schema2xFieldMapper on purpose and only adds the nested document support.
 * Classes that still expect a Schema2xFieldMapper (e.g. in other bundles)
 * keep working with it. If a mapping of 3.x differs from 2.x one day,
 * override it here.
 */
class Schema3xFieldMapper extends Schema2xFieldMapper
{
    public function hasNestedDocuments(): bool
    {
        return true;
    }

    public function getNestedDocumentsField(): string
    {
        return '_nest_parent_';
    }
}
