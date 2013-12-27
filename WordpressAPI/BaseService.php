<?php
/**
 * File containing the BaseService class.
 *
 * @copyright Copyright (C) 2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */
namespace BD\Bundle\EzWordpressAPIBundle\WordpressAPI;

use eZ\Publish\API\Repository\Repository;

class BaseService
{
    /** @var Repository */
    protected $repository;

    public function setRepository( Repository $repository )
    {
        $this->repository = $repository;
    }

    /**
     * @return Repository
     */
    protected function getRepository()
    {
        return $this->repository;
    }

    protected function login( $username, $password )
    {
        $this->getRepository()->setCurrentUser(
            $this->getRepository()->getUserService()->loadUserByCredentials( $username, $password )
        );
    }
}
