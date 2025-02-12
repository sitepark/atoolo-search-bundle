<?php

namespace Atoolo\Search\Serializer\Search\Query;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Search\Query\Boosting;
use Atoolo\Search\Dto\Search\Query\Facet\Facet;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\GeoPoint;
use Atoolo\Search\Dto\Search\Query\QueryOperator;
use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Dto\Search\Query\SearchQueryBuilder;
use Atoolo\Search\Dto\Search\Query\Sort\Criteria;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class SearchQueryDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    /**
     * @param array<mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (!is_array($data)) {
            throw new NotNormalizableValueException(\sprintf('Failed to denormalize data into class "%s": Array expected, %s given', $type, gettype($data)));
        }
        $builder = new SearchQueryBuilder();
        if (isset($data['text'])) {
            $builder->text($data['text']);
        }
        if (isset($data['lang'])) {
            // support both ResourceLanguage::of and constructor
            if (is_string($data['lang'])) {
                $builder->lang(ResourceLanguage::of($data['lang']));
            } else {
                // @phpstan-ignore argument.type
                $builder->lang($this->denormalizer->denormalize($data['lang'], ResourceLanguage::class));
            }
        }
        if (isset($data['offset'])) {
            $builder->offset($data['offset']);
        }
        if (isset($data['limit'])) {
            $builder->limit($data['limit']);
        }
        if (isset($data['sort'])) {
            // @phpstan-ignore argument.type, argument.unpackNonIterable
            $builder->sort(...$this->denormalizer->denormalize($data['sort'], Criteria::class . '[]'));
        }
        if (isset($data['filter'])) {
            // @phpstan-ignore argument.type, argument.unpackNonIterable
            $builder->filter(...$this->denormalizer->denormalize($data['filter'], Filter::class . '[]'));
        }
        if (isset($data['facets'])) {
            // @phpstan-ignore argument.type, argument.unpackNonIterable
            $builder->facet(...$this->denormalizer->denormalize($data['facets'], Facet::class . '[]'));
        }
        if (isset($data['archive'])) {
            $builder->archive($data['archive']);
        }
        if (isset($data['defaultQueryOperator'])) {
            $builder->defaultQueryOperator(QueryOperator::from($data['defaultQueryOperator']));
        }
        if (isset($data['timeZone'])) {
            $builder->timeZone(new \DateTimeZone($data['timeZone']));
        }
        if (isset($data['boosting'])) {
            // @phpstan-ignore argument.type
            $builder->boosting($this->denormalizer->denormalize($data['boosting'], Boosting::class));
        }
        if (isset($data['distanceReferencePoint'])) {
            $builder->distanceReferencePoint(
                // @phpstan-ignore argument.type
                $this->denormalizer->denormalize($data['distanceReferencePoint'], GeoPoint::class),
            );
        }
        if (isset($data['explain'])) {
            $builder->explain($data['explain']);
        }
        return $builder->build();
    }

    /**
     * @param array<mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === SearchQuery::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            SearchQuery::class => true,
        ];
    }
}
