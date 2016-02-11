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

namespace Doctrine\DBAL\Driver\ASE;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;

/**
 * ASE Connection implementation.
 *
 * @since 2.6
 * @author Maximilian Ruta <mr@xtain.net>
 */
class ASEConnection implements Connection, ServerInfoAwareConnection
{
    /**
     * @var resource
     */
    protected $connectionResource;

    /**
     * @var ASEMessageHandler
     */
    protected $messageHandler;

    /**
     * @var string
     */
    protected $appname;

    /**
     * @var \Doctrine\DBAL\Driver\ASE\LastInsertId
     */
    protected $lastInsertId;

    /**
     * @param string $server
     * @param array  $driverOptions
     *
     * @throws \Doctrine\DBAL\Driver\ASE\ASEException
     */
    public function __construct($server, $driverOptions)
    {
        $this->appname = md5(uniqid());

        $username = null;
        $password = null;
        $charset = null;
        $database = null;
        if (isset($driverOptions['user'])) {
            $username = $driverOptions['user'];
        }

        if (isset($driverOptions['password'])) {
            $password = $driverOptions['password'];
        }

        if (isset($driverOptions['charset'])) {
            $charset = $driverOptions['charset'];
        }

        if (isset($driverOptions['dbname'])) {
            $database = $driverOptions['dbname'];
        }

        ASEMessageHandler::registerLogger();
        ASEMessageHandler::clearGlobal();

        try {
            $this->connectionResource = sybase_connect($server, $username, $password, $charset, $this->appname, true);
        } catch (\Throwable $e) {
            throw ASEMessageHandler::fromThrowable($e);
        } catch (\Exception $e) {
            throw ASEMessageHandler::fromThrowable($e);
        }
        $this->messageHandler = new ASEMessageHandler($this->connectionResource);

        if (!$this->connectionResource) {
            throw $this->messageHandler->getLastException();
        }

        if (isset($database)) {
            $this->messageHandler->clear();
            sybase_select_db($database, $this->connectionResource);

            if ($this->messageHandler->hasError()) {
                throw $this->messageHandler->getLastException();
            }
        }

        $this->lastInsertId = new LastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function getServerVersion()
    {
        $result = $this->prepare('SELECT @@version');
        $result->execute();
        return $result->fetchColumn(0);
    }

    /**
     * {@inheritdoc}
     */
    public function requiresQueryForServerVersion()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function prepare($sql)
    {
        return new ASEStatement($this, $this->connectionResource, $sql, $this->messageHandler, $this->lastInsertId);
    }

    /**
     * {@inheritDoc}
     */
    public function query()
    {
        $args = func_get_args();
        $sql = $args[0];
        $stmt = $this->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    /**
     * {@inheritDoc}
     * @license New BSD, code from Zend Framework
     */
    public function quote($value, $type=\PDO::PARAM_STR)
    {
        if (is_null($value)) {
            return 'NULL';
        } elseif (is_int($value)) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        }

        return "'" . str_replace("'", "''", $value) . "'";
    }

    /**
     * {@inheritDoc}
     */
    public function exec($statement)
    {
        $stmt = $this->prepare($statement);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId($name = null)
    {
        // there is currently no possibility to get the last insert id for specific tables
        return $this->lastInsertId->getId();
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
        $this->messageHandler->clear();
        $this->exec('BEGIN TRANSACTION');
        if ($this->messageHandler->hasError()) {
            throw $this->messageHandler->getLastError();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        $this->messageHandler->clear();
        $this->exec('COMMIT TRANSACTION');
        if ($this->messageHandler->hasError()) {
            throw $this->messageHandler->getLastError();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
        $this->messageHandler->clear();
        $this->exec('ROLLBACK TRANSACTION');
        if ($this->messageHandler->hasError()) {
            throw $this->messageHandler->getLastError();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function errorCode()
    {
        $message = $this->messageHandler->getLastError();

        if ($message !== null) {
            return $message->getCode();
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function errorInfo()
    {
        $message = $this->messageHandler->getLastError();

        if ($message !== null) {
            return $message->getInfo();
        }

        return false;
    }
}