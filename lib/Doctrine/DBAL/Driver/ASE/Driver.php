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

use Doctrine\DBAL\Driver\AbstractASEDriver;

/**
 * Driver for ext/sybase_ct.
 *
 * @since 2.6
 * @author Maximilian Ruta <mr@xtain.net>
 */
class Driver extends AbstractASEDriver
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        if (!isset($params['server']) || isset($params['port']) || isset($params['host'])) {
            throw new ASEException(
                "Host and port are not supported by the sybase_ct extension. You need to give a servername. " .
                "The hostname and port then will be read from \$SYBASE/interfaces."
            );
        }

        $server = $params['server'];

        if (isset($params['dbname'])) {
            $driverOptions['dbname'] = $params['dbname'];
        }

        if (isset($username)) {
            $driverOptions['user'] = $username;
        }

        if (isset($username)) {
            $driverOptions['password'] = $password;
        }

        return new ASEConnection($server, $driverOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ase';
    }
}
