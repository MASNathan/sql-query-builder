<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 6/3/14
 * Time: 12:07 AM
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NilPortugues\Sql\QueryBuilder\Manipulation;

/**
 * Class Update
 * @package NilPortugues\Sql\QueryBuilder\Manipulation
 */
class Update extends AbstractCreationalQuery
{
    /**
     * @var int
     */
    public $limitStart;

    /**
     * @var array
     */
    public $orderBy = array();

    /**
     * @return string
     */
    public function partName()
    {
        return 'UPDATE';
    }

    /**
     * @return int
     */
    public function getLimitStart()
    {
        return $this->limitStart;
    }

    /**
     * @param integer $start
     *
     * @return $this
     */
    public function limit($start)
    {
        $this->limitStart = $start;

        return $this;
    }
}
