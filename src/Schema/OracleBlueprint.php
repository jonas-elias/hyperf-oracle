<?php

declare(strict_types=1);

namespace Hyperf\Database\Oracle\Schema;

use Hyperf\Database\Schema\Blueprint;

class OracleBlueprint extends Blueprint
{
    /**
     * Table comment.
     *
     * @var string|null
     */
    public $comment = null;

    /**
     * Column comments.
     *
     * @var array
     */
    public array $commentColumns = [];

    /**
     * Database prefix variable.
     *
     * @var string
     */
    protected $prefix = null;

    /**
     * Database object max length variable.
     *
     * @var int
     */
    protected int $maxLength = 30;

    /**
     * Set table prefix settings.
     *
     * @param string $prefix
     *
     * @return void
     */
    public function setTablePrefix(?string $prefix = ''): void
    {
        $this->prefix = $prefix;
    }

    /**
     * Set database object max length name settings.
     *
     * @param int $maxLength
     *
     * @return void
     */
    public function setMaxLength(?int $maxLength = 30): void
    {
        $this->maxLength = $maxLength;
    }

    /**
     * Create a new nvarchar2 column on the table.
     *
     * @param string $column
     * @param int $length
     *
     * @return \Hyperf\Database\Schema\ColumnDefinition
     */
    public function nvarchar2(string $column, ?int $length = 255): \Hyperf\Database\Schema\ColumnDefinition
    {
        return $this->addColumn('nvarchar2', $column, compact('length'));
    }

    /**
     * Create a default index name for the table.
     *
     * @param string $type
     * @param array $columns
     *
     * @return string
     */
    protected function createIndexName($type, array $columns): string
    {
        if (count($columns) <= 2) {
            $short_type = [
                'primary' => 'pk',
                'foreign' => 'fk',
                'unique'  => 'uk',
            ];

            $type = isset($short_type[$type]) ? $short_type[$type] : $type;

            $index = strtolower($this->prefix . $this->table . '_' . implode('_', $columns) . '_' . $type);

            $index = str_replace(['-', '.'], '_', $index);
            while (strlen($index) > $this->maxLength) {
                $parts = explode('_', $index);

                for ($i = 0; $i < count($parts); $i++) {
                    $len = strlen($parts[$i]);
                    if ($len > 2) {
                        $parts[$i] = substr($parts[$i], 0, $len - 1);
                    }
                }

                $index = implode('_', $parts);
            }
        } else {
            $index = substr($this->table, 0, 10) . '_comp_' . str_replace('.', '_', microtime(true));
        }

        return $index;
    }
}
