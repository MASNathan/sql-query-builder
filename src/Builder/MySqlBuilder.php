<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 6/3/14
 * Time: 12:07 AM
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NilPortugues\Sql\QueryBuilder\Builder;

use NilPortugues\Sql\QueryBuilder\Syntax\Column;
use NilPortugues\Sql\QueryBuilder\Syntax\Table;

/**
 * Class MySqlBuilder
 * @package NilPortugues\Sql\QueryBuilder\Renderer
 */
class MySqlBuilder extends GenericBuilder
{
    /**
     * {@inheritdoc}
     *
     * @param Column $column
     *
     * @return string
     */
    public function writeColumnName(Column $column)
    {
        if ($column->isAll()) {
            return '*';
        }

        return $this->wrapper(parent::writeColumnName($column));
    }

    /**
     * {@inheritdoc}
     *
     * @param Table $table
     *
     * @return string
     */
    public function writeTableName(Table $table)
    {
        return $this->wrapper(parent::writeTableName($table));
    }

    /**
     * {@inheritdoc}
     *
     * @param $alias
     *
     * @return string
     */
    public function writeTableAlias($alias)
    {
        return $this->wrapper(parent::writeTableAlias($alias));
    }

    /**
     * {@inheritdoc}
     *
     * @param $alias
     *
     * @return string
     */
    public function writeColumnAlias($alias)
    {
        return $this->wrapper($alias);
    }

    /**
     * @param        $string
     * @param string $char
     *
     * @return string
     */
    public function wrapper($string, $char = '`')
    {
        return $char.$string.$char;
    }
}
