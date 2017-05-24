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

namespace Doctrine\DBAL\Driver\PDOASE;

use Doctrine\DBAL\Driver\PDOStatement;

/**
 * PDOASE Statement.
 *
 * @since 2.6
 * @author Maximilian Ruta <Maximilian.Ruta@partner.commerzbank.com>
 */
class PDOASEStatement extends PDOStatement
{
    /**
     * @param mixed $value
     * @param int   $type
     *
     * @return mixed
     */
    protected function fixType($value, $type = \PDO::PARAM_STR)
    {
        switch ($type) {
            case \PDO::PARAM_NULL:
                return null;
            case \PDO::PARAM_INT:
                $float = floatval($value);
                $int = intval($float);
                if ($float && $int != $float) {
                    return $float;
                }

                return $int;
            case \PDO::PARAM_BOOL:
                return $value ? 1 : 0;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = \PDO::PARAM_STR)
    {
        return parent::bindValue($param, $this->fixType($value, $type), $type);
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($column, &$variable, $type = \PDO::PARAM_STR, $length = null, $driverOptions = null)
    {
        $variable = $this->fixType($variable, $type);
        return parent::bindParam($column, $variable, $type, $length, $driverOptions);
    }
}
