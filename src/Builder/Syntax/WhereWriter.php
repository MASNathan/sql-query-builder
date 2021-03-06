<?php

namespace NilPortugues\Sql\QueryBuilder\Builder\Syntax;

use NilPortugues\Sql\QueryBuilder\Manipulation\Select;
use NilPortugues\Sql\QueryBuilder\Syntax\Column;
use NilPortugues\Sql\QueryBuilder\Syntax\SyntaxFactory;
use NilPortugues\Sql\QueryBuilder\Syntax\Where;

/**
 * Class WhereWriter
 * @package NilPortugues\Sql\QueryBuilder\BuilderInterface\Syntax
 */
class WhereWriter extends AbstractBaseWriter
{
    /**
     * @var array
     */
    public $matchMode = array(
        'natural'         => "(MATCH({{columnNames}}) AGAINST({{columnValues}}))",
        'boolean'         => "(MATCH({{columnNames}}) AGAINST({{columnValues}} IN BOOLEAN MODE))",
        'query_expansion' => "(MATCH({{columnNames}}) AGAINST({{columnValues}} WITH QUERY EXPANSION))"
    );

    /**
     * @param Where $where
     *
     * @return string
     */
    public function writeWhere(Where $where)
    {
        $clauses = $this->writeWhereClauses($where);
        $clauses = array_filter($clauses);

        if (empty($clauses)) {
            return '';
        }

        return implode($this->writer->writeConjunction($where->getConjunction()), $clauses);
    }

    /**
     * @param Where $where
     *
     * @return array
     */
    public function writeWhereClauses(Where $where)
    {
        $whereArray = array();

        $this->writeWhereMatches($where, $whereArray);
        $this->writeWhereIns($where, $whereArray);
        $this->writeWhereNotIns($where, $whereArray);
        $this->writeWhereBetweens($where, $whereArray);
        $this->writeWhereComparisons($where, $whereArray);
        $this->writeWhereIsNulls($where, $whereArray);
        $this->writeWhereIsNotNulls($where, $whereArray);
        $this->writeWhereBooleans($where, $whereArray);
        $this->writeExists($where, $whereArray);
        $this->writeNotExists($where, $whereArray);
        $this->writeSubWheres($where, $whereArray);

        return $whereArray;
    }

    /**
     * @param Where $where
     * @param array $whereArray
     *
     * @return array
     */
    public function writeWhereMatches(Where $where, array &$whereArray)
    {
        $matches = array();

        foreach ($where->getMatches() as $values) {
            $columns = SyntaxFactory::createColumns($values['columns'], $where->getTable());
            $columnNames = $this->getColumnNames($columns);

            $columnValues = array(implode(" ", $values['values']));
            $columnValues = implode(", ", $this->writer->writeValues($columnValues));

            $matches[] = str_replace(
                array('{{columnNames}}', '{{columnValues}}'),
                array($columnNames, $columnValues),
                $this->matchMode[$values['mode']]
            );
        }

        $whereArray = array_merge($whereArray, $matches);
    }

    /**
     * @param $columns
     *
     * @return string
     */
    public function getColumnNames($columns)
    {
        $columnNames = array();
        foreach ($columns as &$column) {
            $columnNames[] = $this->columnWriter->writeColumn($column);
        }

        return implode(', ', $columnNames);
    }

    /**
     * @param Where $where
     * @param array $whereArray
     *
     * @return array
     */
    public function writeWhereIns(Where $where, array &$whereArray)
    {
        $whereArray = array_merge(
            $whereArray,
            $this->writeWhereIn($where, 'getIns', 'IN')
        );
    }

    /**
     * @param Where  $where
     * @param string $method
     * @param string $operation
     *
     * @return array
     */
    public function writeWhereIn(Where $where, $method, $operation)
    {
        $collection = array();

        foreach ($where->$method() as $column => $values) {
            $newColumn = array($column);
            $column    = SyntaxFactory::createColumn($newColumn, $where->getTable());
            $column    = $this->columnWriter->writeColumn($column);

            $values = $this->writer->writeValues($values);
            $values = implode(", ", $values);

            $collection[] = "({$column} $operation ({$values}))";
        }

        return $collection;
    }

    /**
     * @param Where $where
     * @param array $whereArray
     *
     * @return array
     */
    public function writeWhereNotIns(Where $where, array &$whereArray)
    {
        $whereArray = array_merge(
            $whereArray,
            $this->writeWhereIn($where, 'getNotIns', 'NOT IN')
        );
    }

    /**
     * @param Where $where
     * @param array $whereArray
     *
     * @return array
     */
    public function writeWhereBetweens(Where $where, array &$whereArray)
    {
        $between = $where->getBetweens();
        $me = $this;
        array_walk(
            $between,
            function (&$between) use ($me) {

                $between = "("
                    . $me->columnWriter->writeColumn($between["subject"])
                    . " BETWEEN "
                    . $me->writer->writePlaceholderValue($between["a"])
                    . " AND "
                    . $me->writer->writePlaceholderValue($between["b"])
                    . ")";
            }
        );

        $whereArray = array_merge($whereArray, $between);
    }

    /**
     * @param Where $where
     * @param array $whereArray
     *
     * @return array
     */
    public function writeWhereComparisons(Where $where, array &$whereArray)
    {
        $comparisons = $where->getComparisons();
        $me = $this;
        array_walk(
            $comparisons,
            function (&$comparison) use ($me) {

                $str = $me->writeWherePartialCondition($comparison["subject"]);
                $str .= $me->writer->writeConjunction($comparison["conjunction"]);
                $str .= $me->writeWherePartialCondition($comparison["target"]);

                $comparison = "($str)";
            }
        );

        $whereArray = array_merge($whereArray, $comparisons);
    }

    /**
     * @param $subject
     *
     * @return string
     */
    public function writeWherePartialCondition(&$subject)
    {
        if ($subject instanceof Column) {
            $str = $this->columnWriter->writeColumn($subject);
        } elseif ($subject instanceof Select) {
            $selectWriter = WriterFactory::createSelectWriter($this->writer, $this->placeholderWriter);
            $str          = '(' . $selectWriter->write($subject) . ')';
        } else {
            $str = $this->writer->writePlaceholderValue($subject);
        }

        return $str;
    }

    /**
     * @param Where $where
     * @param array $whereArray
     *
     * @return array
     */
    public function writeWhereIsNulls(Where $where, array &$whereArray)
    {
        $whereArray = array_merge(
            $whereArray,
            $this->writeWhereIsNullable($where, 'getNull', 'writeIsNull')
        );
    }

    /**
     * @param Where  $where
     * @param string $getMethod
     * @param string $writeMethod
     *
     * @return array
     */
    public function writeWhereIsNullable(Where $where, $getMethod, $writeMethod)
    {
        $collection = $where->$getMethod();

        $me = $this;
        array_walk(
            $collection,
            function (&$collection) use ($writeMethod, $me) {
                $collection =
                    "(" . $me->columnWriter->writeColumn($collection["subject"])
                    . $me->writer->$writeMethod() . ")";
            }
        );

        return $collection;
    }

    /**
     * @param Where $where
     * @param array $whereArray
     *
     * @return array
     */
    public function writeWhereIsNotNulls(Where $where, array &$whereArray)
    {
        $whereArray = array_merge(
            $whereArray,
            $this->writeWhereIsNullable($where, 'getNotNull', 'writeIsNotNull')
        );
    }

    /**
     * @param Where $where
     * @param array $whereArray
     *
     * @return array
     */
    public function writeWhereBooleans(Where $where, array &$whereArray)
    {
        $booleans          = $where->getBooleans();
        $placeholderWriter = $this->placeholderWriter;

        $me = $this;
        array_walk(
            $booleans,
            function (&$boolean) use (&$placeholderWriter, $me) {
                $column = $me->columnWriter->writeColumn($boolean["subject"]);
                $value  = $me->placeholderWriter->add($boolean["value"]);

                $boolean = "(ISNULL(" . $column . ", 0) = " . $value . ")";
            }
        );

        $whereArray = array_merge($whereArray, $booleans);
    }

    /**
     * @param Where $where
     * @param array $whereArray
     *
     * @return array
     */
    public function writeExists(Where $where, array &$whereArray)
    {
        $whereArray = array_merge(
            $whereArray,
            $this->writeExistence($where, 'getExists', 'EXISTS')
        );
    }

    /**
     * @param Where  $where
     * @param string $method
     * @param string $operation
     *
     * @return array
     */
    public function writeExistence(Where $where, $method, $operation)
    {
        $exists = array();

        foreach ($where->$method() as $select) {
            $exists[] = "$operation (" . $this->writer->write($select, false) . ")";
        }

        return $exists;
    }

    /**
     * @param Where $where
     * @param array $whereArray
     *
     * @return array
     */
    public function writeNotExists(Where $where, array &$whereArray)
    {
        $whereArray = array_merge(
            $whereArray,
            $this->writeExistence($where, 'getNotExists', 'NOT EXISTS')
        );
    }

    /**
     * @param Where $where
     * @param array $whereArray
     *
     * @return array
     */
    public function writeSubWheres(Where $where, array &$whereArray)
    {
        $subWheres = $where->getSubWheres();

        $me = $this;
        array_walk(
            $subWheres,
            function (&$subWhere) use ($me) {
                $subWhere = "({$me->writeWhere($subWhere)})";
            }
        );

        $whereArray = array_merge($whereArray, $subWheres);
    }
}
