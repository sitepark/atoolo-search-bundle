<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use DateTime;
use DateTimeInterface;
use Solarium\QueryType\Update\Query\Document;

class IndexSchema2xDocument extends Document implements IndexDocument
{
    public string $sp_id;
    public ?string $sp_name;
    public ?string $sp_anchor;
    public ?string $title;
    public ?string $description;
    public ?string $sp_objecttype;
    public bool $sp_canonical;
    public ?string $crawl_process_id;
    public ?string $id;
    public ?string $url;
    /**
     * @var string[]
     */
    public array $sp_contenttype;
    public ?string $sp_language;
    public ?string $meta_content_language;
    public ?string $meta_content_type;
    public ?DateTime $sp_changed;
    public ?DateTime $sp_generated;
    public ?DateTime $sp_date;
    /**
     * @var DateTime[]
     */
    public array $sp_date_list;
    public bool $sp_archive;
    public ?string $sp_title;
    public ?string $sp_sortvalue;
    /**
     * @var string[]
     */
    public array $keywords;
    public string $sp_boost_keywords;
    /**
     * @var string[]
     */
    public array $sp_site;
    /**
     * @var string[]
     */
    public array $sp_geo_points;
    /**
     * @var string[]
     */
    public array $sp_category;
    /**
     * @var string[]
     */
    public array $sp_category_path;
    public int $sp_group;
    /**
     * @var int[]
     */
    public array $sp_group_path;
    public ?string $content;
    /**
     * @var string[]
     */
    public array $include_groups;
    /**
     * @var string[]
     */
    public array $exclude_groups;
    /**
     * @var string[]
     */
    public array $sp_source;

    /**
     * @var string[]
     */
    public array $sp_citygov_phone;

    /**
     * @var string[]
     */
    public array $sp_citygov_email;

    public ?string $sp_citygov_address;

    public ?string $sp_citygov_startletter;

    /**
     * @var string[]
     */
    public array $sp_citygov_organisationtoken;

    public int $sp_organisation;

    public ?string $sp_citygov_firstname;

    public ?string $sp_citygov_lastname;

    /**
     * List of Organisation Id's
     * @var int[]
     */
    public array $sp_organisation_path;

    /**
     * List of Organisationnames
     * @var string[]
     */
    public array $sp_citygov_organisation;

    /**
     * List of Productnames
     * @var string[]
     */
    public array $sp_citygov_product;

    public ?string $sp_citygov_function;
    /**
     * @var array<string,string>
     */
    private array $metaString = [];

    public function setMetaString(string $name, string $value): void
    {
        $this->metaString[$name] = $value;
    }

    public function getFields(): array
    {
        $fields = get_object_vars($this);
        $fields = array_filter($fields, function ($value, $key) {
            if (is_null($value)) {
                return false;
            }
            if ($key === 'metaString') {
                return false;
            }
            return true;
        }, ARRAY_FILTER_USE_BOTH);
        foreach ($this->metaString as $key => $value) {
            $fields['sp_meta_string_' . $key] = $value;
        }

        $fields = array_map(function ($value) {
            if ($value instanceof DateTime) {
                return $value->format(DateTimeInterface::ATOM);
            }
            return $value;
        }, $fields);

        return $fields;
    }
}