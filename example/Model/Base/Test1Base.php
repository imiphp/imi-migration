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
 * tb_test1 基类.
 *
 * @Entity(camel=true, bean=true, incrUpdate=false)
 * @Table(name=@ConfigValue(name="@app.models.app\Model\Test1.name", default="tb_test1"), usePrefix=false, id={"id"}, dbPoolName=@ConfigValue(name="@app.models.app\Model\Test1.poolName"))
 * @DDL(sql="CREATE TABLE `tb_test1` (   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,   `b` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,   PRIMARY KEY (`id`) USING BTREE,   KEY `b` (`b`) USING BTREE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC")
 *
 * @property int|null    $id
 * @property string|null $b
 */
abstract class Test1Base extends Model
{
    /**
     * {@inheritdoc}
     */
    public const PRIMARY_KEY = 'id';

    /**
     * {@inheritdoc}
     */
    public const PRIMARY_KEYS = ['id'];

    /**
     * id.
     *
     * @Column(name="id", type="int", length=10, accuracy=0, nullable=false, default="", isPrimaryKey=true, primaryKeyIndex=0, isAutoIncrement=true, unsigned=true, virtual=false)
     */
    protected ?int $id = null;

    /**
     * 获取 id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * 赋值 id.
     *
     * @param int|null $id id
     *
     * @return static
     */
    public function setId($id)
    {
        $this->id = null === $id ? null : (int) $id;

        return $this;
    }

    /**
     * b.
     *
     * @Column(name="b", type="varchar", length=255, accuracy=0, nullable=false, default="", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false, unsigned=false, virtual=false)
     */
    protected ?string $b = null;

    /**
     * 获取 b.
     */
    public function getB(): ?string
    {
        return $this->b;
    }

    /**
     * 赋值 b.
     *
     * @param string|null $b b
     *
     * @return static
     */
    public function setB($b)
    {
        if (\is_string($b) && mb_strlen($b) > 255)
        {
            throw new \InvalidArgumentException('The maximum length of $b is 255');
        }
        $this->b = null === $b ? null : (string) $b;

        return $this;
    }
}
