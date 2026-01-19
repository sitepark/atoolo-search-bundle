<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use DateTime;
use Solarium\QueryType\Update\Query\Document;

class IndexSchema2xDocument extends Document implements IndexDocument
{
    private const INHERITED_FIELDS = [
        'fields',
        'modifiers',
        'fieldBoosts',
    ];

    private const META_FIELDS = [
        'metaLong',
        'metaSingleLong',
        'metaInt',
        'metaSingleInt',
        'metaFloat',
        'metaSingleFloat',
        'metaString',
        'metaSingleString',
        'metaText',
        'metaSingleText',
        'metaBool',
        'metaSingleBool',
    ];

    public ?string $sp_id = null;
    public ?string $sp_name = null;
    public ?string $sp_anchor = null;
    public ?string $title = null;
    public ?string $sp_startletter = null;
    public ?string $description = null;
    public ?string $sp_objecttype = null;
    public ?bool $sp_canonical = null;
    public ?string $crawl_process_id = null;
    public ?string $id = null;
    public ?string $url = null;
    public ?string $contenttype = null;
    /**
     * @var string[]
     */
    public ?array $sp_contenttype = null;
    public ?string $sp_language = null;
    public ?string $meta_content_language = null;
    public ?string $meta_content_type = null;
    public ?DateTime $sp_changed = null;
    public ?DateTime $sp_generated = null;
    public ?DateTime $sp_date = null;
    public ?DateTime $sp_date_from = null;
    public ?DateTime $sp_date_to = null;
    /**
     * @var DateTime[]
     */
    public ?array $sp_date_list = null;
    public ?bool $sp_archive = null;
    public ?string $sp_title = null;
    public ?string $sp_sortvalue = null;
    /**
     * @var string[]
     */
    public ?array $keywords = null;
    public ?string $sp_boost_keywords = null;
    /**
     * @var string[]
     */
    public ?array $sp_site = null;
    /**
     * @var string[]
     */
    public ?array $sp_geo_points = null;
    /**
     * @var string[]
     */
    public ?array $sp_category = null;
    /**
     * @var string[]
     */
    public ?array $sp_category_path = null;
    public ?int $sp_group = null;
    /**
     * @var int[]
     */
    public ?array $sp_group_path = null;
    public ?string $content = null;
    /**
     * @var string[]
     */
    public ?array $include_groups = null;
    /**
     * @var string[]
     */
    public ?array $exclude_groups = null;
    /**
     * @var string[]
     */
    public ?array $sp_source = null;

    /**
     * @var string[]
     */
    public ?array $sp_citygov_phone = null;

    /**
     * @var string[]
     */
    public ?array $sp_citygov_email = null;

    public ?string $sp_citygov_address = null;

    public ?string $sp_citygov_startletter = null;

    /**
     * @var string[]
     */
    public ?array $sp_citygov_organisationtoken = null;

    public ?int $sp_organisation = null;

    public ?string $sp_citygov_firstname = null;

    public ?string $sp_citygov_lastname = null;

    /**
     * List of Organisation Ids
     * @var int[]
     */
    public ?array $sp_organisation_path = null;

    /**
     * List of Organisationnames
     * @var string[]
     */
    public ?array $sp_citygov_organisation = null;

    /**
     * List of Productnames
     * @var string[]
     */
    public ?array $sp_citygov_product = null;

    public ?string $sp_citygov_function = null;

    /**
     * @var array<string,int|int[]>
     */
    private array $metaInt = [];

    /**
     * @var array<string,int>
     */
    private array $metaSingleInt = [];

    /**
     * @var array<string,int|int[]>
     */
    private array $metaLong = [];

    /**
     * @var array<string,int>
     */
    private array $metaSingleLong = [];

    /**
     * @var array<string,float|float[]>
     */
    private array $metaFloat = [];

    /**
     * @var array<string,float>
     */
    private array $metaSingleFloat = [];

    /**
     * @var array<string,string|string[]>
     */
    private array $metaString = [];

    /**
     * @var array<string,string>
     */
    private array $metaSingleString = [];

    /**
     * @var array<string,string|string[]>
     */
    private array $metaText = [];

    /**
     * @var array<string,string>
     */
    private array $metaSingleText = [];

    /**
     * @var array<string,bool>
     */
    private array $metaBool = [];

    /**
     * Same as $metaBool. The underlying solr schema is redundant here as
     * `sp_meta_bool_*` and `sp_meta_singel_bool_*` have the same type definiton.
     * @var array<string,bool>
     */
    private array $metaSingleBool = [];

    /**
     * @param int|int[] $value
     */
    public function setMetaInt(string $name, int|array $value): void
    {
        $this->metaInt['sp_meta_int_' . $name] = $value;
    }

    public function setMetaSingleInt(string $name, int $value): void
    {
        $this->metaSingleInt['sp_meta_single_int_' . $name] = $value;
    }

    /**
     * @param int|int[] $value
     */
    public function setMetaLong(string $name, int|array $value): void
    {
        $this->metaLong['sp_meta_long_' . $name] = $value;
    }

    public function setMetaSingleLong(string $name, int $value): void
    {
        $this->metaSingleLong['sp_meta_single_long_' . $name] = $value;
    }

    /**
     * @param float|float[] $value
     */
    public function setMetaFloat(string $name, float|array $value): void
    {
        $this->metaFloat['sp_meta_float_' . $name] = $value;
    }

    public function setMetaSingleFloat(string $name, float $value): void
    {
        $this->metaSingleFloat['sp_meta_single_float_' . $name] = $value;
    }

    /**
     * @param string|string[] $value
     */
    public function setMetaString(string $name, string|array $value): void
    {
        $this->metaString['sp_meta_string_' . $name] = $value;
    }

    public function setMetaSingleString(string $name, string $value): void
    {
        $this->metaSingleString['sp_meta_single_string_' . $name] = $value;
    }

    /**
     * @param string|string[] $value
     */
    public function setMetaText(string $name, string|array $value): void
    {
        $this->metaText['sp_meta_text_' . $name] = $value;
    }

    public function setMetaSingleText(string $name, string $value): void
    {
        $this->metaSingleText['sp_meta_single_text_' . $name] = $value;
    }

    public function setMetaBool(string $name, bool $value): void
    {
        $this->metaBool['sp_meta_bool_' . $name] = $value;
    }

    public function setMetaSingleBool(string $name, bool $value): void
    {
        $this->metaSingleBool['sp_meta_single_bool_' . $name] = $value;
    }

    /**
     * @return array<String, mixed>
     */
    public function getFields(): array
    {
        return [
            ...parent::getFields(),
            ...array_filter(
                get_object_vars($this),
                fn($value, $key) => !is_null($value)
                    && !in_array($key, self::INHERITED_FIELDS, true)
                    && !in_array($key, self::META_FIELDS, true),
                ARRAY_FILTER_USE_BOTH,
            ),
            ...$this->metaLong,
            ...$this->metaSingleLong,
            ...$this->metaInt,
            ...$this->metaSingleInt,
            ...$this->metaFloat,
            ...$this->metaSingleFloat,
            ...$this->metaString,
            ...$this->metaSingleString,
            ...$this->metaText,
            ...$this->metaSingleText,
            ...$this->metaBool,
            ...$this->metaSingleBool,
        ];
    }
}
