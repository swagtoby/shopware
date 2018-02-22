<?php declare(strict_types=1);

namespace Shopware\Api\Entity\Search\Parser;

use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use Shopware\Api\Entity\Dbal\EntityDefinitionQueryHelper;
use Shopware\Api\Entity\EntityDefinition;
use Shopware\Api\Entity\Field\ArrayField;
use Shopware\Api\Entity\Field\FkField;
use Shopware\Api\Entity\Field\IdField;
use Shopware\Api\Entity\Search\Query\MatchQuery;
use Shopware\Api\Entity\Search\Query\NestedQuery;
use Shopware\Api\Entity\Search\Query\NotQuery;
use Shopware\Api\Entity\Search\Query\Query;
use Shopware\Api\Entity\Search\Query\RangeQuery;
use Shopware\Api\Entity\Search\Query\ScoreQuery;
use Shopware\Api\Entity\Search\Query\TermQuery;
use Shopware\Api\Entity\Search\Query\TermsQuery;
use Shopware\Context\Struct\ShopContext;

class SqlQueryParser
{
    public static function parseRanking(array $queries, string $definition, string $root, ShopContext $context): ParseResult
    {
        $result = new ParseResult();

        /** @var ScoreQuery $query */
        foreach ($queries as $query) {
            $parsed = self::parse($query->getQuery(), $definition, $context, $root);

            foreach ($parsed->getWheres() as $where) {
                if ($query->getScoreField()) {
                    $field = EntityDefinitionQueryHelper::getFieldAccessor(
                        $query->getScoreField(),
                        $definition,
                        $root,
                        $context
                    );

                    $result->addWhere(
                        sprintf('IF(%s , %s * %s, 0)', $where, $query->getScore(), $field)
                    );
                    continue;
                }

                $result->addWhere(
                    sprintf('IF(%s , %s, 0)', $where, $query->getScore())
                );
            }

            foreach ($parsed->getParameters() as $key => $parameter) {
                $result->addParameter($key, $parameter, $parsed->getType($key));
            }
        }

        return $result;
    }

    public static function parse(Query $query, string $definition, ShopContext $context, string $root = null): ParseResult
    {
        if ($root === null) {
            /** @var EntityDefinition $definition */
            $root = $definition::getEntityName();
        }

        switch (true) {
            case $query instanceof NotQuery:
                return self::parseNotQuery($query, $definition, $root, $context);
            case $query instanceof NestedQuery:
                return self::parseNestedQuery($query, $definition, $root, $context);
            case $query instanceof TermQuery:
                return self::parseTermQuery($query, $definition, $root, $context);
            case $query instanceof TermsQuery:
                return self::parseTermsQuery($query, $definition, $root, $context);
            case $query instanceof MatchQuery:
                return self::parseMatchQuery($query, $definition, $root, $context);
            case $query instanceof RangeQuery:
                return self::parseRangeQuery($query, $definition, $root, $context);
            default:
                throw new \RuntimeException(sprintf('Unsupported query %s', get_class($query)));
        }
    }

    private static function parseRangeQuery(RangeQuery $query, string $definition, string $root, ShopContext $context): ParseResult
    {
        $result = new ParseResult();

        $key = self::getKey();

        $field = EntityDefinitionQueryHelper::getFieldAccessor($query->getField(), $definition, $root, $context);

        $where = [];

        if ($query->hasParameter(RangeQuery::GT)) {
            $where[] = $field . ' > :' . $key;
            $result->addParameter($key, $query->getParameter(RangeQuery::GT));
        } elseif ($query->hasParameter(RangeQuery::GTE)) {
            $where[] = $field . ' >= :' . $key;
            $result->addParameter($key, $query->getParameter(RangeQuery::GTE));
        }

        $key = self::getKey();

        if ($query->hasParameter(RangeQuery::LT)) {
            $where[] = $field . ' < :' . $key;
            $result->addParameter($key, $query->getParameter(RangeQuery::LT));
        } elseif ($query->hasParameter(RangeQuery::LTE)) {
            $where[] = $field . ' <= :' . $key;
            $result->addParameter($key, $query->getParameter(RangeQuery::LTE));
        }

        $where = '(' . implode(' AND ', $where) . ')';
        $result->addWhere($where);

        return $result;
    }

    private static function parseMatchQuery(MatchQuery $query, string $definition, string $root, ShopContext $context): ParseResult
    {
        $key = self::getKey();

        $field = EntityDefinitionQueryHelper::getFieldAccessor($query->getField(), $definition, $root, $context);

        $result = new ParseResult();
        $result->addWhere($field . ' LIKE :' . $key);
        $result->addParameter($key, '%' . $query->getValue() . '%');

        return $result;
    }

    private static function parseTermsQuery(TermsQuery $query, string $definition, string $root, ShopContext $context): ParseResult
    {
        $key = self::getKey();
        $select = EntityDefinitionQueryHelper::getFieldAccessor($query->getField(), $definition, $root, $context);
        $field = EntityDefinitionQueryHelper::getField($query->getField(), $definition, $root);

        $result = new ParseResult();

        if ($field instanceof ArrayField) {
            $result->addWhere('JSON_CONTAINS(' . $select . ', JSON_ARRAY(:' . $key . '))');
            $result->addParameter($key, $query->getValue());

            return $result;
        }

        $result->addWhere($select . ' IN (:' . $key . ')');

        $value = array_values($query->getValue());
        if ($field instanceof IdField || $field instanceof FkField) {
            $value = array_map(function (string $id) {
                return Uuid::fromString($id)->getBytes();
            }, $value);
        }
        $result->addParameter($key, $value, Connection::PARAM_STR_ARRAY);

        return $result;
    }

    private static function parseTermQuery(TermQuery $query, string $definition, string $root, ShopContext $context): ParseResult
    {
        $key = self::getKey();
        $select = EntityDefinitionQueryHelper::getFieldAccessor($query->getField(), $definition, $root, $context);
        $field = EntityDefinitionQueryHelper::getField($query->getField(), $definition, $root);

        $result = new ParseResult();

        if ($field instanceof ArrayField) {
            $result->addWhere('JSON_CONTAINS(' . $select . ', JSON_ARRAY(:' . $key . '))');
            $result->addParameter($key, $query->getValue());

            return $result;
        }

        if ($query->getValue() === null) {
            $result->addWhere($select . ' IS NULL');

            return $result;
        }
        $result->addWhere($select . ' = :' . $key);

        $value = $query->getValue();
        if ($field instanceof IdField || $field instanceof FkField) {
            $value = Uuid::fromString($value)->getBytes();
        }

        $result->addParameter($key, $value);

        return $result;
    }

    private static function parseNestedQuery(NestedQuery $query, string $definition, string $root, ShopContext $context): ParseResult
    {
        $result = self::iterateNested($query, $definition, $root, $context);

        $wheres = $result->getWheres();

        $result->resetWheres();

        $glue = ' ' . $query->getOperator() . ' ';
        if (!empty($wheres)) {
            $result->addWhere('(' . implode($glue, $wheres) . ')');
        }

        return $result;
    }

    private static function parseNotQuery(NotQuery $query, string $definition, string $root, ShopContext $context): ParseResult
    {
        $result = self::iterateNested($query, $definition, $root, $context);

        $wheres = $result->getWheres();

        $result->resetWheres();

        $glue = ' ' . $query->getOperator() . ' ';
        if (!empty($wheres)) {
            $result->addWhere('NOT (' . implode($glue, $wheres) . ')');
        }

        return $result;
    }

    private static function iterateNested(NestedQuery $query, string $definition, string $root, ShopContext $context): ParseResult
    {
        $result = new ParseResult();
        foreach ($query->getQueries() as $nestedQuery) {
            $result = $result->merge(
                self::parse($nestedQuery, $definition, $context, $root)
            );
        }

        return $result;
    }

    private static function getKey(): string
    {
        return 'param_' . str_replace('-', '', Uuid::uuid4()->toString());
    }
}