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
 * 123 基类.
 *
 * @Entity(camel=true, bean=true, incrUpdate=false)
 * @Table(name=@ConfigValue(name="@app.models.app\Model\Diff1.name", default="tb_diff1"), usePrefix=false, id={"id"}, dbPoolName=@ConfigValue(name="@app.models.app\Model\Diff1.poolName"))
 * @DDL(sql="Q1JFQVRFIFRBQkxFIGB0Yl9kaWZmMWAgKCAgIGBtb2RpZnlgIHRleHQgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgTk9UIE5VTEwsICAgYGlkYCBpbnQoMTApIHVuc2lnbmVkIE5PVCBOVUxMIEFVVE9fSU5DUkVNRU5ULCAgIGBpbmRleDFgIHZhcmNoYXIoMjU1KSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsICAgYGluZGV4MmAgdmFyY2hhcigyNTUpIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwgICBgYWRkYCB0aW1lc3RhbXAgTlVMTCBERUZBVUxUIENVUlJFTlRfVElNRVNUQU1QIE9OIFVQREFURSBDVVJSRU5UX1RJTUVTVEFNUCwgICBQUklNQVJZIEtFWSAoYGlkYCkgVVNJTkcgQlRSRUUsICAgS0VZIGBpbmRleF9tb2RpZnlgIChgaW5kZXgxYCxgaW5kZXgyYCkgVVNJTkcgQlRSRUUsICAgS0VZIGB0Yl9kaWZmMV9pYmZrXzFgIChgaW5kZXgyYCkgVVNJTkcgQlRSRUUgKSBFTkdJTkU9SW5ub0RCIERFRkFVTFQgQ0hBUlNFVD11dGY4bWI0IENPTExBVEU9dXRmOG1iNF91bmljb2RlX2NpIFJPV19GT1JNQVQ9RFlOQU1JQyBDT01NRU5UPScxMjMnIC8qITUwMTAwIFBBUlRJVElPTiBCWSBIQVNIIChgaWRgKSAoUEFSVElUSU9OIHAwIEVOR0lORSA9IElubm9EQiwgIFBBUlRJVElPTiBwMSBFTkdJTkUgPSBJbm5vREIsICBQQVJUSVRJT04gcDIgRU5HSU5FID0gSW5ub0RCLCAgUEFSVElUSU9OIHAzIEVOR0lORSA9IElubm9EQikgKi8=", decode="base64_decode")
 *
 * @property string|null $modify
 * @property int|null    $id
 * @property string|null $index1
 * @property string|null $index2
 * @property string|null $add
 */
abstract class Diff1Base extends Model
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
     * modify.
     *
     * @Column(name="modify", type="text", length=0, accuracy=0, nullable=false, default="", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false, unsigned=false, virtual=false)
     */
    protected ?string $modify = null;

    /**
     * 获取 modify.
     */
    public function getModify(): ?string
    {
        return $this->modify;
    }

    /**
     * 赋值 modify.
     *
     * @param string|null $modify modify
     *
     * @return static
     */
    public function setModify($modify)
    {
        if (\is_string($modify) && mb_strlen($modify) > 65535)
        {
            throw new \InvalidArgumentException('The maximum length of $modify is 65535');
        }
        $this->modify = null === $modify ? null : (string) $modify;

        return $this;
    }

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
     * index1.
     *
     * @Column(name="index1", type="varchar", length=255, accuracy=0, nullable=true, default="", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false, unsigned=false, virtual=false)
     */
    protected ?string $index1 = null;

    /**
     * 获取 index1.
     */
    public function getIndex1(): ?string
    {
        return $this->index1;
    }

    /**
     * 赋值 index1.
     *
     * @param string|null $index1 index1
     *
     * @return static
     */
    public function setIndex1($index1)
    {
        if (\is_string($index1) && mb_strlen($index1) > 255)
        {
            throw new \InvalidArgumentException('The maximum length of $index1 is 255');
        }
        $this->index1 = null === $index1 ? null : (string) $index1;

        return $this;
    }

    /**
     * index2.
     *
     * @Column(name="index2", type="varchar", length=255, accuracy=0, nullable=true, default="", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false, unsigned=false, virtual=false)
     */
    protected ?string $index2 = null;

    /**
     * 获取 index2.
     */
    public function getIndex2(): ?string
    {
        return $this->index2;
    }

    /**
     * 赋值 index2.
     *
     * @param string|null $index2 index2
     *
     * @return static
     */
    public function setIndex2($index2)
    {
        if (\is_string($index2) && mb_strlen($index2) > 255)
        {
            throw new \InvalidArgumentException('The maximum length of $index2 is 255');
        }
        $this->index2 = null === $index2 ? null : (string) $index2;

        return $this;
    }

    /**
     * add.
     *
     * @Column(name="add", type="timestamp", length=0, accuracy=0, nullable=true, default="CURRENT_TIMESTAMP", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false, unsigned=false, virtual=false)
     */
    protected ?string $add = null;

    /**
     * 获取 add.
     */
    public function getAdd(): ?string
    {
        return $this->add;
    }

    /**
     * 赋值 add.
     *
     * @param string|null $add add
     *
     * @return static
     */
    public function setAdd($add)
    {
        $this->add = null === $add ? null : (string) $add;

        return $this;
    }
}
