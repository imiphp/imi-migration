<?php

declare(strict_types=1);

namespace app\Model\Base;

use Imi\Config\Annotation\ConfigValue;
use Imi\Model\Annotation\Column;
use Imi\Model\Annotation\DDL;
use Imi\Model\Annotation\Entity;
use Imi\Model\Annotation\Table;
use Imi\Model\Model as Model;

/**
 * VIEW 基类.
 *
 * @Entity(camel=true, bean=true, incrUpdate=false)
 * @Table(name=@ConfigValue(name="@app.models.app\Model\V1.name", default="v1"), usePrefix=false, id={"a"}, dbPoolName=@ConfigValue(name="@app.models.app\Model\V1.poolName"))
 * @DDL(sql="CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `v1` AS select 1 AS `a`,2 AS `b`", decode="")
 *
 * @property int|null $a
 * @property int|null $b
 */
abstract class V1Base extends Model
{
    /**
     * {@inheritdoc}
     */
    public const PRIMARY_KEY = 'a';

    /**
     * {@inheritdoc}
     */
    public const PRIMARY_KEYS = ['a'];

    /**
     * a.
     *
     * @Column(name="a", type="int", length=1, accuracy=0, nullable=false, default="0", isPrimaryKey=true, primaryKeyIndex=0, isAutoIncrement=false, unsigned=false, virtual=false)
     */
    protected ?int $a = 0;

    /**
     * 获取 a.
     */
    public function getA(): ?int
    {
        return $this->a;
    }

    /**
     * 赋值 a.
     *
     * @param int|null $a a
     *
     * @return static
     */
    public function setA($a)
    {
        $this->a = null === $a ? null : (int) $a;

        return $this;
    }

    /**
     * b.
     *
     * @Column(name="b", type="int", length=1, accuracy=0, nullable=false, default="0", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false, unsigned=false, virtual=false)
     */
    protected ?int $b = 0;

    /**
     * 获取 b.
     */
    public function getB(): ?int
    {
        return $this->b;
    }

    /**
     * 赋值 b.
     *
     * @param int|null $b b
     *
     * @return static
     */
    public function setB($b)
    {
        $this->b = null === $b ? null : (int) $b;

        return $this;
    }
}
