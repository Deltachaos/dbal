<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Driver\ASE\ASEException;
use Doctrine\DBAL\Types\Type;

/**
 * ASE Schema Manager.
 *
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @author  Maximilian Ruta <mr@xtain.net>
 * @since   2.6
 */
class ASESchemaManager extends AbstractSchemaManager
{
    /**
     * {@inheritdoc}
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        $dbType = strtok($tableColumn['type'], '(), ');
        $fixed = null;
        $length = (int) $tableColumn['length'];
        $default = $tableColumn['default'];

        if (!isset($tableColumn['name'])) {
            $tableColumn['name'] = '';
        }

        while ($default != ($default2 = preg_replace("/^\((.*)\)$/", '$1', $default))) {
            $default = trim($default2, "'");

            if ($default == 'getdate()') {
                $default = $this->_platform->getCurrentTimestampSQL();
            }
        }

        switch ($dbType) {
            case 'nchar':
            case 'nvarchar':
            case 'ntext':
                // Unicode data requires 2 bytes per character
                $length = $length / 2;
                break;
            case 'varchar':
                // TEXT type is returned as VARCHAR(MAX) with a length of -1
                if ($length == -1) {
                    $dbType = 'text';
                }
                break;
        }

        if ('char' === $dbType || 'nchar' === $dbType || 'binary' === $dbType) {
            $fixed = true;
        }

        $type                   = $this->_platform->getDoctrineTypeMapping($dbType);
        $type                   = $this->extractDoctrineTypeFromComment($tableColumn['comment'], $type);
        $tableColumn['comment'] = $this->removeDoctrineTypeFromComment($tableColumn['comment'], $type);

        $options = array(
            'length'        => ($length == 0 || !in_array($type, array('text', 'string'))) ? null : $length,
            'unsigned'      => false,
            'fixed'         => (bool) $fixed,
            'default'       => $default !== 'NULL' ? $default : null,
            'notnull'       => (bool) $tableColumn['notnull'],
            'scale'         => $tableColumn['scale'],
            'precision'     => $tableColumn['precision'],
            'autoincrement' => (bool) $tableColumn['autoincrement'],
            'comment'       => $tableColumn['comment'] !== '' ? $tableColumn['comment'] : null,
        );

        $column = new Column($tableColumn['name'], Type::getType($type), $options);

        if (isset($tableColumn['collation']) && $tableColumn['collation'] !== 'NULL') {
            $column->setPlatformOption('collation', $tableColumn['collation']);
        }

        return $column;
    }
}
